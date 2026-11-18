<?php

declare(strict_types=1);

namespace Modules\Tes\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Test rooms (table test_rooms). Owner: work package A (Jadwal).
 *
 * Created with the phase 3 foundation as a shared interface (docs/phase-3-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class TestRoomModel extends Model
{
    use Auditable;

    protected $table          = 'test_rooms';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['name', 'capacity', 'location', 'sort_order', 'is_active'];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }

    /**
     * Active rooms in display order.
     *
     * @return list<array<string, mixed>>
     */
    public function active(): array
    {
        return $this->where('is_active', 1)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();
    }
}
