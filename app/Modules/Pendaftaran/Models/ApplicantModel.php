<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Applicants (table applicants). Owner: work package B (Pendaftaran).
 * Every write to applicant data goes through this model so it is audited (D-015).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class ApplicantModel extends Model
{
    use Auditable;

    protected $table          = 'applicants';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'registration_number', 'academic_year', 'wave_id', 'method', 'status', 'document_status',
        'full_name', 'name_key', 'gender', 'birth_place', 'birth_date', 'nik', 'nisn', 'kk_number', 'religion',
        'address', 'village', 'district', 'regency', 'province', 'phone',
        'father_name', 'father_occupation', 'mother_name', 'mother_occupation', 'guardian_name', 'guardian_relation',
        'parent_phone', 'parent_email', 'previous_school_id', 'previous_school_name', 'graduation_year',
        'possible_duplicate', 'access_token_hash', 'notes', 'created_by', 'registered_at',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }
}
