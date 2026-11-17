<?php

declare(strict_types=1);

namespace Modules\Referensi\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Referensi\Libraries\DapodikClient;
use Modules\Referensi\Libraries\DapodikImport;
use Modules\Referensi\Libraries\SchoolImporter;
use RuntimeException;
use Throwable;

/**
 * php spark referensi:import — fills the previous-school list (ref_sekolah)
 * from the public Dapodik portal or from a CSV/XLSX file. Safe to run again.
 */
class ImportSchools extends BaseCommand
{
    protected $group       = 'SPMB';
    protected $name        = 'referensi:import';
    protected $description = 'Imports previous schools (SD, ...) from Dapodik for the configured provinces, or from a CSV/XLSX file.';
    protected $usage       = 'referensi:import [options]';
    protected $options     = [
        '--provinsi'  => 'Province name(s), comma separated (default: setting Referensi.provinces)',
        '--kabupaten' => 'Only regencies whose name contains this text, e.g. "Kudus"',
        '--jenjang'   => 'Level(s), comma separated (default: setting Referensi.levels)',
        '--delay'     => 'Pause between requests in milliseconds (default: Referensi.importDelayMs)',
        '--file'      => 'Import this CSV/XLSX file instead of Dapodik (e.g. MI from EMIS)',
        '--dry-run'   => 'Count only; write nothing',
    ];

    public function run(array $params): int
    {
        $dryRun   = CLI::getOption('dry-run') !== null || array_key_exists('dry-run', $params);
        $importer = new SchoolImporter();
        $file     = CLI::getOption('file');

        try {
            if (is_string($file) && $file !== '') {
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
     * @return array{created: int, updated: int, unchanged: int, failed: int, errors: list<array{row: int, name: string, message: string}>}
     */
    private function fromFile(SchoolImporter $importer, string $file, bool $dryRun): array
    {
        if (! is_file($file)) {
            throw new RuntimeException('File not found: ' . $file);
        }

        CLI::write('Membaca ' . $file . ' ...');

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
