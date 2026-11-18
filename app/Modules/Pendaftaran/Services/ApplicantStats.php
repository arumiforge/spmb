<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Services;

use Modules\Pendaftaran\Models\WaveModel;

/**
 * Counts of applicants for the dashboard (one academic year, deleted and
 * cancelled applicants excluded).
 */
class ApplicantStats
{
    /**
     * @return array{
     *     year: string, total: int, today: int, online: int, offline: int, complete: int, duplicates: int,
     *     waves: list<array{name: string, starts_on: string, ends_on: string, quota: int|null, count: int}>, noWave: int
     * }
     */
    public function summary(?string $year = null): array
    {
        $year ??= (string) setting('Pendaftaran.academicYear');
        $base = static fn () => db_connect()->table('applicants')
            ->where('academic_year', $year)
            ->where('deleted_at', null)
            ->where('status !=', 'cancelled');

        $methods = array_column($base()->select('method, COUNT(*) AS n')->groupBy('method')->get()->getResultArray(), 'n', 'method');
        $perWave = [];

        foreach ($base()->select('wave_id, COUNT(*) AS n')->groupBy('wave_id')->get()->getResultArray() as $row) {
            $perWave[$row['wave_id'] === null ? 'none' : (int) $row['wave_id']] = (int) $row['n'];
        }

        $waves = [];

        foreach (model(WaveModel::class)->forYear($year) as $wave) {
            $waves[] = [
                'name'      => (string) $wave['name'],
                'starts_on' => (string) $wave['starts_on'],
                'ends_on'   => (string) $wave['ends_on'],
                'quota'     => $wave['quota'] === null ? null : (int) $wave['quota'],
                'count'     => (int) ($perWave[$wave['id']] ?? 0),
            ];
        }

        return [
            'year'       => $year,
            'total'      => (int) array_sum($methods),
            'today'      => $base()->where('registered_at >=', date('Y-m-d 00:00:00'))->countAllResults(),
            'online'     => (int) ($methods['online'] ?? 0),
            'offline'    => (int) ($methods['offline'] ?? 0),
            'complete'   => $base()->where('document_status', 'complete')->countAllResults(),
            'duplicates' => $base()->where('possible_duplicate', 1)->countAllResults(),
            'waves'      => $waves,
            'noWave'     => $perWave['none'] ?? 0,
        ];
    }
}
