<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Analitik\Libraries\VisitRecorder;
use Throwable;

/**
 * php spark analitik:prune — deletes per-page visitor rows older than
 * setting('Analitik.retentionDays') (or --hari) and daily visitor hashes older
 * than yesterday. Daily totals are kept. The app also prunes on the first page
 * view of each day; this command is for cron or a manual clean-up.
 *
 * Lives in app/Commands, not in the module: command discovery also tries the
 * name App\Modules\...\Commands\X for module commands, and Composer's App\
 * mapping then includes an already loaded module command a second time
 * (fatal "Cannot redeclare" when discovery runs again, as in the test suite).
 */
class AnalitikPrune extends BaseCommand
{
    protected $group       = 'SPMB';
    protected $name        = 'analitik:prune';
    protected $description = 'Deletes old visitor statistics rows (per-page rows past the retention, visitor hashes older than yesterday).';
    protected $usage       = 'analitik:prune [--hari <days>]';
    protected $options     = [
        '--hari' => 'Keep per-page rows for this many days (default: setting Analitik.retentionDays)',
    ];

    public function run(array $params): int
    {
        $option = CLI::getOption('hari') ?? ($params['hari'] ?? null);
        $days   = $option !== null && $option !== true ? (int) $option : (int) setting('Analitik.retentionDays');

        if ($days < 1) {
            CLI::error('The number of days must be 1 or more.');

            return EXIT_USER_INPUT;
        }

        try {
            $deleted = (new VisitRecorder())->prune($days);
        } catch (Throwable $e) {
            CLI::error('Pruning failed: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write(sprintf('Kept %d days of per-page rows. Deleted %d per-page rows and %d visitor hashes.', $days, $deleted['pages'], $deleted['visitors']), 'green');

        return EXIT_SUCCESS;
    }
}
