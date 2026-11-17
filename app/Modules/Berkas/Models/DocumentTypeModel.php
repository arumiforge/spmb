<?php

declare(strict_types=1);

namespace Modules\Berkas\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Document types applicants hand in (table document_types). Owner: work package C (Berkas).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class DocumentTypeModel extends Model
{
    use Auditable;

    protected $table          = 'document_types';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'code', 'name', 'description', 'is_required', 'may_follow', 'max_size_kb', 'extensions', 'sort_order', 'is_active',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }
}
