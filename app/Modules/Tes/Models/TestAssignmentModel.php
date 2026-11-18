<?php

declare(strict_types=1);

namespace Modules\Tes\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Applicants' test sessions and attendance (table test_assignments).
 * Owners: work package B (assign, move, remove — Services\Scheduler) and C
 * (attendance — Services\Attendance). Write only through those services so
 * every change is logged (FR-TEST-04) and audited.
 *
 * Created with the phase 3 foundation as a shared interface (docs/phase-3-plan.md).
 */
class TestAssignmentModel extends Model
{
    use Auditable;

    public const PENDING     = 'pending';
    public const PRESENT     = 'present';
    public const ABSENT      = 'absent';
    public const RESCHEDULED = 'rescheduled';

    /**
     * @var list<string>
     */
    public const STATUSES = [self::PENDING, self::PRESENT, self::ABSENT, self::RESCHEDULED];

    protected $table          = 'test_assignments';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'applicant_id', 'session_id', 'seat_number', 'status', 'attended_at', 'recorded_by', 'assigned_by',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }

    /**
     * The applicant's assignment with its session and room, or null:
     * assignment columns + test_date, starts_at, ends_at, session_label,
     * wave_id, room_name, room_location.
     *
     * @return array<string, mixed>|null
     */
    public function forApplicant(int $applicantId): ?array
    {
        $row = $this->db->table('test_assignments a')
            ->select('a.*, s.test_date, s.starts_at, s.ends_at, s.label AS session_label, s.wave_id, s.academic_year, r.name AS room_name, r.location AS room_location')
            ->join('test_sessions s', 's.id = a.session_id')
            ->join('test_rooms r', 'r.id = s.room_id')
            ->where('a.applicant_id', $applicantId)
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Participants of a session in seat/registration order: assignment columns
     * + registration_number, full_name, gender, applicant_status. Cancelled
     * and deleted applicants are left out.
     *
     * @return list<array<string, mixed>>
     */
    public function forSession(int $sessionId): array
    {
        return $this->db->table('test_assignments a')
            ->select('a.*, ap.registration_number, ap.full_name, ap.gender, ap.status AS applicant_status')
            ->join('applicants ap', 'ap.id = a.applicant_id')
            ->where('a.session_id', $sessionId)
            ->where('ap.deleted_at', null)
            ->where('ap.status <>', 'cancelled')
            ->orderBy('a.seat_number IS NULL', 'ASC', false)
            ->orderBy('a.seat_number', 'ASC')
            ->orderBy('ap.id', 'ASC')
            ->get()
            ->getResultArray();
    }
}
