<?php

declare(strict_types=1);

namespace Modules\Berkas\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Uploaded documents (table applicant_documents). Owner: work package C (Berkas).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class ApplicantDocumentModel extends Model
{
    use Auditable;

    protected $table          = 'applicant_documents';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'applicant_id', 'document_type_id', 'file_path', 'original_name', 'mime', 'size_bytes', 'status', 'note',
        'uploaded_by', 'verified_by', 'verified_at',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }
}
