<?php

declare(strict_types=1);

namespace Modules\Referensi\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use Modules\Referensi\Libraries\BundledSchools;

/**
 * Loads the national SD/MI list that ships with SPMB (D-058) the first time
 * spmb:install runs, and again only when an update brings a newer list.
 * Takes about a minute for ~177,000 schools; a repeat run is skipped.
 */
class InstallSeeder extends Seeder
{
    public function run(): void
    {
        $bundle = new BundledSchools();

        if ($bundle->isImported()) {
            self::say('  Daftar SD/MI nasional versi ' . $bundle->version() . ' sudah dimuat.');

            return;
        }

        self::say('  Memuat daftar SD/MI nasional (' . number_format($bundle->manifest()['schools'], 0, ',', '.') . ' sekolah, sekitar satu menit) ...');
        $report = $bundle->import();

        service('audit')->log('schools_imported', 'ref_sekolah', null, null, [
            'source'    => BundledSchools::SOURCE,
            'version'   => $bundle->version(),
            'created'   => $report['created'],
            'updated'   => $report['updated'],
            'unchanged' => $report['unchanged'],
            'failed'    => $report['failed'],
        ], 'Daftar SD/MI nasional dimuat saat instalasi');

        self::say(sprintf('  %d baru, %d diperbarui, %d tetap, %d dilewati.', $report['created'], $report['updated'], $report['unchanged'], $report['failed']));
    }

    private static function say(string $line): void
    {
        if (is_cli() && ENVIRONMENT !== 'testing') {
            CLI::write($line);
        }
    }
}
