<?php

declare(strict_types=1);

namespace Modules\Analitik\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Analitik\Libraries\VisitStats;

/**
 * Website → Pengunjung (analitik.view): summary tiles, visitors per day with a
 * 7 / 30 / 90 day switch (?rentang=), the most opened pages of the last 30 days
 * and, for pengaturan.sekolah, the settings card (footer line, retention).
 * Settings changes are audited as one `settings_updated` event.
 */
class Pengunjung extends BaseController
{
    public const RANGES        = [7, 30, 90];
    public const RETENTION_MIN = 30;
    public const RETENTION_MAX = 3650;

    public function index(): string
    {
        $range = (int) $this->request->getGet('rentang');
        $range = in_array($range, self::RANGES, true) ? $range : 30;
        $stats = new VisitStats();
        $user  = auth()->user();

        return view('Modules\Analitik\Views\index', [
            'title'       => lang('Analitik.title'),
            'subtitle'    => lang('Analitik.intro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Website'], ['label' => lang('Analitik.menu')]],
            'totals'      => $stats->totals(),
            'daily'       => $stats->daily($range),
            'range'       => $range,
            'pages'       => $stats->topPages(30, 10),
            'canSettings' => $user !== null && $user->can('pengaturan.sekolah'),
            'values'      => [
                'showInFooter'  => setting('Analitik.showInFooter') ? '1' : '0',
                'retentionDays' => (string) setting('Analitik.retentionDays'),
            ],
        ]);
    }

    public function update(): RedirectResponse
    {
        $rules = [
            'retentionDays' => [
                'label' => lang('Analitik.retentionDays'),
                'rules' => 'required|is_natural|greater_than_equal_to[' . self::RETENTION_MIN . ']|less_than_equal_to[' . self::RETENTION_MAX . ']',
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $values = [
            'showInFooter'  => $this->request->getPost('showInFooter') === '1',
            'retentionDays' => (int) $this->request->getPost('retentionDays'),
        ];
        $old = [];
        $new = [];

        foreach ($values as $key => $value) {
            $current = setting('Analitik.' . $key);

            if ($current !== $value) {
                $old[$key] = $current;
                $new[$key] = $value;
                setting('Analitik.' . $key, $value);
            }
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'Analitik', $old, $new, lang('Analitik.settingsAudit'));
        }

        return redirect()->route('analitik')->with('message', lang('Analitik.settingsSaved'));
    }
}
