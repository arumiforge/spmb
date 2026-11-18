<?php

declare(strict_types=1);

namespace Modules\Tes\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use Modules\Kartu\Services\CardTokenService;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Models\TestSessionModel;

/**
 * Test attendance (FR-TEST-03, D-061): finds an applicant from a scanned card
 * or a typed registration number, and records Hadir / Tidak hadir / undo.
 *
 * The only writer of test_assignments.status besides Scheduler (package B).
 * Writes skip the model's generic audit callbacks and log one meaningful
 * event per applicant instead (test_attendance, old → new status, D-042).
 * Refusals throw DomainException with a friendly Indonesian message.
 */
class Attendance
{
    /**
     * Statuses staff may record; "pending" is the undo (back to "Belum tes").
     *
     * @var list<string>
     */
    public const RECORDABLE = [TestAssignmentModel::PRESENT, TestAssignmentModel::ABSENT, TestAssignmentModel::PENDING];

    private readonly TestAssignmentModel $assignments;
    private readonly BaseConnection $db;

    public function __construct(?TestAssignmentModel $assignments = null)
    {
        $this->assignments = $assignments ?? model(TestAssignmentModel::class);
        $this->db          = db_connect();
    }

    /**
     * The applicant behind a card URL (…/kartu/<token>), a bare card code or a
     * registration number (exact, any letter case), with their current
     * schedule (TestSchedule::forApplicant, null when not scheduled).
     * Returns null when nothing matches; throws for a revoked card.
     *
     * @return array{applicant: array<string, mixed>, schedule: array<string, mixed>|null}|null
     *
     * @throws DomainException revoked card
     */
    public function resolve(string $input): ?array
    {
        $input = trim($input);

        if ($input === '' || mb_strlen($input) > 300) {
            return null;
        }

        $isUrl = preg_match('#/kartu/([A-Za-z0-9_-]+)/?(?:[?\#].*)?$#', $input, $m) === 1;
        $code  = $isUrl ? $m[1] : $input;
        $cards = new CardTokenService();

        $applicant = null;

        if (preg_match('/\A[A-Za-z0-9_-]{20,64}\z/', $code) === 1) {
            if ($cards->isRevoked($code)) {
                throw new DomainException(lang('TesKehadiran.scanRevoked'));
            }

            $applicant = $cards->resolve($code);
        }

        if ($applicant === null && ! $isUrl && mb_strlen($input) <= 30) {
            // The column collation is case-insensitive: "pmb-2027-0001" finds PMB-2027-0001.
            $applicant = model(ApplicantModel::class)->where('registration_number', $input)->first();
        }

        if ($applicant === null) {
            return null;
        }

        return [
            'applicant' => $applicant,
            'schedule'  => (new TestSchedule())->forApplicant((int) $applicant['id']),
        ];
    }

    /**
     * Records the applicant's attendance: present (sets attended_at and
     * recorded_by), absent (recorded_by) or pending (undo; clears both).
     * Recording the status they already have changes nothing and logs nothing.
     *
     * @return array<string, mixed> the assignment row after the change, plus
     *                              'previous' (old status) and 'changed' (bool)
     *
     * @throws DomainException unknown status, unknown or cancelled applicant, not scheduled
     */
    public function record(int $applicantId, string $status, int $staffId): array
    {
        if (! in_array($status, self::RECORDABLE, true)) {
            throw new DomainException(lang('TesKehadiran.badStatus'));
        }

        $applicant = model(ApplicantModel::class)->find($applicantId);

        if ($applicant === null) {
            throw new DomainException(lang('TesKehadiran.notApplicant'));
        }

        if ($applicant['status'] === 'cancelled') {
            throw new DomainException(lang('TesKehadiran.cancelled'));
        }

        $this->db->transException(true)->transStart();

        try {
            // Serialise two staff phones scanning the same card at once.
            $row = $this->db->query('SELECT * FROM test_assignments WHERE applicant_id = ? FOR UPDATE', [$applicantId])->getRowArray();

            if ($row === null) {
                throw new DomainException(lang('TesKehadiran.notScheduled'));
            }

            $previous = (string) $row['status'];

            if ($previous === $status) {
                $this->db->transComplete();

                return $row + ['previous' => $previous, 'changed' => false];
            }

            $changes = match ($status) {
                TestAssignmentModel::PRESENT => ['status' => $status, 'attended_at' => date('Y-m-d H:i:s'), 'recorded_by' => $staffId],
                TestAssignmentModel::ABSENT  => ['status' => $status, 'attended_at' => null, 'recorded_by' => $staffId],
                default                      => ['status' => $status, 'attended_at' => null, 'recorded_by' => null],
            };

            $this->assignments->allowCallbacks(false)->update((int) $row['id'], $changes);
            $this->audit($applicant, (int) $row['session_id'], $previous, $status, $staffId);
            $this->db->transComplete();
        } finally {
            $this->db->transException(false);
        }

        return array_merge($row, $changes, ['previous' => $previous, 'changed' => true]);
    }

    /**
     * After the session has ended, marks every participant still "Belum tes"
     * or "Dijadwalkan ulang" as absent. One audit event per applicant.
     *
     * @return int how many were marked
     *
     * @throws DomainException unknown session, session not ended yet
     */
    public function markRemainingAbsent(int $sessionId, int $staffId): int
    {
        $session = model(TestSessionModel::class)->find($sessionId);

        if ($session === null) {
            throw new DomainException(lang('TesKehadiran.notFound'));
        }

        if (! self::hasEnded($session)) {
            throw new DomainException(lang('TesKehadiran.notEnded'));
        }

        $this->db->transException(true)->transStart();

        try {
            $remaining = array_values(array_filter(
                $this->lockedParticipants($sessionId),
                static fn (array $row): bool => in_array($row['status'], [TestAssignmentModel::PENDING, TestAssignmentModel::RESCHEDULED], true),
            ));

            if ($remaining !== []) {
                $this->assignments->allowCallbacks(false)
                    ->whereIn('id', array_map(static fn (array $row): int => (int) $row['id'], $remaining))
                    ->set(['status' => TestAssignmentModel::ABSENT, 'attended_at' => null, 'recorded_by' => $staffId])
                    ->update();

                foreach ($remaining as $row) {
                    $this->audit($row, $sessionId, (string) $row['status'], TestAssignmentModel::ABSENT, $staffId, (int) $row['applicant_id']);
                }
            }

            $this->db->transComplete();
        } finally {
            $this->db->transException(false);
        }

        return count($remaining);
    }

    /**
     * Whether the session's end time has passed (server time).
     *
     * @param array<string, mixed> $session test_sessions row
     */
    public static function hasEnded(array $session): bool
    {
        return date('Y-m-d H:i:s') >= $session['test_date'] . ' ' . substr((string) $session['ends_at'], 0, 8);
    }

    /**
     * Whether the session is running now (between start and end, server time).
     *
     * @param array<string, mixed> $session test_sessions row
     */
    public static function isRunning(array $session): bool
    {
        $now = date('Y-m-d H:i:s');

        return $now >= $session['test_date'] . ' ' . substr((string) $session['starts_at'], 0, 8) && ! self::hasEnded($session);
    }

    /**
     * Current participants of the session (not cancelled, not deleted),
     * locked for the rest of the transaction.
     *
     * @return list<array<string, mixed>>
     */
    private function lockedParticipants(int $sessionId): array
    {
        return $this->db->query(
            "SELECT a.id, a.applicant_id, a.status, ap.registration_number
               FROM test_assignments a
               JOIN applicants ap ON ap.id = a.applicant_id
              WHERE a.session_id = ? AND ap.deleted_at IS NULL AND ap.status <> 'cancelled'
              ORDER BY a.id
                FOR UPDATE",
            [$sessionId],
        )->getResultArray();
    }

    /**
     * @param array<string, mixed> $applicant row with registration_number (and id unless $applicantId is given)
     */
    private function audit(array $applicant, int $sessionId, string $from, string $to, int $staffId, ?int $applicantId = null): void
    {
        service('audit')->log(
            'test_attendance',
            'applicants',
            $applicantId ?? (int) $applicant['id'],
            ['status' => $from],
            ['status' => $to, 'session_id' => $sessionId],
            lang('TesKehadiran.auditPresent', [
                (string) $applicant['registration_number'],
                lang('Status.test.' . $from),
                lang('Status.test.' . $to),
            ]),
            $staffId,
        );
    }
}
