<?php

declare(strict_types=1);

namespace Modules\Tes\Models;

use CodeIgniter\Model;

/**
 * Schedule change log (table test_schedule_changes, FR-TEST-04). Insert-only:
 * rows are never updated or deleted, so the model is not Auditable (it is a
 * log itself; the services also write an audit event). Owner: work package B.
 *
 * Created with the phase 3 foundation as a shared interface (docs/phase-3-plan.md).
 */
class TestScheduleChangeModel extends Model
{
    public const ALLOCATED = 'allocated';
    public const ASSIGNED  = 'assigned';
    public const MOVED     = 'moved';
    public const REMOVED   = 'removed';

    protected $table          = 'test_schedule_changes';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $updatedField   = '';
    protected $allowedFields  = ['applicant_id', 'from_session_id', 'to_session_id', 'kind', 'reason', 'changed_by'];

    /**
     * Changes for one applicant, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function forApplicant(int $applicantId): array
    {
        return $this->where('applicant_id', $applicantId)->orderBy('id', 'DESC')->findAll();
    }

    /**
     * Changes with applicant, from/to session facts and staff name, newest
     * first (package B). Each row adds registration_number, full_name,
     * from_date/from_start/from_end/from_room, to_* likewise, staff_name.
     * Filters: applicant_id, from / to (Y-m-d, created_at), kind, q (number or name).
     *
     * @param array<string, mixed> $filters
     */
    public function detailed(array $filters = []): static
    {
        $this->select('test_schedule_changes.*, ap.registration_number, ap.full_name')
            ->select('fs.test_date AS from_date, fs.starts_at AS from_start, fs.ends_at AS from_end, fs.label AS from_label, fr.name AS from_room')
            ->select('ts.test_date AS to_date, ts.starts_at AS to_start, ts.ends_at AS to_end, ts.label AS to_label, tr.name AS to_room')
            ->select('COALESCE(NULLIF(u.full_name, \'\'), u.username) AS staff_name', false)
            ->join('applicants ap', 'ap.id = test_schedule_changes.applicant_id')
            ->join('test_sessions fs', 'fs.id = test_schedule_changes.from_session_id', 'left')
            ->join('test_rooms fr', 'fr.id = fs.room_id', 'left')
            ->join('test_sessions ts', 'ts.id = test_schedule_changes.to_session_id', 'left')
            ->join('test_rooms tr', 'tr.id = ts.room_id', 'left')
            ->join('users u', 'u.id = test_schedule_changes.changed_by', 'left');

        if (! empty($filters['applicant_id'])) {
            $this->where('test_schedule_changes.applicant_id', (int) $filters['applicant_id']);
        }

        if (! empty($filters['from'])) {
            $this->where('test_schedule_changes.created_at >=', $filters['from'] . ' 00:00:00');
        }

        if (! empty($filters['to'])) {
            $this->where('test_schedule_changes.created_at <=', $filters['to'] . ' 23:59:59');
        }

        if (! empty($filters['kind'])) {
            $this->where('test_schedule_changes.kind', $filters['kind']);
        }

        if (! empty($filters['q'])) {
            $this->groupStart()->like('ap.full_name', $filters['q'])->orLike('ap.registration_number', $filters['q'])->groupEnd();
        }

        return $this->orderBy('test_schedule_changes.id', 'DESC');
    }

    /**
     * The applicant's latest changes with details (see detailed()).
     *
     * @return list<array<string, mixed>>
     */
    public function latestForApplicant(int $applicantId, int $limit = 5): array
    {
        return $this->detailed(['applicant_id' => $applicantId])->findAll($limit);
    }
}
