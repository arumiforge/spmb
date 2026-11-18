<?php

declare(strict_types=1);

namespace Modules\Tes\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;
use Modules\Pendaftaran\Models\WaveModel;

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

    public const QUOTA_MAX = 999;

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

    /**
     * Seats taken: assignments of applicants that are not cancelled or deleted.
     * Package A (quota may not go below it).
     */
    public function seatsTaken(int $sessionId): int
    {
        return $this->db->table('test_assignments a')
            ->join('applicants ap', 'ap.id = a.applicant_id')
            ->where('a.session_id', $sessionId)
            ->where('ap.deleted_at', null)
            ->where('ap.status !=', 'cancelled')
            ->countAllResults();
    }

    /**
     * Every assignment row of the session, cancelled applicants included.
     * A session with any row is kept (deactivate it instead), so no seat ever
     * points at a deleted session. Package A.
     */
    public function assignmentCount(int $sessionId): int
    {
        return $this->db->table('test_assignments')->where('session_id', $sessionId)->countAllResults();
    }

    /**
     * The first session (not deleted, active or not) in the same room on the
     * same date whose time overlaps [start, end), with room_name, or null.
     *
     * @return array<string, mixed>|null
     */
    public function overlapping(int $roomId, string $date, string $start, string $end, ?int $exceptId = null): ?array
    {
        $builder = $this->db->table('test_sessions s')
            ->select('s.*, r.name AS room_name')
            ->join('test_rooms r', 'r.id = s.room_id')
            ->where('s.deleted_at', null)
            ->where('s.room_id', $roomId)
            ->where('s.test_date', $date)
            ->where('s.starts_at <', self::time($end))
            ->where('s.ends_at >', self::time($start));

        if ($exceptId !== null) {
            $builder->where('s.id !=', $exceptId);
        }

        $row = $builder->orderBy('s.starts_at', 'ASC')->get(1)->getRowArray();

        return $row ?: null;
    }

    /**
     * Sessions (not deleted, any academic year) on a date. A holiday cannot be
     * added on a day that has sessions.
     */
    public function countOnDate(string $date): int
    {
        return $this->where('test_date', $date)->countAllResults();
    }

    /**
     * Checks a session before it is saved (FR-TEST-01, D-059) and returns
     * friendly messages keyed by field; empty = valid. $data holds
     * academic_year, wave_id, test_date, starts_at, ends_at, room_id, quota;
     * $existing is the stored row when editing.
     *
     * Rules: valid date and times, end after start, not on a holiday, room
     * exists (and is active unless unchanged), wave of the same year, quota
     * 1…999 and not below the seats taken, no overlap with another session in
     * the same room on the same date.
     *
     * @param array<string, mixed>      $data
     * @param array<string, mixed>|null $existing
     *
     * @return array<string, string>
     */
    public function validateSession(array $data, ?array $existing = null): array
    {
        $errors = [];
        $date   = (string) ($data['test_date'] ?? '');
        $start  = (string) ($data['starts_at'] ?? '');
        $end    = (string) ($data['ends_at'] ?? '');
        $quota  = $data['quota'] ?? null;

        if (! self::isDate($date)) {
            $errors['test_date'] = lang('TesJadwal.errDate');
        }

        if (! self::isTime($start)) {
            $errors['starts_at'] = lang('TesJadwal.errStart');
        }

        if (! self::isTime($end)) {
            $errors['ends_at'] = lang('TesJadwal.errEnd');
        } elseif (! isset($errors['starts_at']) && self::time($end) <= self::time($start)) {
            $errors['ends_at'] = lang('TesJadwal.errEndBeforeStart');
        }

        if (! is_numeric($quota) || (int) $quota < 1 || (int) $quota > self::QUOTA_MAX || (string) (int) $quota !== (string) $quota) {
            $errors['quota'] = lang('TesJadwal.errQuota', [self::QUOTA_MAX]);
        } elseif ($existing !== null && (int) $quota < ($taken = $this->seatsTaken((int) $existing['id']))) {
            $errors['quota'] = lang('TesJadwal.errQuotaBelowTaken', [$taken]);
        }

        $room = model(TestRoomModel::class)->find((int) ($data['room_id'] ?? 0));

        if ($room === null) {
            $errors['room_id'] = lang('TesJadwal.errRoom');
        } elseif ((int) $room['is_active'] !== 1 && ($existing === null || (int) $existing['room_id'] !== (int) $room['id'])) {
            $errors['room_id'] = lang('TesJadwal.errRoomInactive', [$room['name']]);
        }

        $waveId = $data['wave_id'] ?? null;

        if ($waveId !== null && $waveId !== '') {
            $wave = model(WaveModel::class)->find((int) $waveId);

            if ($wave === null || (string) $wave['academic_year'] !== (string) ($data['academic_year'] ?? '')) {
                $errors['wave_id'] = lang('TesJadwal.errWave');
            }
        }

        if (! isset($errors['test_date'])) {
            $holiday = model(TestHolidayModel::class)->where('date', $date)->first();

            if ($holiday !== null) {
                $errors['test_date'] = lang('TesJadwal.errHoliday', [tanggal_indo($date), $holiday['note'] ?: lang('TesJadwal.holiday')]);
            }
        }

        if ($errors === []) {
            $other = $this->overlapping((int) $room['id'], $date, $start, $end, $existing === null ? null : (int) $existing['id']);

            if ($other !== null) {
                $errors['starts_at'] = lang('TesJadwal.errOverlap', [
                    $room['name'],
                    trim(($other['label'] ?? '') . ' ' . str_replace(':', '.', substr((string) $other['starts_at'], 0, 5)) . '–' . str_replace(':', '.', substr((string) $other['ends_at'], 0, 5))),
                ]);
            }
        }

        return $errors;
    }

    /**
     * "07:30" or "07:30:00" → "07:30:00".
     */
    public static function time(string $value): string
    {
        return strlen($value) === 5 ? $value . ':00' : $value;
    }

    public static function isTime(string $value): bool
    {
        return preg_match('/\A([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?\z/', $value) === 1;
    }

    public static function isDate(string $value): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }
}
