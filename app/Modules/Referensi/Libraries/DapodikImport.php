<?php

declare(strict_types=1);

namespace Modules\Referensi\Libraries;

use Modules\Referensi\Models\SchoolModel;
use RuntimeException;

/**
 * Walks the Dapodik region tree of the configured provinces and upserts the
 * schools of every district through SchoolImporter (source "dapodik").
 * Safe to run again: upsert by NPSN, nothing is deleted.
 */
class DapodikImport
{
    /**
     * Dapodik "jenjang" asked for each of our levels. MI (madrasah) is listed by
     * the Ministry of Religious Affairs (EMIS), not Dapodik: import it from a file.
     */
    public const DAPODIK_LEVELS = ['SD' => 'SD', 'SDLB' => 'SD', 'SLB' => 'SLB'];

    public function __construct(
        private readonly DapodikClient $client,
        private readonly SchoolImporter $importer,
    ) {
    }

    /**
     * @param list<string>                $provinces   Province names as in setting('Referensi.provinces')
     * @param list<string>                $levels      Our levels (SD, MI, ...)
     * @param string|null                 $regencyOnly Only regencies whose name contains this text
     * @param callable(string): void|null $progress    Receives one line per district
     *
     * @return array{created: int, updated: int, unchanged: int, failed: int, districts: int, skippedLevels: list<string>, errors: list<array{row: int, name: string, message: string}>}
     */
    public function run(array $provinces, array $levels, ?string $regencyOnly = null, ?callable $progress = null, bool $dryRun = false): array
    {
        $totals = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0, 'districts' => 0, 'skippedLevels' => [], 'errors' => []];
        $asked  = [];

        foreach ($levels as $level) {
            $level = strtoupper($level);

            if (isset(self::DAPODIK_LEVELS[$level])) {
                $asked[self::DAPODIK_LEVELS[$level]][] = $level;
            } else {
                $totals['skippedLevels'][] = $level;
            }
        }

        foreach ($asked as $jenjang => $wanted) {
            $wanted = array_values(array_unique(array_merge($wanted, $jenjang === 'SD' ? ['SD', 'SDLB'] : [$jenjang])));

            foreach ($this->matchProvinces($provinces, $jenjang) as $province) {
                foreach ($this->client->regencies($province['code'], $jenjang) as $regency) {
                    if ($regencyOnly !== null && $regencyOnly !== '' && mb_stripos($regency['name'], $regencyOnly) === false) {
                        continue;
                    }

                    foreach ($this->client->districts($regency['code'], $jenjang) as $district) {
                        $rows = [];

                        foreach ($this->client->schools($district['code'], $jenjang) as $i => $school) {
                            $row = self::mapSchool($school, $province['name'], $regency['name'], $district['name']);

                            if ($row !== null && in_array($row['level'], $wanted, true)) {
                                $rows[$i + 1] = $row;
                            }
                        }

                        $report = $this->importer->import($rows, 'dapodik', $dryRun);
                        $totals['districts']++;

                        foreach (['created', 'updated', 'unchanged', 'failed'] as $key) {
                            $totals[$key] += $report[$key];
                        }

                        foreach (array_slice($report['errors'], 0, 20) as $error) {
                            $error['name']      = $error['name'] . ' (' . $district['name'] . ')';
                            $totals['errors'][] = $error;
                        }

                        if ($progress !== null) {
                            $progress(sprintf(
                                '%s, %s: %d sekolah (%d baru, %d diperbarui)',
                                $regency['name'],
                                $district['name'],
                                count($rows),
                                $report['created'],
                                $report['updated'],
                            ));
                        }
                    }
                }
            }
        }

        return $totals;
    }

    /**
     * Maps one Dapodik school row to ref_sekolah columns, or null without a name.
     *
     * @param array<string, mixed> $school
     *
     * @return array<string, string|null>|null
     */
    public static function mapSchool(array $school, string $province, string $regency, string $district): ?array
    {
        $name = trim((string) ($school['nama'] ?? $school['nama_sekolah'] ?? ''));

        if ($name === '') {
            return null;
        }

        $text = static fn (string ...$keys): ?string => array_reduce(
            $keys,
            static fn (?string $carry, string $key): ?string => $carry ?? (isset($school[$key]) && trim((string) $school[$key]) !== '' ? trim((string) $school[$key]) : null),
        );

        return SchoolModel::normalise([
            'npsn'      => $text('npsn'),
            'name'      => $name,
            'level'     => strtoupper($text('bentuk_pendidikan', 'jenjang') ?? ''),
            'ownership' => ($own = $text('status_sekolah')) === null ? null : mb_strtolower($own),
            'province'  => $text('provinsi') ?? $province,
            'regency'   => $text('kabupaten') ?? $regency,
            'district'  => $text('kecamatan') ?? $district,
            'village'   => $text('desa_kelurahan'),
            'address'   => $text('alamat_jalan'),
        ]);
    }

    /**
     * Dapodik provinces whose name matches one of ours ("Prov. Jawa Tengah" = "Jawa Tengah").
     *
     * @param list<string> $provinces
     *
     * @return list<array{code: string, name: string}>
     */
    private function matchProvinces(array $provinces, string $jenjang): array
    {
        $wanted = array_map(static fn (string $p): string => mb_strtolower((string) SchoolModel::normalise(['province' => $p])['province']), $provinces);
        $found  = [];

        foreach ($this->client->provinces($jenjang) as $province) {
            $name = (string) SchoolModel::normalise(['province' => $province['name']])['province'];

            if (in_array(mb_strtolower($name), $wanted, true)) {
                $found[] = ['code' => $province['code'], 'name' => $name];
            }
        }

        if ($found === []) {
            throw new RuntimeException('None of the provinces (' . implode(', ', $provinces) . ') was found in Dapodik.');
        }

        return $found;
    }
}
