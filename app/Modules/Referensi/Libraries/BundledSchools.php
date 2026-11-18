<?php

declare(strict_types=1);

namespace Modules\Referensi\Libraries;

use Modules\Referensi\Models\SchoolModel;
use RuntimeException;

/**
 * The national SD/MI list that ships with SPMB (D-058): one gzipped JSON file
 * per province in Database/Data/sekolah-sd-mi/, described by manifest.json.
 * Public data (NPSN, name, level, status, village, district, regency,
 * province); no personal data.
 *
 * Loaded by the installer (Referensi InstallSeeder) and by
 * `php spark referensi:import --bawaan`. The list version is remembered in
 * the setting Referensi.bundledVersion, so a second run is skipped until a
 * newer list ships with an update.
 */
class BundledSchools
{
    public const SOURCE = 'nasional';

    private readonly string $dir;

    /**
     * @var array{version: string, schools: int, files: list<array{file: string, province: string, schools: int, sd: int, mi: int}>}|null
     */
    private ?array $manifest = null;

    public function __construct(?string $dir = null)
    {
        $this->dir = rtrim($dir ?? dirname(__DIR__) . '/Database/Data/sekolah-sd-mi', '/');
    }

    /**
     * @return array{version: string, schools: int, files: list<array{file: string, province: string, schools: int, sd: int, mi: int}>}
     *
     * @throws RuntimeException when the manifest is missing or unreadable
     */
    public function manifest(): array
    {
        if ($this->manifest === null) {
            $path = $this->dir . '/manifest.json';
            $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

            if (! is_array($data) || ! isset($data['version'], $data['files']) || ! is_array($data['files'])) {
                throw new RuntimeException('The bundled school list is missing or damaged: ' . $path);
            }

            $this->manifest = $data;
        }

        return $this->manifest;
    }

    public function version(): string
    {
        return (string) $this->manifest()['version'];
    }

    /**
     * True when this version of the list was already loaded into ref_sekolah.
     */
    public function isImported(): bool
    {
        return (string) setting('Referensi.bundledVersion') === $this->version();
    }

    /**
     * Files to load, optionally only for some provinces ("Jawa Tengah", "jawa-tengah",
     * "Prov. Jawa Tengah" all match).
     *
     * @param list<string>|null $provinces
     *
     * @return list<array{file: string, path: string, province: string, schools: int}>
     *
     * @throws RuntimeException when a requested province is not in the list
     */
    public function files(?array $provinces = null): array
    {
        $wanted = $provinces === null ? null : array_map(self::provinceKey(...), $provinces);
        $found  = [];
        $files  = [];

        foreach ($this->manifest()['files'] as $entry) {
            $key = self::provinceKey($entry['province']);

            if ($wanted !== null && ! in_array($key, $wanted, true)) {
                continue;
            }

            $found[] = $key;
            $files[] = [
                'file'     => $entry['file'],
                'path'     => $this->dir . '/' . $entry['file'],
                'province' => $entry['province'],
                'schools'  => (int) $entry['schools'],
            ];
        }

        if ($wanted !== null && ($missing = array_diff($wanted, $found)) !== []) {
            throw new RuntimeException('Province not in the bundled list: ' . implode(', ', $missing));
        }

        return $files;
    }

    /**
     * Loads the list (all provinces, or only $provinces) with the usual upsert
     * rules (SchoolImporter): by NPSN, never deletes, never changes is_active.
     * A full, real run records the version in Referensi.bundledVersion.
     *
     * @param list<string>|null                                                     $provinces
     * @param (callable(string $province, array<string, mixed> $report): void)|null $progress  called after each file
     *
     * @return array{created: int, updated: int, unchanged: int, failed: int, errors: list<array{row: int, name: string, message: string}>}
     */
    public function import(?array $provinces = null, bool $dryRun = false, ?callable $progress = null, ?SchoolImporter $importer = null): array
    {
        // The installer and the command may load all 38 files in one process.
        if (self::memoryLimitBytes() < 1024 * 1024 * 1024) {
            ini_set('memory_limit', '1024M');
        }

        $importer ??= new SchoolImporter();
        $total = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0, 'errors' => []];

        foreach ($this->files($provinces) as $file) {
            $report = $importer->import($importer->readJson($file['path']), self::SOURCE, $dryRun);

            foreach (['created', 'updated', 'unchanged', 'failed'] as $key) {
                $total[$key] += $report[$key];
            }

            foreach ($report['errors'] as $error) {
                if (count($total['errors']) < SchoolImporter::MAX_ERRORS) {
                    $total['errors'][] = ['row' => $error['row'], 'name' => $error['name'], 'message' => $file['province'] . ': ' . $error['message']];
                }
            }

            if ($progress !== null) {
                $progress($file['province'], $report);
            }
        }

        if (! $dryRun && $provinces === null) {
            setting('Referensi.bundledVersion', $this->version());
        }

        return $total;
    }

    private static function provinceKey(string $name): string
    {
        $name = (string) (SchoolModel::normalise(['province' => $name])['province'] ?? $name);

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', str_replace('.', '', mb_strtolower($name))), '-');
    }

    private static function memoryLimitBytes(): int
    {
        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '' || $limit === '-1') {
            return PHP_INT_MAX;
        }

        $value = (int) $limit;

        return match (strtolower(substr($limit, -1))) {
            'g'     => $value * 1024 ** 3,
            'm'     => $value * 1024 ** 2,
            'k'     => $value * 1024,
            default => $value,
        };
    }
}
