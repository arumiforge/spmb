<?php

declare(strict_types=1);

namespace Modules\Referensi\Libraries;

use CodeIgniter\Database\BaseConnection;
use Modules\Referensi\Models\RegionModel;
use RuntimeException;
use Throwable;

/**
 * The national region list that ships with SPMB (D-111): provinces, regencies
 * and cities, districts and villages from cahyadsn/wilayah (MIT, Kepmendagri
 * 300.2.2-2138/2025), converted by scripts/wilayah.mjs into one gzipped JSON
 * file of [code, name] pairs in Database/Data/wilayah/, described by manifest.json.
 *
 * Loaded by the installer (Referensi InstallSeeder) and by
 * `php spark referensi:import --bawaan`. The version is remembered in the
 * setting Referensi.regionsVersion, so a repeat run only reads the small
 * manifest and stops until a newer list ships with an update.
 *
 * A load replaces the table contents in one transaction: new codes are added,
 * changed names updated and codes no longer in the list removed. Applicants
 * keep their codes and names either way.
 */
class BundledRegions
{
    private const BATCH = 1000;

    private readonly string $dir;

    /**
     * @var array{version: string, file: string, sha256?: string, regions: int, provinces: int, regencies: int, districts: int, villages: int}|null
     */
    private ?array $manifest = null;

    public function __construct(?string $dir = null)
    {
        $this->dir = rtrim($dir ?? dirname(__DIR__) . '/Database/Data/wilayah', '/');
    }

    /**
     * @return array{version: string, file: string, sha256?: string, regions: int, provinces: int, regencies: int, districts: int, villages: int}
     *
     * @throws RuntimeException when the manifest is missing or unreadable
     */
    public function manifest(): array
    {
        if ($this->manifest === null) {
            $path = $this->dir . '/manifest.json';
            $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

            if (! is_array($data) || ! isset($data['version'], $data['file']) || ! is_string($data['file']) || preg_match('/\A[a-z0-9.-]+\z/', $data['file']) !== 1) {
                throw new RuntimeException('The bundled region list is missing or damaged: ' . $path);
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
     * True when this version of the list was already loaded into ref_wilayah.
     */
    public function isImported(): bool
    {
        return (string) setting('Referensi.regionsVersion') === $this->version();
    }

    /**
     * The rows of the bundled file, checked: code shape, name length, parent before child.
     *
     * @return list<array{code: string, parent_code: string|null, level: int, name: string}>
     *
     * @throws RuntimeException when the file is missing, damaged or does not match the manifest
     */
    public function rows(): array
    {
        $manifest = $this->manifest();
        $path     = $this->dir . '/' . $manifest['file'];
        $raw      = is_file($path) ? file_get_contents($path) : false;

        if ($raw === false) {
            throw new RuntimeException('The bundled region file is missing: ' . $path);
        }

        if (isset($manifest['sha256']) && hash('sha256', $raw) !== $manifest['sha256']) {
            throw new RuntimeException('The bundled region file does not match its manifest: ' . $path);
        }

        $json = str_ends_with($path, '.gz') ? @gzdecode($raw) : $raw;
        $data = is_string($json) ? json_decode($json, true) : null;
        unset($raw, $json);

        if (! is_array($data)) {
            throw new RuntimeException('The bundled region file is damaged: ' . $path);
        }

        $rows = [];
        $seen = [];

        foreach ($data as $i => $pair) {
            $code  = is_array($pair) && isset($pair[0], $pair[1]) && is_string($pair[0]) && is_string($pair[1]) ? $pair[0] : '';
            $level = RegionModel::levelOf($code);
            $name  = $level === null ? '' : trim((string) preg_replace('/\s+/u', ' ', $pair[1]));

            if ($level === null || $name === '' || mb_strlen($name) > 100) {
                throw new RuntimeException('The bundled region file has a bad row at position ' . $i . '.');
            }

            $parent = RegionModel::parentOf($code);

            if ($parent !== null && ! isset($seen[$parent])) {
                throw new RuntimeException('Region ' . $code . ' comes before its parent ' . $parent . '.');
            }

            $seen[$code] = true;
            $rows[]      = ['code' => $code, 'parent_code' => $parent, 'level' => $level, 'name' => $name];
        }

        if (isset($manifest['regions']) && count($rows) !== (int) $manifest['regions']) {
            throw new RuntimeException('The bundled region file holds ' . count($rows) . ' regions, the manifest says ' . $manifest['regions'] . '.');
        }

        return $rows;
    }

    /**
     * Loads the list into ref_wilayah (replacing what is there) and records the version.
     * With $dryRun only counts what would change.
     *
     * @return array{created: int, updated: int, unchanged: int, removed: int}
     */
    public function import(bool $dryRun = false, ?BaseConnection $db = null): array
    {
        if (self::memoryLimitBytes() < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

        $db ??= db_connect();
        $rows    = $this->rows();
        $table   = $db->table('ref_wilayah');
        $current = [];

        foreach ($db->table('ref_wilayah')->select('code, name')->get()->getResultArray() as $row) {
            $current[(string) $row['code']] = (string) $row['name'];
        }

        $report = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'removed' => 0];
        $write  = [];

        foreach ($rows as $row) {
            if (! array_key_exists($row['code'], $current)) {
                $report['created']++;
                $write[] = $row;
            } elseif ($current[$row['code']] !== $row['name']) {
                $report['updated']++;
                $write[] = $row;
            } else {
                $report['unchanged']++;
            }

            unset($current[$row['code']]);
        }

        $report['removed'] = count($current);

        if ($dryRun) {
            return $report;
        }

        $db->transBegin();

        try {
            foreach (array_chunk($write, self::BATCH) as $chunk) {
                $table->upsertBatch($chunk);
            }

            foreach (array_chunk(array_keys($current), self::BATCH) as $chunk) {
                $db->table('ref_wilayah')->whereIn('code', $chunk)->delete();
            }
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }

        $db->transCommit();
        setting('Referensi.regionsVersion', $this->version());

        return $report;
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
