<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestScheduleChangeModel;
use Modules\Tes\Services\Scheduler;
use Modules\Tes\Services\SchedulerException;

/**
 * One applicant's test seat: give a seat or move to another session
 * (tes.kehadiran), and take them out of the schedule (tes.atur). Writes go
 * through Services\Scheduler, which logs and audits every change.
 */
class JadwalPendaftar extends BaseController
{
    /**
     * GET /admin/pendaftar/<id>/tes.
     */
    public function edit(int $id): string
    {
        $applicant = $this->applicant($id);
        $current   = (new TestSchedule())->forApplicant($id);
        $user      = auth()->user();
        $canAtur   = $user->can('tes.atur');
        $choices   = $applicant['status'] === 'cancelled'
            ? []
            : (new Scheduler())->choicesFor($applicant, $current['session_id'] ?? null, $current !== null && $canAtur);

        return view('Modules\Tes\Views\jadwal_pendaftar\edit', [
            'title'       => lang($current === null ? 'TesPenjadwalan.seatTitleAssign' : 'TesPenjadwalan.seatTitleMove'),
            'subtitle'    => $applicant['full_name'] . ' · ' . $applicant['registration_number'],
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Data Pendaftar', 'url' => 'admin/pendaftar'],
                ['label' => 'Detail', 'url' => 'admin/pendaftar/' . $id],
                ['label' => lang('Tes.schedule')],
            ],
            'applicant' => $applicant,
            'current'   => $current,
            'choices'   => $choices,
            'changes'   => model(TestScheduleChangeModel::class)->latestForApplicant($id),
            'canRemove' => $canAtur && $current !== null,
        ]);
    }

    /**
     * POST /admin/pendaftar/<id>/tes — sesi, alasan, lebih_kuota.
     */
    public function update(int $id): RedirectResponse
    {
        $applicant = $this->applicant($id);
        $sessionId = (int) $this->request->getPost('sesi');
        $reason    = (string) $this->request->getPost('alasan');
        $overQuota = $this->request->getPost('lebih_kuota') === '1';
        $staffId   = (int) auth()->id();
        $back      = redirect()->route('tes.jadwal-pendaftar', [$id])->withInput();

        if ($sessionId <= 0) {
            return $back->with('errors', ['sesi' => lang('TesPenjadwalan.err.sessionRequired')]);
        }

        $scheduler = new Scheduler();
        $isMove    = (new TestSchedule())->forApplicant($id) !== null;

        try {
            $row = $isMove
                ? $scheduler->move($id, $sessionId, $staffId, $reason, $overQuota)
                : $scheduler->assign($id, $sessionId, $staffId, $reason, $overQuota);
        } catch (SchedulerException $e) {
            return $back->with('error', $e->getMessage());
        }

        $label = TestSchedule::sessionLabel($row);

        return redirect()->to(site_url('admin/pendaftar/' . $id) . '#jadwal-tes')
            ->with('message', lang($isMove ? 'TesPenjadwalan.moved' : 'TesPenjadwalan.assigned', [$applicant['registration_number'], $label]));
    }

    /**
     * POST /admin/pendaftar/<id>/tes/hapus — alasan (tes.atur).
     */
    public function delete(int $id): RedirectResponse
    {
        $applicant = $this->applicant($id);

        try {
            (new Scheduler())->remove($id, (int) auth()->id(), (string) $this->request->getPost('alasan'));
        } catch (SchedulerException $e) {
            return redirect()->route('tes.jadwal-pendaftar', [$id])->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('admin/pendaftar/' . $id) . '#jadwal-tes')
            ->with('message', lang('TesPenjadwalan.removed', [$applicant['registration_number']]));
    }

    /**
     * @return array<string, mixed>
     */
    private function applicant(int $id): array
    {
        $applicant = model(ApplicantModel::class)->find($id);

        if ($applicant === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $applicant;
    }
}
