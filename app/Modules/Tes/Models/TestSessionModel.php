<?php

declare(strict_types=1);

namespace Modules\Tes\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Test sessions (table test_sessions). Owner: work package A (Jadwal).
 *
 * Created with the phase 3 foundation as a shared interface (docs/phase-3-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 *
 * Seats taken = assignments of applicants that are not cancelled or deleted
 * (a cancelled applicant frees the seat without touching the assignment).
 */
class TestSessionModel extends Model
{
    use Auditable;

    protected $table          = 'test_sessions';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'academic_year', 'wave_id', 'test_date', 'starts_at', 'ends_at', 'room_id', 'quota', 'label', 'is_active', 'notes',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }

    /**
     * Sessions with room and counts, in time order (date, start, room order).
     * Filters: academic_year (default: setting Pendaftaran.academicYear), date,
     * date_from, date_to, wave_id ('none' = sessions for every wave), room_id,
     * active (bool). Each row adds room_name, room_location, filled, present,
     * absent, pending (pending + rescheduled) and free (quota - filled, min 0).
     *
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function withStats(array $filters = []): array
    {
        $year    = (string) ($filters['academic_year'] ?? setting('Pendaftaran.academicYear'));
        $builder = $this->db->table('test_sessions s')
            ->select('s.*, r.name AS room_name, r.location AS room_location, r.sort_order AS room_order')
            ->select("COUNT(ap.id) AS filled, COALESCE(SUM(ap.id IS NOT NULL AND a.status = 'present'), 0) AS present, COALESCE(SUM(ap.id IS NOT NULL AND a.status = 'absent'), 0) AS absent, COALESCE(SUM(ap.id IS NOT NULL AND a.status IN ('pending', 'rescheduled')), 0) AS pending", false)
            ->join('test_rooms r', 'r.id = s.room_id')
            ->join('test_assignments a', 'a.session_id = s.id', 'left')
            ->join('applicants ap', "ap.id = a.applicant_id AND ap.deleted_at IS NULL AND ap.status != 'cancelled'", 'left')
            ->where('s.deleted_at', null)
            ->where('s.academic_year', $year)
            ->groupBy('s.id')
            ->orderBy('s.test_date', 'ASC')
            ->orderBy('s.starts_at', 'ASC')
            ->orderBy('r.sort_order', 'ASC')
            ->orderBy('r.name', 'ASC');

        foreach (['date' => 's.test_date', 'date_from' => 's.test_date >=', 'date_to' => 's.test_date <=', 'room_id' => 's.room_id'] as $key => $column) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $builder->where($column, $filters[$key]);
            }
        }

        if (($filters['wave_id'] ?? '') === 'none') {
            $builder->where('s.wave_id', null);
        } elseif (isset($filters['wave_id']) && $filters['wave_id'] !== '') {
            $builder->where('s.wave_id', (int) $filters['wave_id']);
        }

        if (isset($filters['active'])) {
            $builder->where('s.is_active', $filters['active'] ? 1 : 0);
        }

        $rows = $builder->get()->getResultArray();

        foreach ($rows as &$row) {
            foreach (['filled', 'present', 'absent', 'pending'] as $count) {
                $row[$count] = (int) $row[$count];
            }

            $row['free'] = max(0, (int) $row['quota'] - $row['filled']);
        }

        return $rows;
    }

    /**
     * One session with room and counts (see withStats), or null.
     *
     * @return array<string, mixed>|null
     */
    public function findWithStats(int $id): ?array
    {
        $session = $this->find($id);

        if ($session === null) {
            return null;
        }

        foreach ($this->withStats(['academic_year' => $session['academic_year'], 'date' => $session['test_date']]) as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }
}
