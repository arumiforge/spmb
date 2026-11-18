<?php

declare(strict_types=1);

namespace Modules\Tes\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Days without tests (table test_holidays). Owner: work package A (Jadwal).
 *
 * Created with the phase 3 foundation as a shared interface (docs/phase-3-plan.md).
 */
class TestHolidayModel extends Model
{
    use Auditable;

    protected $table          = 'test_holidays';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['date', 'note'];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }

    public function isHoliday(string $date): bool
    {
        return $this->where('date', $date)->countAllResults() > 0;
    }

    /**
     * Holidays between two dates (inclusive), keyed by date.
     *
     * @return array<string, array<string, mixed>>
     */
    public function between(string $from, string $to): array
    {
        $rows = $this->where('date >=', $from)->where('date <=', $to)->orderBy('date', 'ASC')->findAll();

        return array_column($rows, null, 'date');
    }

    /**
     * Holidays from a date on, oldest first (the calendar's list). Package A.
     *
     * @return list<array<string, mixed>>
     */
    public function upcoming(string $from, int $limit = 50): array
    {
        return $this->where('date >=', $from)->orderBy('date', 'ASC')->findAll($limit);
    }
}
