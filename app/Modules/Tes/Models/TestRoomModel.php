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

    /**
     * All rooms (active and inactive) in display order, each with
     * `sessions`: the number of sessions (not deleted) held in it. Package A.
     *
     * @return list<array<string, mixed>>
     */
    public function withSessionCounts(): array
    {
        $rows = $this->db->table('test_rooms r')
            ->select('r.*, COUNT(s.id) AS sessions')
            ->join('test_sessions s', 's.room_id = r.id AND s.deleted_at IS NULL', 'left')
            ->where('r.deleted_at', null)
            ->groupBy('r.id')
            ->orderBy('r.sort_order', 'ASC')
            ->orderBy('r.name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['sessions'] = (int) $row['sessions'];
        }

        return $rows;
    }

    /**
     * Sessions (not deleted) held in the room. A room in use cannot be deleted.
     */
    public function sessionCount(int $roomId): int
    {
        return $this->db->table('test_sessions')->where('room_id', $roomId)->where('deleted_at', null)->countAllResults();
    }

    /**
     * Another room (not deleted) with the same name, case-insensitive, or null.
     *
     * @return array<string, mixed>|null
     */
    public function findByName(string $name, ?int $exceptId = null): ?array
    {
        $builder = $this->where('LOWER(name)', mb_strtolower(trim($name)));

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->first();
    }
}
