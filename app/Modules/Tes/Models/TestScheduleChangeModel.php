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
}
