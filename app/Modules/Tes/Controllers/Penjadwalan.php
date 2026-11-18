<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Tes\Services\Scheduler;

/**
 * Automatic allocation (FR-TEST-02, D-060): counts, a form (wave, start
 * date), a dry-run preview, and the run itself after confirmation.
 * GET /admin/tes/penjadwalan[?gelombang=&mulai=&lihat=1], POST to run (tes.atur).
 */
class Penjadwalan extends BaseController
{
    public function index(): string
    {
        $year      = (string) setting('Pendaftaran.academicYear');
        $waves     = model(WaveModel::class)->forYear($year);
        $scheduler = new Scheduler();
        $waveId    = $this->waveInput((string) $this->request->getGet('gelombang'), $waves);
        $mulai     = (string) $this->request->getGet('mulai');
        $fromDate  = Scheduler::validDate($mulai) ?? Scheduler::tomorrow();
        $preview   = null;
        $dateError = $mulai !== '' && Scheduler::validDate($mulai) === null;

        if ($this->request->getGet('lihat') === '1' && ! $dateError) {
            $preview = $scheduler->allocate(['academic_year' => $year, 'wave_id' => $waveId, 'from_date' => $fromDate], true);
        }

        $sessions = $scheduler->openSessions($year, $waveId, $fromDate);
        $counts   = $scheduler->eligibleCounts($year);

        return view('Modules\Tes\Views\penjadwalan\index', [
            'title'       => lang('TesPenjadwalan.allocTitle'),
            'subtitle'    => lang('TesPenjadwalan.allocIntro', [$year]),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Tes.breadcrumb')], ['label' => lang('TesPenjadwalan.allocTitle')]],
            'waves'       => array_column($waves, 'name', 'id'),
            'counts'      => $counts,
            'waiting'     => $waveId === null ? array_sum($counts) : ($counts[(string) $waveId] ?? 0),
            'freeSeats'   => array_sum(array_column($sessions, 'free')),
            'openCount'   => count(array_filter($sessions, static fn (array $s): bool => $s['free'] > 0)),
            'waveId'      => $waveId,
            'fromDate'    => $fromDate,
            'dateError'   => $dateError,
            'preview'     => $preview,
        ]);
    }

    public function run(): RedirectResponse
    {
        $year   = (string) setting('Pendaftaran.academicYear');
        $waves  = model(WaveModel::class)->forYear($year);
        $waveId = $this->waveInput((string) $this->request->getPost('gelombang'), $waves);
        $from   = Scheduler::validDate((string) $this->request->getPost('mulai'));

        if ($from === null) {
            return redirect()->route('tes.penjadwalan')->with('error', lang('TesPenjadwalan.err.dateInvalid'));
        }

        $report = (new Scheduler())->allocate([
            'academic_year' => $year,
            'wave_id'       => $waveId,
            'from_date'     => $from,
            'staff_id'      => (int) auth()->id(),
        ]);

        if ($report['placed'] === 0) {
            return redirect()->route('tes.penjadwalan')->with('warning', lang('TesPenjadwalan.allocNothing'));
        }

        $message = $report['unplaced'] > 0
            ? lang('TesPenjadwalan.allocDoneUnplaced', [$report['placed'], $report['unplaced']])
            : lang('TesPenjadwalan.allocDone', [$report['placed']]);

        return redirect()->route('tes.penjadwalan')->with($report['unplaced'] > 0 ? 'warning' : 'message', $message);
    }

    /**
     * @param list<array<string, mixed>> $waves
     */
    private function waveInput(string $value, array $waves): ?int
    {
        return in_array($value, array_map('strval', array_column($waves, 'id')), true) ? (int) $value : null;
    }
}
