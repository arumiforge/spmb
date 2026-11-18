<?php

declare(strict_types=1);

namespace Modules\Tes\Services;

use App\Models\UserModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Models\TestScheduleChangeModel;
use Throwable;

/**
 * The only writer of test seats (test_assignments session/seat columns):
 * automatic allocation (FR-TEST-02, D-060), giving a seat, moving and removing.
 * Every change runs in one transaction with the target sessions locked
 * (SELECT … FOR UPDATE), adds a test_schedule_changes row (FR-TEST-04) and one
 * semantic audit event (model callbacks off, like D-042): test_allocated (one
 * per run), test_assigned, test_moved, test_removed — subject: the applicant.
 *
 * Seats taken = assignments of applicants that are not cancelled and not
 * deleted; a cancelled applicant frees the seat but keeps the row.
 * Refusals throw SchedulerException with a friendly Indonesian message.
 */
class Scheduler
{
    public const MIN_REASON = 5;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Places eligible applicants without a seat into active sessions from
     * from_date in time order, in registration order. Never moves anyone.
     *
     * Options: academic_year (default setting), wave_id (null = all waves),
     * from_date (Y-m-d, default tomorrow), staff_id (who runs it, for the
     * audit event; default the signed-in user). The seats themselves are
     * logged as automatic (assigned_by and changed_by null).
     *
     * @param array<string, mixed> $options
     *
     * @return array{placed: int, unplaced: int, eligible: int, sessions: list<array{session_id: int, label: string, session_label: string, count: int, first_number: string, last_number: string}>, unplacedNumbers: list<string>}
     */
    public function allocate(array $options, bool $dryRun = false): array
    {
        $year     = (string) ($options['academic_year'] ?? setting('Pendaftaran.academicYear'));
        $waveId   = isset($options['wave_id']) && $options['wave_id'] !== '' ? (int) $options['wave_id'] : null;
        $fromDate = self::validDate($options['from_date'] ?? null) ?? self::tomorrow();

        if ($dryRun) {
            return $this->plan($year, $waveId, $fromDate, false)['report'];
        }

        $this->db->transBegin();

        try {
            $plan = $this->plan($year, $waveId, $fromDate, true);
            $this->writePlan($plan['placements']);

            $report = $plan['report'];

            if ($report['placed'] > 0) {
                service('audit')->log('test_allocated', 'test_sessions', null, null, [
                    'academic_year' => $year,
                    'wave_id'       => $waveId,
                    'from_date'     => $fromDate,
                    'placed'        => $report['placed'],
                    'unplaced'      => $report['unplaced'],
                    'sessions'      => count($report['sessions']),
                ], lang('TesPenjadwalan.auditAllocated', [$report['placed']]), isset($options['staff_id']) ? (int) $options['staff_id'] : self::userId());
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return $report;
    }

    /**
     * Gives an applicant without a seat their first seat (kind "assigned",
     * status pending). $allowOverQuota: staff confirmed a full session (used
     * for walk-ins on the attendance page).
     *
     * @return array<string, mixed> the assignment with its session (TestAssignmentModel::forApplicant)
     *
     * @throws SchedulerException
     */
    public function assign(int $applicantId, int $sessionId, int $staffId, ?string $reason = null, bool $allowOverQuota = false): array
    {
        $reason = self::cleanReason($reason);

        return $this->inTransaction(function () use ($applicantId, $sessionId, $staffId, $reason, $allowOverQuota): void {
            $applicant = $this->lockApplicant($applicantId);
            $current   = $this->lockAssignment($applicantId);

            if ($current !== null) {
                throw new SchedulerException(lang('TesPenjadwalan.err.alreadyScheduled'));
            }

            $session = $this->lockSession($sessionId);
            $this->assertSessionFits($applicant, $session, false);

            if (! $allowOverQuota && $this->taken($sessionId) >= (int) $session['quota']) {
                throw new SchedulerException(lang('TesPenjadwalan.err.sessionFull'));
            }

            $row = [
                'applicant_id' => $applicantId,
                'session_id'   => $sessionId,
                'seat_number'  => $this->nextSeat($sessionId),
                'status'       => TestAssignmentModel::PENDING,
                'assigned_by'  => $staffId,
            ];
            model(TestAssignmentModel::class, false)->allowCallbacks(false)->insert($row);
            $this->logChange($applicantId, null, $sessionId, TestScheduleChangeModel::ASSIGNED, $reason, $staffId);
            service('audit')->log('test_assigned', 'applicants', $applicantId, null, [
                'session_id'  => $sessionId,
                'seat_number' => $row['seat_number'],
                'status'      => $row['status'],
            ], self::describe($applicant, null, $session, $reason), $staffId);
        }, $applicantId);
    }

    /**
     * Moves a scheduled applicant to another session: status "rescheduled",
     * attendance cleared, kind "moved". A full session is refused unless
     * $allowOverQuota (staff confirmed). A session in the past is allowed
     * only for staff with tes.atur.
     *
     * @return array<string, mixed> the assignment with its session (TestAssignmentModel::forApplicant)
     *
     * @throws SchedulerException
     */
    public function move(int $applicantId, int $sessionId, int $staffId, string $reason, bool $allowOverQuota = false): array
    {
        $reason = self::requireReason($reason);

        return $this->inTransaction(function () use ($applicantId, $sessionId, $staffId, $reason, $allowOverQuota): void {
            $applicant = $this->lockApplicant($applicantId);
            $current   = $this->lockAssignment($applicantId);

            if ($current === null) {
                throw new SchedulerException(lang('TesPenjadwalan.err.notScheduled'));
            }

            if ((int) $current['session_id'] === $sessionId) {
                throw new SchedulerException(lang('TesPenjadwalan.err.sameSession'));
            }

            $session = $this->lockSession($sessionId);
            $this->assertSessionFits($applicant, $session, self::userCan($staffId, 'tes.atur'));

            if (! $allowOverQuota && $this->taken($sessionId) >= (int) $session['quota']) {
                throw new SchedulerException(lang('TesPenjadwalan.err.sessionFullMove'));
            }

            $changes = [
                'session_id'  => $sessionId,
                'seat_number' => $this->nextSeat($sessionId),
                'status'      => TestAssignmentModel::RESCHEDULED,
                'attended_at' => null,
                'recorded_by' => null,
                'assigned_by' => $staffId,
            ];
            model(TestAssignmentModel::class, false)->allowCallbacks(false)->update((int) $current['id'], $changes);
            $this->logChange($applicantId, (int) $current['session_id'], $sessionId, TestScheduleChangeModel::MOVED, $reason, $staffId);

            $keys = ['session_id', 'seat_number', 'status', 'attended_at'];
            service('audit')->log(
                'test_moved',
                'applicants',
                $applicantId,
                array_intersect_key($current, array_flip($keys)),
                array_intersect_key($changes, array_flip($keys)),
                self::describe($applicant, $this->sessionRow((int) $current['session_id']), $session, $reason),
                $staffId,
            );
        }, $applicantId);
    }

    /**
     * Takes the applicant out of the test schedule (row deleted, kind "removed").
     * Someone already marked present cannot be removed (undo the attendance first).
     *
     * @throws SchedulerException
     */
    public function remove(int $applicantId, int $staffId, string $reason): void
    {
        $reason = self::requireReason($reason);

        $this->inTransaction(function () use ($applicantId, $staffId, $reason): void {
            $applicant = $this->lockApplicant($applicantId, true);
            $current   = $this->lockAssignment($applicantId);

            if ($current === null) {
                throw new SchedulerException(lang('TesPenjadwalan.err.notScheduled'));
            }

            if ($current['status'] === TestAssignmentModel::PRESENT) {
                throw new SchedulerException(lang('TesPenjadwalan.err.alreadyPresent'));
            }

            model(TestAssignmentModel::class, false)->allowCallbacks(false)->delete((int) $current['id']);
            $this->logChange($applicantId, (int) $current['session_id'], null, TestScheduleChangeModel::REMOVED, $reason, $staffId);
            service('audit')->log(
                'test_removed',
                'applicants',
                $applicantId,
                array_intersect_key($current, array_flip(['session_id', 'seat_number', 'status'])),
                null,
                self::describe($applicant, $this->sessionRow((int) $current['session_id']), null, $reason),
                $staffId,
            );
        }, null);
    }

    /**
     * Eligible applicants without a seat, per wave id ('' = without wave):
     * the numbers shown on the allocation page.
     *
     * @return array<string, int>
     */
    public function eligibleCounts(?string $academicYear = null): array
    {
        $rows = $this->eligibleBuilder($academicYear ?? (string) setting('Pendaftaran.academicYear'), null)
            ->select('ap.wave_id, COUNT(*) AS total')
            ->groupBy('ap.wave_id')
            ->get()
            ->getResultArray();
        $out = [];

        foreach ($rows as $row) {
            $out[(string) ($row['wave_id'] ?? '')] = (int) $row['total'];
        }

        return $out;
    }

    /**
     * Sessions that can take new applicants from $fromDate (active, not on a
     * holiday), in time order, with room and free seats.
     *
     * @return list<array<string, mixed>>
     */
    public function openSessions(string $academicYear, ?int $waveId, string $fromDate, bool $lock = false): array
    {
        $builder = $this->db->table('test_sessions s')
            ->select('s.*, r.name AS room_name, r.location AS room_location')
            ->join('test_rooms r', 'r.id = s.room_id')
            ->where('s.deleted_at', null)
            ->where('s.is_active', 1)
            ->where('s.academic_year', $academicYear)
            ->where('s.test_date >=', $fromDate)
            ->where('s.test_date NOT IN (SELECT h.date FROM ' . $this->db->prefixTable('test_holidays') . ' h)', null, false)
            ->orderBy('s.test_date', 'ASC')
            ->orderBy('s.starts_at', 'ASC')
            ->orderBy('r.sort_order', 'ASC')
            ->orderBy('r.name', 'ASC')
            ->orderBy('s.id', 'ASC');

        if ($waveId !== null) {
            $builder->groupStart()->where('s.wave_id', $waveId)->orWhere('s.wave_id', null)->groupEnd();
        }

        $sql      = $builder->getCompiledSelect() . ($lock ? ' FOR UPDATE' : '');
        $sessions = $this->db->query($sql)->getResultArray();

        if ($sessions === []) {
            return [];
        }

        $taken = $this->takenBySession(array_map('intval', array_column($sessions, 'id')));

        foreach ($sessions as &$session) {
            $session['filled']   = $taken[(int) $session['id']]['taken'] ?? 0;
            $session['free']     = max(0, (int) $session['quota'] - $session['filled']);
            $session['max_seat'] = $taken[(int) $session['id']]['max_seat'] ?? 0;
        }

        return $sessions;
    }

    /**
     * Sessions an applicant may be given or moved to (for the seat form):
     * open sessions from today of their year that accept their wave, except
     * their current one. Full sessions are included (free = 0).
     *
     * @param array<string, mixed> $applicant applicants row
     *
     * @return list<array<string, mixed>>
     */
    public function choicesFor(array $applicant, ?int $exceptSessionId = null): array
    {
        $waveId   = $applicant['wave_id'] === null ? null : (int) $applicant['wave_id'];
        $sessions = $this->openSessions((string) $applicant['academic_year'], $waveId, date('Y-m-d'));

        return array_values(array_filter($sessions, static function (array $s) use ($waveId, $exceptSessionId): bool {
            if ($exceptSessionId !== null && (int) $s['id'] === $exceptSessionId) {
                return false;
            }

            // A session of a wave takes only applicants of that wave.
            return $s['wave_id'] === null || ($waveId !== null && (int) $s['wave_id'] === $waveId);
        }));
    }

    // ------------------------------------------------------------------

    /**
     * @return array{placements: list<array<string, mixed>>, report: array<string, mixed>}
     */
    private function plan(string $year, ?int $waveId, string $fromDate, bool $lock): array
    {
        // Lock the sessions first so the applicants read next are current.
        $sessions = $this->openSessions($year, $waveId, $fromDate, $lock);

        $builder = $this->eligibleBuilder($year, $waveId)
            ->select('ap.id, ap.registration_number, ap.wave_id')
            ->orderBy('ap.id', 'ASC');
        $sql        = $builder->getCompiledSelect() . ($lock ? ' FOR UPDATE' : '');
        $applicants = $this->db->query($sql)->getResultArray();

        $placements = [];
        $perSession = [];
        $unplaced   = [];

        foreach ($applicants as $applicant) {
            $applicantWave = $applicant['wave_id'] === null ? null : (int) $applicant['wave_id'];
            $placed        = false;

            foreach ($sessions as &$session) {
                if ($session['free'] <= 0) {
                    continue;
                }

                if ($session['wave_id'] !== null && ($applicantWave === null || (int) $session['wave_id'] !== $applicantWave)) {
                    continue;
                }

                $session['free']--;
                $session['max_seat']++;
                $sessionId    = (int) $session['id'];
                $placements[] = [
                    'applicant_id' => (int) $applicant['id'],
                    'session_id'   => $sessionId,
                    'seat_number'  => $session['max_seat'],
                ];

                $perSession[$sessionId] ??= [
                    'session_id'    => $sessionId,
                    'label'         => TestSchedule::sessionLabel($session),
                    'session_label' => (string) ($session['label'] ?? ''),
                    'count'         => 0,
                    'first_number'  => (string) $applicant['registration_number'],
                    'last_number'   => '',
                ];
                $perSession[$sessionId]['count']++;
                $perSession[$sessionId]['last_number'] = (string) $applicant['registration_number'];
                $placed                                = true;

                break;
            }
            unset($session);

            if (! $placed) {
                $unplaced[] = (string) $applicant['registration_number'];
            }
        }

        // Report the sessions in time order (the order of $sessions).
        $ordered = [];

        foreach ($sessions as $session) {
            if (isset($perSession[(int) $session['id']])) {
                $ordered[] = $perSession[(int) $session['id']];
            }
        }

        return [
            'placements' => $placements,
            'report'     => [
                'placed'          => count($placements),
                'unplaced'        => count($unplaced),
                'eligible'        => count($applicants),
                'sessions'        => $ordered,
                'unplacedNumbers' => $unplaced,
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $placements
     */
    private function writePlan(array $placements): void
    {
        if ($placements === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        foreach (array_chunk($placements, 500) as $chunk) {
            $this->db->table('test_assignments')->insertBatch(array_map(static fn (array $p): array => $p + [
                'status'      => TestAssignmentModel::PENDING,
                'assigned_by' => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ], $chunk));

            $this->db->table('test_schedule_changes')->insertBatch(array_map(static fn (array $p): array => [
                'applicant_id'    => $p['applicant_id'],
                'from_session_id' => null,
                'to_session_id'   => $p['session_id'],
                'kind'            => TestScheduleChangeModel::ALLOCATED,
                'reason'          => null,
                'changed_by'      => null,
                'created_at'      => $now,
            ], $chunk));
        }
    }

    /**
     * Applicants of the year that may get a seat automatically and have none.
     */
    private function eligibleBuilder(string $year, ?int $waveId): BaseBuilder
    {
        $statuses = array_values(array_diff((array) setting('Tes.eligibleStatuses'), ['cancelled']));
        $builder  = $this->db->table('applicants ap')
            ->where('ap.academic_year', $year)
            ->where('ap.deleted_at', null)
            ->whereIn('ap.status', $statuses === [] ? [''] : $statuses)
            ->where('NOT EXISTS (SELECT 1 FROM ' . $this->db->prefixTable('test_assignments') . ' ta WHERE ta.applicant_id = ap.id)', null, false);

        if ($waveId !== null) {
            $builder->where('ap.wave_id', $waveId);
        }

        if ((bool) setting('Tes.requireCompleteDocuments')) {
            $builder->where('ap.document_status', 'complete');
        }

        return $builder;
    }

    /**
     * Seats taken and the highest seat number per session.
     *
     * @param list<int> $sessionIds
     *
     * @return array<int, array{taken: int, max_seat: int}>
     */
    private function takenBySession(array $sessionIds): array
    {
        $rows = $this->db->table('test_assignments a')
            ->select("a.session_id, SUM(ap.id IS NOT NULL AND ap.deleted_at IS NULL AND ap.status <> 'cancelled') AS taken, COALESCE(MAX(a.seat_number), 0) AS max_seat", false)
            ->join('applicants ap', 'ap.id = a.applicant_id', 'left')
            ->whereIn('a.session_id', $sessionIds)
            ->groupBy('a.session_id')
            ->get()
            ->getResultArray();
        $out = [];

        foreach ($rows as $row) {
            $out[(int) $row['session_id']] = ['taken' => (int) $row['taken'], 'max_seat' => (int) $row['max_seat']];
        }

        return $out;
    }

    private function taken(int $sessionId): int
    {
        return $this->takenBySession([$sessionId])[$sessionId]['taken'] ?? 0;
    }

    private function nextSeat(int $sessionId): int
    {
        return ($this->takenBySession([$sessionId])[$sessionId]['max_seat'] ?? 0) + 1;
    }

    /**
     * Runs $work in one transaction and returns the applicant's assignment.
     *
     * @return array<string, mixed>
     */
    private function inTransaction(callable $work, ?int $applicantId): array
    {
        $this->db->transBegin();

        try {
            $work();
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return $applicantId === null ? [] : (model(TestAssignmentModel::class, false)->forApplicant($applicantId) ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function lockApplicant(int $applicantId, bool $allowCancelled = false): array
    {
        $applicant = $this->db->query('SELECT * FROM ' . $this->db->prefixTable('applicants') . ' WHERE id = ? FOR UPDATE', [$applicantId])->getRowArray();

        if ($applicant === null || $applicant['deleted_at'] !== null) {
            throw new SchedulerException(lang('TesPenjadwalan.err.applicantMissing'));
        }

        if (! $allowCancelled && $applicant['status'] === 'cancelled') {
            throw new SchedulerException(lang('TesPenjadwalan.err.applicantCancelled'));
        }

        return $applicant;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lockAssignment(int $applicantId): ?array
    {
        return $this->db->query('SELECT * FROM ' . $this->db->prefixTable('test_assignments') . ' WHERE applicant_id = ? FOR UPDATE', [$applicantId])->getRowArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function lockSession(int $sessionId): array
    {
        $session = $this->db->query(
            'SELECT s.*, r.name AS room_name, r.location AS room_location FROM ' . $this->db->prefixTable('test_sessions') . ' s'
            . ' JOIN ' . $this->db->prefixTable('test_rooms') . ' r ON r.id = s.room_id WHERE s.id = ? FOR UPDATE',
            [$sessionId],
        )->getRowArray();

        if ($session === null || $session['deleted_at'] !== null) {
            throw new SchedulerException(lang('TesPenjadwalan.err.sessionMissing'));
        }

        return $session;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sessionRow(int $sessionId): ?array
    {
        return $this->db->table('test_sessions s')
            ->select('s.*, r.name AS room_name')
            ->join('test_rooms r', 'r.id = s.room_id')
            ->where('s.id', $sessionId)
            ->get(1)
            ->getRowArray();
    }

    /**
     * @param array<string, mixed> $applicant
     * @param array<string, mixed> $session
     *
     * @throws SchedulerException
     */
    private function assertSessionFits(array $applicant, array $session, bool $mayUsePast): void
    {
        if (! (bool) $session['is_active']) {
            throw new SchedulerException(lang('TesPenjadwalan.err.sessionInactive'));
        }

        if ((string) $session['academic_year'] !== (string) $applicant['academic_year']) {
            throw new SchedulerException(lang('TesPenjadwalan.err.otherYear'));
        }

        if ($session['wave_id'] !== null && (int) $session['wave_id'] !== (int) ($applicant['wave_id'] ?? 0)) {
            throw new SchedulerException(lang('TesPenjadwalan.err.otherWave'));
        }

        if ($this->db->table('test_holidays')->where('date', $session['test_date'])->countAllResults() > 0) {
            throw new SchedulerException(lang('TesPenjadwalan.err.holiday'));
        }

        if (! $mayUsePast && (string) $session['test_date'] < date('Y-m-d')) {
            throw new SchedulerException(lang('TesPenjadwalan.err.past'));
        }
    }

    private function logChange(int $applicantId, ?int $from, ?int $to, string $kind, ?string $reason, ?int $staffId): void
    {
        $this->db->table('test_schedule_changes')->insert([
            'applicant_id'    => $applicantId,
            'from_session_id' => $from,
            'to_session_id'   => $to,
            'kind'            => $kind,
            'reason'          => $reason,
            'changed_by'      => $staffId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * "PMB-2027-0001 · Sabtu, 12 Juni 2027 … → … · alasan".
     *
     * @param array<string, mixed>      $applicant
     * @param array<string, mixed>|null $from
     * @param array<string, mixed>|null $to
     */
    private static function describe(array $applicant, ?array $from, ?array $to, ?string $reason): string
    {
        $parts = [(string) $applicant['registration_number']];

        if ($from !== null && $to !== null) {
            $parts[] = TestSchedule::sessionLabel($from) . ' → ' . TestSchedule::sessionLabel($to);
        } elseif ($to !== null || $from !== null) {
            $parts[] = TestSchedule::sessionLabel($to ?? $from);
        }

        if ($reason !== null && $reason !== '') {
            $parts[] = $reason;
        }

        return implode(' · ', $parts);
    }

    private static function cleanReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : mb_substr($reason, 0, 255);
    }

    /**
     * @throws SchedulerException
     */
    private static function requireReason(string $reason): string
    {
        $reason = self::cleanReason($reason);

        if ($reason === null || mb_strlen($reason) < self::MIN_REASON) {
            throw new SchedulerException(lang('TesPenjadwalan.err.reason', [self::MIN_REASON]));
        }

        return $reason;
    }

    private static function userCan(int $userId, string $permission): bool
    {
        $user = model(UserModel::class, false)->findById($userId);

        return $user !== null && $user->can($permission);
    }

    private static function userId(): ?int
    {
        $id = function_exists('auth') && auth()->loggedIn() ? auth()->id() : null;

        return $id === null ? null : (int) $id;
    }

    public static function validDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    public static function tomorrow(): string
    {
        return (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
    }
}
