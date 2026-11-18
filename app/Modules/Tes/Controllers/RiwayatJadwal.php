<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use Modules\Tes\Models\TestScheduleChangeModel;
use Modules\Tes\Services\Scheduler;

/**
 * Schedule change log (FR-TEST-04, tes.kehadiran): who, when, from → to,
 * reason; newest first. Filters: dari, sampai (Y-m-d), jenis, q. 25 per page.
 */
class RiwayatJadwal extends BaseController
{
    private const PER_PAGE = 25;

    public function index(): string
    {
        $kinds   = [TestScheduleChangeModel::ALLOCATED, TestScheduleChangeModel::ASSIGNED, TestScheduleChangeModel::MOVED, TestScheduleChangeModel::REMOVED];
        $filters = [
            'q'      => trim((string) $this->request->getGet('q')),
            'dari'   => Scheduler::validDate((string) $this->request->getGet('dari')) ?? '',
            'sampai' => Scheduler::validDate((string) $this->request->getGet('sampai')) ?? '',
            'jenis'  => in_array($this->request->getGet('jenis'), $kinds, true) ? (string) $this->request->getGet('jenis') : '',
        ];

        $model = model(TestScheduleChangeModel::class);
        $rows  = $model->detailed([
            'q'    => $filters['q'],
            'from' => $filters['dari'],
            'to'   => $filters['sampai'],
            'kind' => $filters['jenis'],
        ])->paginate(self::PER_PAGE);

        return view('Modules\Tes\Views\riwayat_jadwal\index', [
            'title'       => lang('TesPenjadwalan.logTitle'),
            'subtitle'    => lang('TesPenjadwalan.logIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Tes.breadcrumb')], ['label' => lang('TesPenjadwalan.logTitle')]],
            'rows'        => $rows,
            'pager'       => $model->pager,
            'filters'     => $filters,
            'kinds'       => array_combine($kinds, array_map(static fn (string $k): string => lang('Tes.change.' . $k), $kinds)),
        ]);
    }
}
