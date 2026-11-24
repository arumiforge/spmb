<?php

declare(strict_types=1);

namespace Modules\Referensi\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Referensi\Libraries\AddressBackfill;
use Modules\Referensi\Libraries\RegionInput;

/**
 * php spark referensi:cocokkan-alamat — matches the typed addresses of earlier
 * applicants to the national region list (D-111). Run once after the list
 * arrives; safe to run again. Admin Sekolah can do the same in Sekolah Asal →
 * Alamat pendaftar.
 */
class MatchAddresses extends BaseCommand
{
    protected $group       = 'SPMB';
    protected $name        = 'referensi:cocokkan-alamat';
    protected $description = 'Matches the typed addresses of applicants to the national region list (codes + official names).';
    protected $usage       = 'referensi:cocokkan-alamat [options]';
    protected $options     = [
        '--dry-run'     => 'Show what would change; write nothing',
        '--tanpa-mirip' => 'Only exact names (no guesses for names one or two letters off)',
    ];

    public function run(array $params): int
    {
        $dryRun  = CLI::getOption('dry-run') !== null || array_key_exists('dry-run', $params);
        $similar = CLI::getOption('tanpa-mirip') === null && ! array_key_exists('tanpa-mirip', $params);

        if (! RegionInput::available()) {
            CLI::error('Daftar wilayah belum dimuat. Jalankan dulu: php spark referensi:import --bawaan');

            return EXIT_ERROR;
        }

        $report = (new AddressBackfill())->run($dryRun, $similar);

        foreach ($report['rows'] as $row) {
            $line = sprintf('  %-14s %-30s %s', $row['number'], mb_strimwidth($row['typed'], 0, 30, '…'), $row['result'] === '' ? '(tidak cocok)' : '→ ' . $row['result']);

            if ($row['unmatched'] !== []) {
                $line .= ' [belum cocok: ' . implode(', ', $row['unmatched']) . ']';
            }

            CLI::write($line, $row['status'] === 'full' ? 'green' : ($row['status'] === 'partial' ? 'yellow' : 'red'));
        }

        CLI::write(sprintf(
            '%s%d diperiksa: %d cocok semua, %d sebagian, %d tidak cocok; %d %s.',
            $dryRun ? '[uji coba, tidak ada yang disimpan] ' : '',
            $report['checked'],
            $report['full'],
            $report['partial'],
            $report['none'],
            $report['changed'],
            $dryRun ? 'akan diubah' : 'diubah',
        ), 'green');

        return EXIT_SUCCESS;
    }
}
