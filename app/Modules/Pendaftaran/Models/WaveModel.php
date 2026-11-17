<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Registration waves (table waves). Owner: work package B (Pendaftaran).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class WaveModel extends Model
{
    use Auditable;

    protected $table          = 'waves';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'academic_year', 'name', 'starts_on', 'ends_on', 'quota', 'description', 'is_active',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }
}
