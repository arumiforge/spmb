<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Asisten\Libraries\UsageLimiter;
use Modules\Asisten\Models\QuestionLogModel;
use Throwable;

/**
 * php spark asisten:prune — deletes Tanya Asisten questions older than setting('Asisten.retentionDays')
 * (or --hari) and daily visitor usage rows older than yesterday. The app also prunes on the first
 * question of each day; this command is for cron or a manual clean-up.
 *
 * Lives in app/Commands, not in the module, for the same reason as analitik:prune (command discovery
 * would include a module command twice in the test suite).
 */
class AsistenPrune extends BaseCommand
{
    protected $group       = 'SPMB';
    protected $name        = 'asisten:prune';
    protected $description = 'Deletes old Tanya Asisten questions (past the retention) and daily visitor usage rows older than yesterday.';
    protected $usage       = 'asisten:prune [--hari <days>]';
    protected $options     = [
        '--hari' => 'Keep questions for this many days (default: setting Asisten.retentionDays)',
    ];

    public function run(array $params): int
    {
        $option = CLI::getOption('hari') ?? ($params['hari'] ?? null);
        $days   = $option !== null && $option !== true ? (int) $option : (int) setting('Asisten.retentionDays');

        if ($days < 1) {
            CLI::error('The number of days must be 1 or more.');

            return EXIT_USER_INPUT;
        }

        try {
            $questions = model(QuestionLogModel::class)->prune($days);
            $usage     = UsageLimiter::prune();
        } catch (Throwable $e) {
            CLI::error('Pruning failed: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write(sprintf('Kept %d days of questions. Deleted %d questions and %d usage rows.', $days, $questions, $usage), 'green');

        return EXIT_SUCCESS;
    }
}
