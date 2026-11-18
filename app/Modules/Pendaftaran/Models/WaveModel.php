<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Registration waves (table waves). Owner: work package B (Pendaftaran).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 *
 * Dates are inclusive: a wave is open from starts_on 00:00 to ends_on 23:59
 * (school time zone). Waves of one academic year never overlap (FR-REG-04).
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

    /**
     * The active wave of the configured academic year that covers $date
     * (default: today), or null when registration is closed on that day.
     *
     * @return array<string, mixed>|null
     */
    public function current(?DateTimeInterface $date = null): ?array
    {
        $day = ($date ?? new DateTimeImmutable('now'))->format('Y-m-d');

        $row = $this->builder()
            ->where('deleted_at', null)
            ->where('academic_year', (string) setting('Pendaftaran.academicYear'))
            ->where('is_active', 1)
            ->where('starts_on <=', $day)
            ->where('ends_on >=', $day)
            ->orderBy('starts_on', 'ASC')
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Waves of one academic year (active and inactive), oldest first.
     *
     * @return list<array<string, mixed>>
     */
    public function forYear(string $academicYear): array
    {
        return $this->builder()
            ->where('deleted_at', null)
            ->where('academic_year', $academicYear)
            ->orderBy('starts_on', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * The first wave of the same academic year whose dates overlap
     * [$startsOn, $endsOn], or null. Inactive waves count too: switching a
     * wave on later must not create an overlap.
     *
     * @return array<string, mixed>|null
     */
    public function overlapping(string $academicYear, string $startsOn, string $endsOn, ?int $exceptId = null): ?array
    {
        $builder = $this->builder()
            ->where('deleted_at', null)
            ->where('academic_year', $academicYear)
            ->where('starts_on <=', $endsOn)
            ->where('ends_on >=', $startsOn);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        $row = $builder->orderBy('starts_on', 'ASC')->get(1)->getRowArray();

        return $row ?: null;
    }

    /**
     * Applicants registered in a wave (not deleted, not cancelled).
     */
    public function applicantCount(int $waveId): int
    {
        return $this->db->table('applicants')
            ->where('wave_id', $waveId)
            ->where('deleted_at', null)
            ->where('status !=', 'cancelled')
            ->countAllResults();
    }

    /**
     * True when the wave has a quota and it has been reached.
     *
     * @param array<string, mixed> $wave
     */
    public function isFull(array $wave): bool
    {
        if ($wave['quota'] === null || $wave['quota'] === '') {
            return false;
        }

        return $this->applicantCount((int) $wave['id']) >= (int) $wave['quota'];
    }

    /**
     * Distinct academic years that have waves, newest first.
     *
     * @return list<string>
     */
    public function years(): array
    {
        $rows = $this->builder()->select('academic_year')->distinct()
            ->where('deleted_at', null)->orderBy('academic_year', 'DESC')->get()->getResultArray();

        return array_column($rows, 'academic_year');
    }
}
