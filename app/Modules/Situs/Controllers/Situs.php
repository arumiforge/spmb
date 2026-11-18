<?php

declare(strict_types=1);

namespace Modules\Situs\Controllers;

use App\Controllers\BaseController;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Situs\Services\WaveSchedule;

/**
 * Public information pages for parents and students (FR-WEB-01, FR-WEB-02).
 */
class Situs extends BaseController
{
    public function persyaratan(): string
    {
        $modules   = service('modules');
        $documents = $modules->isEnabled('Berkas') ? $this->documentTypes() : [];

        return view('Modules\Situs\Views\persyaratan', [
            'title'       => lang('Situs.requirementsTitle'),
            'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('Situs.requirementsTitle')]],
            'page'        => [
                'documents'    => $documents,
                'berkasOn'     => $modules->isEnabled('Berkas'),
                'kartuOn'      => $modules->isEnabled('Kartu'),
                'onlineOpen'   => WaveSchedule::onlineOpen(),
                'academicYear' => (string) setting('Pendaftaran.academicYear'),
            ],
        ]);
    }

    public function jadwal(): string
    {
        return view('Modules\Situs\Views\jadwal', [
            'title'       => lang('Situs.scheduleTitle'),
            'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('Situs.scheduleTitle')]],
            'page'        => [
                'waves'        => WaveSchedule::forYear(),
                'banner'       => WaveSchedule::banner(),
                'academicYear' => (string) setting('Pendaftaran.academicYear'),
            ],
        ]);
    }

    /**
     * Active document types in order. Uses DocumentTypeModel::active() once
     * the Berkas package provides it.
     *
     * @return list<array<string, mixed>>
     */
    private function documentTypes(): array
    {
        $model = model(DocumentTypeModel::class);

        if (method_exists($model, 'active')) {
            return $model->active();
        }

        return $model->where('is_active', 1)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();
    }
}
