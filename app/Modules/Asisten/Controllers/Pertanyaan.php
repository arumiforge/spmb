<?php

declare(strict_types=1);

namespace Modules\Asisten\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use Modules\Asisten\Models\QuestionLogModel;

/**
 * Website → Pertanyaan pengunjung (asisten.atur, D-110): what visitors asked the assistant, how it was
 * answered, the most common questions Tanya Jawab did not answer (each with "Jadikan tanya jawab",
 * which opens the Tanya Jawab form with the question filled in) and the log retention setting.
 */
class Pertanyaan extends BaseController
{
    public const RANGES        = [7, 30, 90];
    public const PER_PAGE      = 25;
    public const RETENTION_MIN = 7;
    public const RETENTION_MAX = 730;

    public function index(): string
    {
        $range = (int) $this->request->getGet('rentang');
        $range = in_array($range, self::RANGES, true) ? $range : 30;
        $kind  = (string) $this->request->getGet('jenis');
        $kind  = in_array($kind, QuestionLogModel::KINDS, true) ? $kind : '';
        $since = Time::today()->subDays($range - 1)->toDateTimeString();
        $log   = model(QuestionLogModel::class);

        $recent = $log->where('created_at >=', $since);

        if ($kind !== '') {
            $recent->where('answered_by', $kind);
        }

        $rows = $recent->orderBy('id', 'DESC')->paginate(self::PER_PAGE);
        $user = auth()->user();

        return view('Modules\Asisten\Views\admin\pertanyaan', [
            'title'       => lang('Asisten.logTitle'),
            'subtitle'    => lang('Asisten.logIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Website'], ['label' => lang('Asisten.logTitle')]],
            'range'       => $range,
            'kind'        => $kind,
            'counts'      => $log->countsSince($since),
            'top'         => $log->topUnanswered($since),
            'rows'        => $rows,
            'pager'       => $log->pager,
            'canQa'       => $user !== null && $user->can('situs.kelola') && service('modules')->isEnabled('Situs'),
            'retention'   => (string) setting('Asisten.retentionDays'),
        ]);
    }

    public function update(): RedirectResponse
    {
        $rules = [
            'retentionDays' => [
                'label' => lang('Asisten.fieldRetention'),
                'rules' => 'required|is_natural|greater_than_equal_to[' . self::RETENTION_MIN . ']|less_than_equal_to[' . self::RETENTION_MAX . ']',
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $days    = (int) $this->request->getPost('retentionDays');
        $current = (int) setting('Asisten.retentionDays');

        if ($current !== $days) {
            setting('Asisten.retentionDays', $days);
            service('audit')->log('settings_updated', 'settings', 'Asisten', ['retentionDays' => $current], ['retentionDays' => $days], 'Lama simpan pertanyaan Tanya Asisten diubah');
            model(QuestionLogModel::class)->prune($days);
        }

        return redirect()->route('asisten.pertanyaan')->with('message', lang('Asisten.saved'));
    }
}
