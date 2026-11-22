<?php

declare(strict_types=1);

namespace Modules\Situs\Controllers;

use App\Controllers\BaseController;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Situs\Libraries\AnswerRenderer;
use Modules\Situs\Models\SiteQuestionModel;
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
     * Public questions and answers grouped by topic (D-072). With no published
     * question the page shows an empty state (and the menu link is hidden).
     */
    public function tanyaJawab(): string
    {
        $renderer = new AnswerRenderer();
        $topics   = [];
        $used     = [];

        foreach (model(SiteQuestionModel::class)->publishedByTopic() as $group) {
            $slug  = self::slug($group['topic'], 'topik', $used);
            $items = [];

            foreach ($group['items'] as $item) {
                $items[] = [
                    'id'       => 'pertanyaan-' . (int) $item['id'],
                    'question' => (string) $item['question'],
                    'html'     => $renderer->render((string) $item['answer']),
                ];
            }

            $topics[] = ['slug' => $slug, 'topic' => $group['topic'], 'items' => $items];
        }

        $modules = service('modules');

        return view('Modules\Situs\Views\tanya_jawab', [
            'title'       => lang('Situs.qaTitle'),
            'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('Situs.qaTitle')]],
            'page'        => [
                'topics'     => $topics,
                'onlineOpen' => WaveSchedule::onlineOpen(),
                'berkasOn'   => $modules->isEnabled('Berkas'),
                'phone'      => (string) setting('School.phone'),
                'whatsapp'   => (string) setting('School.whatsapp'),
                'email'      => (string) setting('School.email'),
                'address'    => (string) setting('School.address'),
            ],
        ]);
    }

    /**
     * Unique anchor id for a heading, e.g. "biaya-pendaftaran".
     *
     * @param array<string, true> $used
     */
    private static function slug(string $label, string $fallback, array &$used): string
    {
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($label)), '-') ?: $fallback;
        $base = $slug;

        for ($i = 2; isset($used[$slug]); $i++) {
            $slug = $base . '-' . $i;
        }

        $used[$slug] = true;

        return $slug;
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
