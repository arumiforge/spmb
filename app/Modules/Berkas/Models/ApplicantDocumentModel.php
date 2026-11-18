<?php

declare(strict_types=1);

namespace Modules\Berkas\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Uploaded documents (table applicant_documents). Owner: work package C (Berkas).
 *
 * One current (not soft-deleted) row per applicant and document type; a
 * replaced file keeps its row soft-deleted (D-036).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class ApplicantDocumentModel extends Model
{
    use Auditable;

    public const STATUSES = ['pending', 'valid', 'invalid', 'to_follow'];

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

    /**
     * Current documents of an applicant keyed by document type id (the newest
     * row wins if, unexpectedly, there are several).
     *
     * @return array<int, array<string, mixed>>
     */
    public function currentFor(int $applicantId): array
    {
        $rows = $this->where('applicant_id', $applicantId)
            ->orderBy('id', 'ASC')
            ->findAll();

        $byType = [];

        foreach ($rows as $row) {
            $byType[(int) $row['document_type_id']] = $row;
        }

        return $byType;
    }

    /**
     * The current document of one type, or null.
     *
     * @return array<string, mixed>|null
     */
    public function currentOf(int $applicantId, int $typeId): ?array
    {
        return $this->where('applicant_id', $applicantId)
            ->where('document_type_id', $typeId)
            ->orderBy('id', 'DESC')
            ->first();
    }
}
