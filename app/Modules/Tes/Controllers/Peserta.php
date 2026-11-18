<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Models\TestSessionModel;

/**
 * Participants list (tes.kehadiran): applicants of the academic year with
 * their session, seat and test status, including "Belum dijadwalkan".
 * Cancelled applicants are left out. Filters in the query string:
 * q, tanggal, sesi, status, gelombang. 25 per page.
 */
class Peserta extends BaseController
{
    private const PER_PAGE = 25;

    public function index(): string
    {
        $year    = (string) setting('Pendaftaran.academicYear');
        $filters = [
            'q'         => trim((string) $this->request->getGet('q')),
            'tanggal'   => (string) $this->request->getGet('tanggal'),
            'sesi'      => (string) $this->request->getGet('sesi'),
            'status'    => (string) $this->request->getGet('status'),
            'gelombang' => (string) $this->request->getGet('gelombang'),
        ];
        $waves    = model(WaveModel::class)->forYear($year);
        $sessions = model(TestSessionModel::class)->withStats(['academic_year' => $year]);

        $model = model(ApplicantModel::class);
        $model->select('applicants.id, applicants.registration_number, applicants.full_name, applicants.wave_id, a.session_id, a.seat_number, a.status AS test_status, s.test_date, s.starts_at, s.ends_at, s.label AS session_label, r.name AS room_name')
            ->join('test_assignments a', 'a.applicant_id = applicants.id', 'left')
            ->join('test_sessions s', 's.id = a.session_id', 'left')
            ->join('test_rooms r', 'r.id = s.room_id', 'left')
            ->where('applicants.academic_year', $year)
            ->where('applicants.status <>', 'cancelled');

        if ($filters['q'] !== '') {
            $model->groupStart()->like('applicants.full_name', $filters['q'])->orLike('applicants.registration_number', $filters['q'])->groupEnd();
        }

        if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $filters['tanggal']) === 1) {
            $model->where('s.test_date', $filters['tanggal']);
        } else {
            $filters['tanggal'] = '';
        }

        if (in_array($filters['sesi'], array_map('strval', array_column($sessions, 'id')), true)) {
            $model->where('a.session_id', (int) $filters['sesi']);
        } else {
            $filters['sesi'] = '';
        }

        if ($filters['status'] === 'unscheduled') {
            $model->where('a.id', null);
        } elseif (in_array($filters['status'], TestAssignmentModel::STATUSES, true)) {
            $model->where('a.status', $filters['status']);
        } else {
            $filters['status'] = '';
        }

        if ($filters['gelombang'] === 'tanpa') {
            $model->where('applicants.wave_id', null);
        } elseif (in_array($filters['gelombang'], array_map('strval', array_column($waves, 'id')), true)) {
            $model->where('applicants.wave_id', (int) $filters['gelombang']);
        } else {
            $filters['gelombang'] = '';
        }

        $rows = $model->orderBy('a.id IS NULL', 'ASC', false)
            ->orderBy('s.test_date', 'ASC')
            ->orderBy('s.starts_at', 'ASC')
            ->orderBy('r.sort_order', 'ASC')
            ->orderBy('r.name', 'ASC')
            ->orderBy('a.seat_number', 'ASC')
            ->orderBy('applicants.id', 'ASC')
            ->paginate(self::PER_PAGE);

        $sessionOptions = [];

        foreach ($sessions as $session) {
            $sessionOptions[$session['id']] = trim(($session['label'] ? $session['label'] . ' · ' : '') . TestSchedule::sessionLabel($session));
        }

        return view('Modules\Tes\Views\peserta\index', [
            'title'       => lang('TesPenjadwalan.participantsTitle'),
            'subtitle'    => lang('TesPenjadwalan.participantsIntro', [$year]),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Tes.breadcrumb')], ['label' => lang('TesPenjadwalan.participantsTitle')]],
            'rows'        => $rows,
            'pager'       => $model->pager,
            'filters'     => $filters,
            'waves'       => array_column($waves, 'name', 'id'),
            'sessions'    => $sessionOptions,
            'canAtur'     => auth()->user()->can('tes.atur'),
        ]);
    }
}
