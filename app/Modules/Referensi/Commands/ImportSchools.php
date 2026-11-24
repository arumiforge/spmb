<?php

declare(strict_types=1);

namespace Modules\Referensi\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Referensi\Libraries\AddressBackfill;
use Modules\Referensi\Libraries\BundledRegions;
use Modules\Referensi\Libraries\BundledSchools;
use Modules\Referensi\Libraries\DapodikClient;
use Modules\Referensi\Libraries\DapodikImport;
use Modules\Referensi\Libraries\SchoolImporter;
use RuntimeException;
use Throwable;

/**
 * php spark referensi:import — fills the previous-school list (ref_sekolah)
 * from the bundled national SD/MI list (--bawaan), the public Dapodik portal
 * or a CSV/XLSX/JSON file. With --bawaan it also loads the bundled national
 * region list (ref_wilayah, D-111). Safe to run again: each bundled list is
 * skipped while its version is already loaded.
 */
class ImportSchools extends BaseCommand
{
    protected $group       = 'SPMB';
    protected $name        = 'referensi:import';
    protected $description = 'Imports previous schools: the bundled national SD/MI and region lists (--bawaan), Dapodik for the configured provinces, or a CSV/XLSX/JSON file.';
    protected $usage       = 'referensi:import [options]';
    protected $options     = [
        '--bawaan'    => 'Load the national SD/MI list and region list that ship with SPMB (each skipped when its version is already loaded)',
        '--paksa'     => 'With --bawaan: load again even when this version is already loaded',
        '--provinsi'  => 'Province name(s), comma separated (default: setting Referensi.provinces)',
        '--kabupaten' => 'Only regencies whose name contains this text, e.g. "Kudus"',
        '--jenjang'   => 'Level(s), comma separated (default: setting Referensi.levels)',
        '--delay'     => 'Pause between requests in milliseconds (default: Referensi.importDelayMs)',
        '--file'      => 'Import this CSV/XLSX/JSON file instead of Dapodik (JSON: national SD/MI list, .json.gz allowed)',
        '--dry-run'   => 'Count only; write nothing',
    ];

    public function run(array $params): int
    {
        $dryRun   = CLI::getOption('dry-run') !== null || array_key_exists('dry-run', $params);
        $importer = new SchoolImporter();
        $file     = CLI::getOption('file');

        try {
            if (CLI::getOption('bawaan') !== null || array_key_exists('bawaan', $params)) {
                $this->regionsFromBundle($dryRun);
                $report = $this->fromBundle($importer, $dryRun);

                if ($report === null) {
                    return EXIT_SUCCESS;
                }

                $source = BundledSchools::SOURCE;
            } elseif (is_string($file) && $file !== '') {
                $report = $this->fromFile($importer, $file, $dryRun);
                $source = 'import';
            } else {
                $report = $this->fromDapodik($importer, $dryRun);
                $source = 'dapodik';
            }
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        foreach (array_slice($report['errors'], 0, 50) as $error) {
            CLI::write(sprintf('  baris %d %s: %s', $error['row'], $error['name'], $error['message']), 'yellow');
        }

        CLI::write(sprintf(
            '%s%d baru, %d diperbarui, %d tetap, %d dilewati.',
            $dryRun ? '[uji coba, tidak ada yang disimpan] ' : '',
            $report['created'],
            $report['updated'],
            $report['unchanged'],
            $report['failed'],
        ), 'green');

        if (! $dryRun) {
            service('audit')->log('schools_imported', 'ref_sekolah', null, null, [
                'source'    => $source,
                'created'   => $report['created'],
                'updated'   => $report['updated'],
                'unchanged' => $report['unchanged'],
                'failed'    => $report['failed'],
            ], 'Impor sekolah asal lewat perintah');
        }

        return EXIT_SUCCESS;
    }

    /**
     * Loads the bundled region list (D-111) unless this version is already loaded.
     */
    private function regionsFromBundle(bool $dryRun): void
    {
        $bundle = new BundledRegions();
        $force  = CLI::getOption('paksa') !== null;

        if (! $force && ! $dryRun && $bundle->isImported()) {
            CLI::write('Daftar wilayah versi ' . $bundle->version() . ' sudah dimuat.', 'green');

            return;
        }

        $manifest = $bundle->manifest();
        CLI::write(sprintf('Memuat daftar wilayah versi %s (%s desa/kelurahan) ...', $bundle->version(), number_format((int) ($manifest['villages'] ?? 0), 0, ',', '.')));
        $report = $bundle->import($dryRun);

        CLI::write(sprintf(
            '%sWilayah: %d baru, %d diperbarui, %d tetap, %d dihapus.',
            $dryRun ? '[uji coba, tidak ada yang disimpan] ' : '',
            $report['created'],
            $report['updated'],
            $report['unchanged'],
            $report['removed'],
        ), 'green');

        if (! $dryRun) {
            service('audit')->log('regions_imported', 'ref_wilayah', null, null, ['version' => $bundle->version()] + $report, 'Daftar wilayah dimuat lewat perintah');

            if ((new AddressBackfill())->pendingCount() > 0) {
                CLI::write('Ada alamat pendaftar lama yang belum dicocokkan. Jalankan sekali: php spark referensi:cocokkan-alamat --dry-run, lalu tanpa --dry-run.', 'yellow');
            }
        }
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, failed: int, errors: list<array{row: int, name: string, message: string}>}|null null when already loaded
     */
    private function fromBundle(SchoolImporter $importer, bool $dryRun): ?array
    {
        $bundle    = new BundledSchools();
        $force     = CLI::getOption('paksa') !== null;
        $provinces = CLI::getOption('provinsi');
        $provinces = is_string($provinces) && $provinces !== '' ? self::listOption('provinsi', []) : null;

        if ($provinces === null && ! $force && ! $dryRun && $bundle->isImported()) {
            CLI::write('Daftar SD/MI nasional versi ' . $bundle->version() . ' sudah dimuat. Pakai --paksa untuk memuat ulang.', 'green');

            return null;
        }

        CLI::write(sprintf('Memuat daftar SD/MI nasional versi %s (%s sekolah) ...', $bundle->version(), number_format($bundle->manifest()['schools'], 0, ',', '.')));

        return $bundle->import($provinces, $dryRun, static function (string $province, array $report): void {
            CLI::write(sprintf('  %-28s %6d baru, %6d diperbarui, %6d tetap, %3d dilewati', $province, $report['created'], $report['updated'], $report['unchanged'], $report['failed']));
        }, $importer);
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, failed: int, errors: list<array{row: int, name: string, message: string}>}
     */
    private function fromFile(SchoolImporter $importer, string $file, bool $dryRun): array
    {
        if (! is_file($file)) {
            throw new RuntimeException('File not found: ' . $file);
        }

        CLI::write('Membaca ' . $file . ' ...');

        if (preg_match('/\.(json|ndjson|jsonl)(\.gz)?\z/i', $file) === 1) {
            // A national list has ~170,000 schools; give the CLI room for it.
            ini_set('memory_limit', '2048M');

            return $importer->import($importer->readJson($file), 'import', $dryRun);
        }

        return $importer->import($importer->readFile($file, pathinfo($file, PATHINFO_EXTENSION)), 'import', $dryRun);
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, failed: int, errors: list<array{row: int, name: string, message: string}>}
     */
    private function fromDapodik(SchoolImporter $importer, bool $dryRun): array
    {
        $config    = config('Referensi');
        $provinces = self::listOption('provinsi', (array) setting('Referensi.provinces'));
        $levels    = self::listOption('jenjang', (array) setting('Referensi.levels'));
        $delay     = CLI::getOption('delay');
        $delay     = is_numeric($delay) ? max(0, (int) $delay) : $config->importDelayMs;
        $regency   = CLI::getOption('kabupaten');

        CLI::write('Mengambil data sekolah dari ' . $config->dapodikUrl . ' untuk ' . implode(', ', $provinces) . ' ...');

        if ($config->dapodikToken === '') {
            CLI::write('referensi.dapodikToken is not set; trying without an access key.', 'yellow');
        }

        $import = new DapodikImport(DapodikClient::http($config->dapodikUrl, $config->dapodikToken, $delay), $importer);
        $report = $import->run($provinces, $levels, is_string($regency) ? $regency : null, static fn (string $line) => CLI::write('  ' . $line), $dryRun);

        foreach ($report['skippedLevels'] as $level) {
            CLI::write('Jenjang ' . $level . ' tidak ada di Dapodik; impor dari berkas EMIS dengan --file.', 'yellow');
        }

        return $report;
    }

    /**
     * @param list<mixed> $default
     *
     * @return list<string>
     */
    private static function listOption(string $name, array $default): array
    {
        $value = CLI::getOption($name);
        $list  = is_string($value) && $value !== '' ? explode(',', $value) : array_map('strval', $default);

        return array_values(array_filter(array_map('trim', $list), static fn (string $v): bool => $v !== ''));
    }
}
