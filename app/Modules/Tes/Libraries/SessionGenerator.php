<?php

declare(strict_types=1);

namespace Modules\Tes\Libraries;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Tes\Models\TestHolidayModel;
use Modules\Tes\Models\TestRoomModel;
use Modules\Tes\Models\TestSessionModel;
use Throwable;

/**
 * Creates many test sessions at once (FR-TEST-01, D-059): every chosen
 * weekday in a date range × every time slot × every chosen room. Holidays are
 * skipped, and so is any session that would overlap an existing session in
 * the same room. Each session's quota is its room's capacity; its label
 * follows a pattern such as "Sesi {n}", where n counts the slots of the day
 * by start time. Work package A (docs/phase-3-plan.md).
 *
 * Input (both methods):
 *   date_from, date_to   Y-m-d, inclusive
 *   weekdays             list of ISO weekdays 1 (Monday) … 7 (Sunday)
 *   slots                list of ['start' => 'HH:MM', 'end' => 'HH:MM' or ''];
 *                        an empty end = start + Tes.defaultDurationMinutes
 *   rooms                list of room ids
 *   wave_id              null/'' = every wave, or a wave of the academic year
 *   label                label pattern, default "Sesi {n}"
 *   academic_year        optional, default setting Pendaftaran.academicYear
 */
class SessionGenerator
{
    public const MAX_DAYS     = 366;
    public const MAX_SESSIONS = 1000;
    public const MAX_SLOTS    = 8;

    /**
     * @var list<int> Monday … Saturday
     */
    public const DEFAULT_WEEKDAYS = [1, 2, 3, 4, 5, 6];

    public const DEFAULT_LABEL = 'Sesi {n}';

    /**
     * What create() would make, without writing anything:
     * ['errors' => field => message, 'input' => normalised input,
     *  'sessions' => rows to insert (+ room_name), 'days' => list of
     *  ['date', 'day', 'sessions', 'seats'], 'count', 'seats',
     *  'skippedHolidays' => list of ['date', 'day', 'note'],
     *  'skippedOverlaps' => list of ['date', 'day', 'time', 'room', 'conflict']].
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function preview(array $input): array
    {
        [$input, $errors, $rooms] = $this->normalise($input);
        $result                   = [
            'errors'          => $errors,
            'input'           => $input,
            'sessions'        => [],
            'days'            => [],
            'count'           => 0,
            'seats'           => 0,
            'skippedHolidays' => [],
            'skippedOverlaps' => [],
        ];

        if ($errors !== []) {
            return $result;
        }

        $sessions = model(TestSessionModel::class);
        $holidays = model(TestHolidayModel::class)->between($input['date_from'], $input['date_to']);
        $period   = new DatePeriod(
            new DateTimeImmutable($input['date_from']),
            new DateInterval('P1D'),
            (new DateTimeImmutable($input['date_to']))->modify('+1 day'),
        );

        foreach ($period as $day) {
            $date = $day->format('Y-m-d');

            if (! in_array((int) $day->format('N'), $input['weekdays'], true)) {
                continue;
            }

            if (isset($holidays[$date])) {
                $result['skippedHolidays'][] = ['date' => $date, 'day' => TestSchedule::dayLabel($date), 'note' => (string) ($holidays[$date]['note'] ?? '')];

                continue;
            }

            $dayCount = 0;
            $daySeats = 0;

            foreach ($input['slots'] as $n => $slot) {
                foreach ($rooms as $room) {
                    $other = $sessions->overlapping((int) $room['id'], $date, $slot['start'], $slot['end']);

                    if ($other !== null) {
                        $result['skippedOverlaps'][] = [
                            'date'     => $date,
                            'day'      => TestSchedule::dayLabel($date),
                            'time'     => TestSchedule::timeLabel($slot['start'], $slot['end']),
                            'room'     => (string) $room['name'],
                            'conflict' => trim(($other['label'] ?? '') . ' ' . TestSchedule::timeLabel((string) $other['starts_at'], (string) $other['ends_at'])),
                        ];

                        continue;
                    }

                    $quota                = min((int) $room['capacity'], TestSessionModel::QUOTA_MAX);
                    $result['sessions'][] = [
                        'academic_year' => $input['academic_year'],
                        'wave_id'       => $input['wave_id'],
                        'test_date'     => $date,
                        'starts_at'     => $slot['start'],
                        'ends_at'       => $slot['end'],
                        'room_id'       => (int) $room['id'],
                        'quota'         => $quota,
                        'label'         => mb_substr(str_replace('{n}', (string) ($n + 1), $input['label']), 0, 60),
                        'is_active'     => 1,
                        'room_name'     => (string) $room['name'],
                    ];
                    $dayCount++;
                    $daySeats += $quota;
                }
            }

            if ($dayCount > 0) {
                $result['days'][] = ['date' => $date, 'day' => TestSchedule::dayLabel($date), 'sessions' => $dayCount, 'seats' => $daySeats];
            }
        }

        $result['count'] = count($result['sessions']);
        $result['seats'] = array_sum(array_column($result['sessions'], 'quota'));

        if ($result['count'] > self::MAX_SESSIONS) {
            $result['errors']['date_to'] = lang('TesJadwal.genTooMany', [number_format($result['count'], 0, ',', '.'), self::MAX_SESSIONS]);
        }

        return $result;
    }

    /**
     * Creates the sessions of preview() in one transaction and logs one audit
     * event `sessions_generated` (model callbacks off, like D-042). Returns the
     * number of sessions created.
     *
     * @param array<string, mixed> $input
     *
     * @throws InvalidArgumentException with a friendly message when the input is
     *                                  not valid or nothing would be created
     */
    public function create(array $input, int $staffId): int
    {
        $db      = db_connect();
        $roomIds = array_values(array_filter(array_map('intval', (array) ($input['rooms'] ?? []))));
        $db->transBegin();

        try {
            // Lock the rooms first so two staff generating at once cannot double-book them.
            if ($roomIds !== []) {
                $db->query('SELECT id FROM ' . $db->prefixTable('test_rooms') . ' WHERE id IN ? FOR UPDATE', [$roomIds]);
            }

            $preview = $this->preview($input);

            if ($preview['errors'] !== []) {
                throw new InvalidArgumentException((string) reset($preview['errors']));
            }

            if ($preview['count'] === 0) {
                throw new InvalidArgumentException(lang('TesJadwal.genNothing'));
            }

            $now  = date('Y-m-d H:i:s');
            $rows = array_map(static function (array $row) use ($now): array {
                unset($row['room_name']);

                return $row + ['created_at' => $now, 'updated_at' => $now];
            }, $preview['sessions']);

            foreach (array_chunk($rows, 200) as $chunk) {
                $db->table('test_sessions')->insertBatch($chunk);
            }

            $in = $preview['input'];
            service('audit')->log('sessions_generated', 'test_sessions', null, null, [
                'count'     => $preview['count'],
                'date_from' => $in['date_from'],
                'date_to'   => $in['date_to'],
                'rooms'     => implode(', ', array_values(array_unique(array_column($preview['sessions'], 'room_name')))),
                'slots'     => implode(', ', array_map(static fn (array $s): string => substr($s['start'], 0, 5) . '–' . substr($s['end'], 0, 5), $in['slots'])),
                'wave_id'   => $in['wave_id'],
            ], lang('TesJadwal.genAudit', [$preview['count']]), $staffId > 0 ? $staffId : null);

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }

        return $preview['count'];
    }

    /**
     * Default form values: tomorrow … two weeks later, Monday–Saturday, one
     * slot 07:30 + default duration, every active room, every wave.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $start = '07:30';

        return [
            'date_from' => date('Y-m-d', strtotime('+1 day')),
            'date_to'   => date('Y-m-d', strtotime('+14 days')),
            'weekdays'  => self::DEFAULT_WEEKDAYS,
            'slots'     => [['start' => $start, 'end' => self::addMinutes($start, self::duration())]],
            'rooms'     => array_map('intval', array_column(model(TestRoomModel::class)->active(), 'id')),
            'wave_id'   => null,
            'label'     => self::DEFAULT_LABEL,
        ];
    }

    /**
     * Session length from setting Tes.defaultDurationMinutes (30…480).
     */
    public static function duration(): int
    {
        return max(30, min(480, (int) setting('Tes.defaultDurationMinutes')));
    }

    /**
     * "07:30" + 120 → "09:30" (clamped to 23:59).
     */
    public static function addMinutes(string $time, int $minutes): string
    {
        [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));
        $total   = min(23 * 60 + 59, $h * 60 + $m + $minutes);

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: list<array<string, mixed>>}
     */
    private function normalise(array $input): array
    {
        $errors = [];
        $year   = (string) ($input['academic_year'] ?? setting('Pendaftaran.academicYear'));
        $from   = trim((string) ($input['date_from'] ?? ''));
        $to     = trim((string) ($input['date_to'] ?? ''));

        if (! TestSessionModel::isDate($from)) {
            $errors['date_from'] = lang('TesJadwal.errDate');
        }

        if (! TestSessionModel::isDate($to)) {
            $errors['date_to'] = lang('TesJadwal.errDate');
        } elseif (! isset($errors['date_from'])) {
            $days = (int) (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->format('%r%a');

            if ($days < 0) {
                $errors['date_to'] = lang('TesJadwal.genToBeforeFrom');
            } elseif ($days >= self::MAX_DAYS) {
                $errors['date_to'] = lang('TesJadwal.genRangeTooLong', [self::MAX_DAYS]);
            }
        }

        $weekdays = array_values(array_unique(array_filter(
            array_map('intval', (array) ($input['weekdays'] ?? [])),
            static fn (int $d): bool => $d >= 1 && $d <= 7,
        )));
        sort($weekdays);

        if ($weekdays === []) {
            $errors['weekdays'] = lang('TesJadwal.genNoWeekday');
        }

        $slots = [];

        foreach ((array) ($input['slots'] ?? []) as $slot) {
            $start = trim((string) ($slot['start'] ?? ''));
            $end   = trim((string) ($slot['end'] ?? ''));

            if ($start === '' && $end === '') {
                continue;
            }

            if (! TestSessionModel::isTime($start)) {
                $errors['slots'] = lang('TesJadwal.genSlotInvalid');

                continue;
            }

            $end = $end === '' ? self::addMinutes($start, self::duration()) : $end;

            if (! TestSessionModel::isTime($end) || TestSessionModel::time($end) <= TestSessionModel::time($start)) {
                $errors['slots'] = lang('TesJadwal.genSlotEndBeforeStart', [substr($start, 0, 5)]);

                continue;
            }

            $slots[] = ['start' => TestSessionModel::time($start), 'end' => TestSessionModel::time($end)];
        }

        usort($slots, static fn (array $a, array $b): int => strcmp($a['start'], $b['start']));

        if ($slots === [] && ! isset($errors['slots'])) {
            $errors['slots'] = lang('TesJadwal.genNoSlot');
        } elseif (count($slots) > self::MAX_SLOTS) {
            $errors['slots'] = lang('TesJadwal.genTooManySlots', [self::MAX_SLOTS]);
        }

        for ($i = 1; $i < count($slots) && ! isset($errors['slots']); $i++) {
            if ($slots[$i]['start'] < $slots[$i - 1]['end']) {
                $errors['slots'] = lang('TesJadwal.genSlotsOverlap', [
                    substr($slots[$i - 1]['start'], 0, 5) . '–' . substr($slots[$i - 1]['end'], 0, 5),
                    substr($slots[$i]['start'], 0, 5) . '–' . substr($slots[$i]['end'], 0, 5),
                ]);
            }
        }

        $roomIds = array_values(array_unique(array_filter(array_map('intval', (array) ($input['rooms'] ?? [])))));
        $rooms   = $roomIds === [] ? [] : model(TestRoomModel::class)->whereIn('id', $roomIds)->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();

        if ($rooms === [] || count($rooms) !== count($roomIds)) {
            $errors['rooms'] = lang('TesJadwal.genNoRoom');
        }

        $waveId = $input['wave_id'] ?? null;
        $waveId = $waveId === null || $waveId === '' ? null : (int) $waveId;

        if ($waveId !== null) {
            $wave = model(WaveModel::class)->find($waveId);

            if ($wave === null || (string) $wave['academic_year'] !== $year) {
                $errors['wave_id'] = lang('TesJadwal.errWave');
            }
        }

        $label = trim((string) ($input['label'] ?? ''));
        $label = $label === '' ? self::DEFAULT_LABEL : mb_substr($label, 0, 60);

        return [[
            'academic_year' => $year,
            'date_from'     => $from,
            'date_to'       => $to,
            'weekdays'      => $weekdays,
            'slots'         => $slots,
            'rooms'         => array_map('intval', array_column($rooms, 'id')),
            'wave_id'       => $waveId,
            'label'         => $label,
        ], $errors, $rooms];
    }
}
