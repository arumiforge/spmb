<?php

declare(strict_types=1);

namespace Modules\Tes\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use Modules\Tes\Services\Scheduler;
use Modules\Tes\Services\SchedulerException;

/**
 * Demo allocation (development only, called by the Tes DemoSeeder of package
 * A): places the demo applicants into the demo sessions with
 * Scheduler::allocate(), then moves one applicant once so the change log has
 * an example. Idempotent: a second run places only new applicants and adds
 * no second move.
 */
class DemoAllocationSeeder extends Seeder
{
    public function run(): void
    {
        $scheduler = new Scheduler($this->db);
        $report    = $scheduler->allocate([]);

        if (is_cli() && $report['placed'] > 0) {
            CLI::write('  Penjadwalan tes: ' . $report['placed'] . ' pendaftar dijadwalkan, ' . $report['unplaced'] . ' belum kebagian kursi.');
        }

        $this->demoMove($scheduler);
    }

    private function demoMove(Scheduler $scheduler): void
    {
        if ($this->db->table('test_schedule_changes')->where('kind', 'moved')->countAllResults() > 0) {
            return;
        }

        $staff = $this->db->table('users')->select('id')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $row   = $this->db->table('test_assignments a')
            ->select('a.applicant_id, a.session_id')
            ->join('applicants ap', 'ap.id = a.applicant_id')
            ->where('ap.status <>', 'cancelled')
            ->where('ap.deleted_at', null)
            ->orderBy('a.id', 'ASC')
            ->get(1, 2)
            ->getRowArray();

        if ($staff === null || $row === null) {
            return;
        }

        $applicant = $this->db->table('applicants')->where('id', $row['applicant_id'])->get()->getRowArray();

        foreach ($scheduler->choicesFor($applicant, (int) $row['session_id']) as $session) {
            if ($session['free'] > 0 && $session['test_date'] !== null) {
                try {
                    $scheduler->move((int) $row['applicant_id'], (int) $session['id'], (int) $staff['id'], 'Sakit, minta pindah ke hari lain');
                } catch (SchedulerException) {
                    // Demo data only; skip when the sessions do not allow it.
                }

                return;
            }
        }
    }
}
