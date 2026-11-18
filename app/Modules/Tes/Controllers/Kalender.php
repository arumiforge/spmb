<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestHolidayModel;
use Modules\Tes\Models\TestSessionModel;

/**
 * Test calendar (FR-TEST-01): one month of test days with their sessions and
 * seats, holidays, and the holiday list. A holiday cannot be added on a day
 * that already has sessions (D-059: no session on a holiday).
 */
class Kalender extends BaseController
{
    private const MONTHS = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function index(): string
    {
        $param = (string) $this->request->getGet('bulan');
        $month = preg_match('/\A(19|20)\d{2}-(0[1-9]|1[0-2])\z/', $param) === 1
            ? new DateTimeImmutable($param . '-01')
            : new DateTimeImmutable(date('Y-m-01'));
        $first = $month->format('Y-m-d');
        $last  = $month->format('Y-m-t');

        $sessions = model(TestSessionModel::class)->withStats(['date_from' => $first, 'date_to' => $last]);
        $holidays = model(TestHolidayModel::class)->between($first, $last);
        $days     = [];

        foreach ($sessions as $row) {
            $date = (string) $row['test_date'];
            $days[$date] ??= ['sessions' => [], 'filled' => 0, 'quota' => 0];
            $days[$date]['sessions'][] = $row;
            $days[$date]['filled'] += $row['filled'];
            $days[$date]['quota'] += (int) $row['quota'];
        }

        // Grid of whole weeks, Monday first.
        $start = $month->modify('-' . ((int) $month->format('N') - 1) . ' days');
        $end   = (new DateTimeImmutable($last))->modify('+' . (7 - (int) (new DateTimeImmutable($last))->format('N')) . ' days');
        $dates = [];

        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            $dates[] = $day->format('Y-m-d');
        }

        $weeks = array_chunk($dates, 7);

        $label = self::MONTHS[(int) $month->format('n')] . ' ' . $month->format('Y');

        return view('Modules\Tes\Views\kalender\index', [
            'title'       => lang('TesJadwal.calendarTitle'),
            'subtitle'    => lang('TesJadwal.calendarIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Tes.breadcrumb')], ['label' => lang('TesJadwal.calendarTitle')]],
            'month'       => $month->format('Y-m'),
            'monthLabel'  => $label,
            'prev'        => $month->modify('-1 month')->format('Y-m'),
            'next'        => $month->modify('+1 month')->format('Y-m'),
            'thisMonth'   => date('Y-m'),
            'weeks'       => $weeks,
            'days'        => $days,
            'holidays'    => $holidays,
            'upcoming'    => model(TestHolidayModel::class)->upcoming($first),
            'today'       => date('Y-m-d'),
            'summary'     => [
                'days'     => count($days),
                'sessions' => count($sessions),
                'filled'   => array_sum(array_column($days, 'filled')),
                'quota'    => array_sum(array_column($days, 'quota')),
            ],
        ]);
    }

    public function addHoliday(): RedirectResponse
    {
        $rules = [
            'date' => ['label' => lang('TesJadwal.holidayDate'), 'rules' => 'required|valid_date[Y-m-d]'],
            'note' => ['label' => lang('TesJadwal.holidayNote'), 'rules' => 'permit_empty|max_length[100]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $date     = (string) $this->request->getPost('date');
        $note     = trim((string) $this->request->getPost('note'));
        $holidays = model(TestHolidayModel::class);
        $back     = redirect()->to(url_to('tes.kalender') . '?bulan=' . substr($date, 0, 7));

        if ($holidays->isHoliday($date)) {
            return $back->withInput()->with('errors', ['date' => lang('TesJadwal.holidayExists', [TestSchedule::dayLabel($date)])]);
        }

        $count = model(TestSessionModel::class)->countOnDate($date);

        if ($count > 0) {
            return $back->withInput()->with('errors', ['date' => lang('TesJadwal.holidayHasSessions', [TestSchedule::dayLabel($date), $count])]);
        }

        $holidays->insert(['date' => $date, 'note' => $note === '' ? null : $note]);

        return $back->with('message', lang('TesJadwal.holidayAdded', [TestSchedule::dayLabel($date)]));
    }

    public function deleteHoliday(int $id): RedirectResponse
    {
        $holidays = model(TestHolidayModel::class);
        $holiday  = $holidays->find($id);

        if ($holiday === null) {
            return redirect()->route('tes.kalender')->with('error', lang('TesJadwal.holidayNotFound'));
        }

        $holidays->delete($id);

        return redirect()->to(url_to('tes.kalender') . '?bulan=' . substr((string) $holiday['date'], 0, 7))
            ->with('message', lang('TesJadwal.holidayDeleted', [TestSchedule::dayLabel((string) $holiday['date'])]));
    }
}
