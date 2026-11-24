<?php

declare(strict_types=1);

namespace Modules\Statistik\Libraries;

use Modules\Pendaftaran\Models\WaveModel;

/**
 * The filters of the Statistik page, read from the query string:
 * tahun (academic year), gelombang (wave id or "tanpa"), status ("aktif" =
 * everyone except cancelled, the default; "semua"; or one status code), plus
 * the view switches kab/kec (location drill-down), sekolah (origin school
 * grouping), waktu (per day or week) and penghasilan (father's or mother's
 * income in the cross-tabs).
 */
final class StatistikFilter
{
    public const STATUSES       = ['aktif', 'semua', 'registered', 'verified', 'cancelled'];
    public const SCHOOL_MODES   = ['kecamatan', 'kabupaten', 'nama'];
    public const TIME_MODES     = ['hari', 'minggu'];
    public const INCOME_PARENTS = ['ayah', 'ibu'];

    /**
     * @param list<string>       $years Academic years to choose from, newest first
     * @param array<int, string> $waves Waves of the chosen year, id => name, oldest first
     */
    private function __construct(
        public readonly string $year,
        public readonly int|string|null $wave,
        public readonly string $status,
        public readonly ?string $regency,
        public readonly ?string $district,
        public readonly string $schoolMode,
        public readonly ?string $timeMode,
        public readonly string $incomeParent,
        public readonly array $years,
        public readonly array $waves,
    ) {
    }

    /**
     * @param array<string, mixed> $get Query string values
     */
    public static function fromQuery(array $get): self
    {
        $current = (string) setting('Pendaftaran.academicYear');
        $years   = self::years($current);
        $year    = is_string($get['tahun'] ?? null) && in_array($get['tahun'], $years, true) ? $get['tahun'] : $current;

        $waves = [];

        foreach (model(WaveModel::class)->forYear($year) as $row) {
            $waves[(int) $row['id']] = (string) $row['name'];
        }

        $waveIn = $get['gelombang'] ?? '';
        $wave   = null;

        if ($waveIn === 'tanpa') {
            $wave = 'tanpa';
        } elseif (is_string($waveIn) && ctype_digit($waveIn) && isset($waves[(int) $waveIn])) {
            $wave = (int) $waveIn;
        }

        $status = is_string($get['status'] ?? null) && in_array($get['status'], self::STATUSES, true) ? $get['status'] : 'aktif';
        $text   = static fn (string $key): ?string => is_string($get[$key] ?? null) && $get[$key] !== '' && strlen($get[$key]) <= 400 ? $get[$key] : null;

        return new self(
            year: $year,
            wave: $wave,
            status: $status,
            regency: $text('kab'),
            district: $text('kab') === null ? null : $text('kec'),
            schoolMode: in_array($get['sekolah'] ?? null, self::SCHOOL_MODES, true) ? $get['sekolah'] : 'kecamatan',
            timeMode: in_array($get['waktu'] ?? null, self::TIME_MODES, true) ? $get['waktu'] : null,
            incomeParent: in_array($get['penghasilan'] ?? null, self::INCOME_PARENTS, true) ? $get['penghasilan'] : 'ayah',
            years: $years,
            waves: $waves,
        );
    }

    /**
     * Academic years that have applicants or waves, plus the current one, newest first.
     *
     * @return list<string>
     */
    private static function years(string $current): array
    {
        $rows  = db_connect()->table('applicants')->select('academic_year')->distinct()->where('deleted_at', null)->get()->getResultArray();
        $years = array_unique([$current, ...array_column($rows, 'academic_year'), ...model(WaveModel::class)->years()]);
        $years = array_values(array_filter(array_map('strval', $years), static fn (string $y): bool => $y !== ''));
        rsort($years);

        return $years;
    }

    /**
     * Query string for links: the filters plus the view switches, with $changes applied
     * (null removes a key). Default values are left out so addresses stay short.
     *
     * @param array<string, string|null> $changes
     */
    public function query(array $changes = []): string
    {
        $values = array_merge([
            'tahun'       => $this->year === (string) setting('Pendaftaran.academicYear') ? null : $this->year,
            'gelombang'   => $this->wave === null ? null : (string) $this->wave,
            'status'      => $this->status === 'aktif' ? null : $this->status,
            'kab'         => $this->regency,
            'kec'         => $this->district,
            'sekolah'     => $this->schoolMode === 'kecamatan' ? null : $this->schoolMode,
            'waktu'       => $this->timeMode,
            'penghasilan' => $this->incomeParent === 'ayah' ? null : $this->incomeParent,
        ], $changes);

        $values = array_filter($values, static fn (?string $v): bool => $v !== null && $v !== '');

        return $values === [] ? '' : '?' . http_build_query($values);
    }

    public function isFiltered(): bool
    {
        return $this->year !== (string) setting('Pendaftaran.academicYear') || $this->wave !== null || $this->status !== 'aktif';
    }
}
