<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Placement test settings (setting('Tes.*'), app/Config/Tes.php): schedule
 * shown to applicants (D-062), who is scheduled automatically (D-060),
 * applicant instructions and the generator's default session length.
 * Changes are audited as one `settings_updated` event, like other settings pages.
 */
class Pengaturan extends BaseController
{
    public const INSTRUCTIONS_MAX = 500;

    public function index(): string
    {
        return view('Modules\Tes\Views\pengaturan\index', [
            'title'       => lang('TesJadwal.settingsTitle'),
            'subtitle'    => lang('TesJadwal.settingsIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Tes']],
            'values'      => [
                'scheduleVisible'          => setting('Tes.scheduleVisible') ? '1' : '0',
                'eligibleStatuses'         => array_values((array) setting('Tes.eligibleStatuses')),
                'requireCompleteDocuments' => setting('Tes.requireCompleteDocuments') ? '1' : '0',
                'instructions'             => (string) setting('Tes.instructions'),
                'defaultDurationMinutes'   => (string) setting('Tes.defaultDurationMinutes'),
            ],
            'statuses' => self::statuses(),
        ]);
    }

    public function update(): RedirectResponse
    {
        $rules = [
            'instructions'           => ['label' => lang('TesJadwal.instructions'), 'rules' => 'permit_empty|max_length[' . self::INSTRUCTIONS_MAX . ']'],
            'defaultDurationMinutes' => ['label' => lang('TesJadwal.defaultDuration'), 'rules' => 'required|is_natural|greater_than_equal_to[30]|less_than_equal_to[480]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $posted   = array_map('strval', (array) $this->request->getPost('eligibleStatuses'));
        $eligible = array_values(array_intersect(array_keys(self::statuses()), $posted));

        if ($eligible === []) {
            return redirect()->back()->withInput()->with('errors', ['eligibleStatuses' => lang('TesJadwal.eligibleNone')]);
        }

        $values = [
            'scheduleVisible'          => $this->request->getPost('scheduleVisible') === '1',
            'eligibleStatuses'         => $eligible,
            'requireCompleteDocuments' => $this->request->getPost('requireCompleteDocuments') === '1',
            'instructions'             => trim((string) $this->request->getPost('instructions')),
            'defaultDurationMinutes'   => (int) $this->request->getPost('defaultDurationMinutes'),
        ];

        $old = [];
        $new = [];

        foreach ($values as $key => $value) {
            $current = setting('Tes.' . $key);

            if ($current !== $value) {
                $old[$key] = $current;
                $new[$key] = $value;
                setting('Tes.' . $key, $value);
            }
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'Tes', $old, $new, lang('TesJadwal.settingsAudit'));
        }

        return redirect()->route('tes.pengaturan')->with('message', lang('TesJadwal.settingsSaved'));
    }

    /**
     * Applicant statuses that may be scheduled: every status except cancelled.
     *
     * @return array<string, string> code => label
     */
    private static function statuses(): array
    {
        $statuses = (array) lang('Status.applicant');
        unset($statuses['cancelled']);

        return $statuses;
    }
}
