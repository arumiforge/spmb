<?php

declare(strict_types=1);

namespace Modules\Tes\Libraries;

use DateTimeImmutable;
use Modules\Tes\Models\TestAssignmentModel;

/**
 * Read side of an applicant's test schedule, for other modules (Kartu card
 * and lookup, Pendaftaran panels) and for the Tes packages. Never writes.
 * Shared interface of the phase 3 foundation (docs/phase-3-plan.md);
 * signatures must not change.
 */
class TestSchedule
{
    /**
     * The applicant's schedule, or null when not scheduled:
     * ['assignment_id', 'session_id', 'status', 'status_label', 'date' (Y-m-d),
     *  'day' ("Sabtu, 12 Juni 2027"), 'time' ("07.30–09.30 WIB"), 'room', 'location',
     *  'session_label', 'label' (one line, see sessionLabel)].
     *
     * @return array<string, mixed>|null
     */
    public function forApplicant(int $applicantId): ?array
    {
        $row = model(TestAssignmentModel::class)->forApplicant($applicantId);

        if ($row === null) {
            return null;
        }

        return [
            'assignment_id' => (int) $row['id'],
            'session_id'    => (int) $row['session_id'],
            'status'        => (string) $row['status'],
            'status_label'  => lang('Status.test.' . $row['status']),
            'date'          => (string) $row['test_date'],
            'day'           => self::dayLabel((string) $row['test_date']),
            'time'          => self::timeLabel((string) $row['starts_at'], (string) $row['ends_at']),
            'room'          => (string) $row['room_name'],
            'location'      => (string) ($row['room_location'] ?? ''),
            'session_label' => (string) ($row['session_label'] ?? ''),
            'label'         => self::sessionLabel($row),
        ];
    }

    /**
     * Whether applicants may see their schedule (setting Tes.scheduleVisible).
     */
    public function isVisibleToApplicants(): bool
    {
        return (bool) setting('Tes.scheduleVisible');
    }

    /**
     * The "Jadwal Tes" line of the card: the session label, or "Belum
     * dijadwalkan" when there is none or the schedule is not shown yet.
     */
    public function cardLine(int $applicantId): string
    {
        $schedule = $this->isVisibleToApplicants() ? $this->forApplicant($applicantId) : null;

        return $schedule === null ? lang('Status.test.unscheduled') : $schedule['label'];
    }

    /**
     * "Sabtu, 12 Juni 2027 · 07.30–09.30 WIB · Ruang 1" from a session row
     * with test_date, starts_at, ends_at and room_name.
     *
     * @param array<string, mixed> $session
     */
    public static function sessionLabel(array $session): string
    {
        $parts = [
            self::dayLabel((string) $session['test_date']),
            self::timeLabel((string) $session['starts_at'], (string) $session['ends_at']),
        ];

        if (($session['room_name'] ?? '') !== '') {
            $parts[] = (string) $session['room_name'];
        }

        return implode(' · ', $parts);
    }

    /**
     * "Sabtu, 12 Juni 2027" (in the page's language on the public site, D-109).
     */
    public static function dayLabel(string $date): string
    {
        $day   = new DateTimeImmutable($date);
        $names = locale_day_names();

        return $names[(int) $day->format('w')] . (locale_is_rtl() ? '، ' : ', ') . tanggal_indo($day);
    }

    /**
     * "07.30–09.30 WIB" from TIME values ("07:30:00").
     */
    public static function timeLabel(string $start, string $end): string
    {
        $separator = locale_current() === 'id' ? '.' : ':';
        $format    = static fn (string $time): string => str_replace(':', $separator, substr($time, 0, 5));

        return $format($start) . '–' . $format($end) . ' ' . setting('School.timezoneLabel');
    }
}
