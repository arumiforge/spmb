<?php

declare(strict_types=1);

namespace Modules\Referensi\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Previous schools (table ref_sekolah). Owner: work package A (Referensi).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class SchoolModel extends Model
{
    use Auditable;

    protected $table          = 'ref_sekolah';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'npsn', 'name', 'level', 'ownership', 'province', 'regency', 'district', 'village', 'address', 'source', 'is_active',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }
}
