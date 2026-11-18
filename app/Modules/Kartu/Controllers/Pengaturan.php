<?php

declare(strict_types=1);

namespace Modules\Kartu\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Kartu\Services\CardAvailability;

/**
 * Card settings (setting('Kartu.*')): availability rule (D-035), header and footer text.
 */
class Pengaturan extends BaseController
{
    /**
     * @var list<string>
     */
    public const FIELDS = ['availableWhen', 'headerText', 'footerNote'];

    public function index(): string
    {
        $values = [];

        foreach (self::FIELDS as $field) {
            $values[$field] = (string) setting('Kartu.' . $field);
        }

        return view('Modules\Kartu\Views\admin\settings', [
            'title'       => lang('Kartu.settingsTitle'),
            'subtitle'    => lang('Kartu.settingsIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => lang('Kartu.settingsTitle')]],
            'settings'    => ['values' => $values],
        ]);
    }

    public function update(): RedirectResponse
    {
        $rules = [
            'availableWhen' => ['label' => lang('Kartu.availableWhen'), 'rules' => 'required|in_list[' . implode(',', CardAvailability::OPTIONS) . ']'],
            'headerText'    => ['label' => lang('Kartu.headerText'), 'rules' => 'required|max_length[80]'],
            'footerNote'    => ['label' => lang('Kartu.footerNote'), 'rules' => 'permit_empty|max_length[150]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $old = [];
        $new = [];

        foreach (self::FIELDS as $field) {
            $value   = trim((string) $this->request->getPost($field));
            $current = (string) setting('Kartu.' . $field);

            if ($value !== $current) {
                $old[$field] = $current;
                $new[$field] = $value;
                setting('Kartu.' . $field, $value);
            }
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'Kartu', $old, $new, 'Pengaturan kartu diubah');
        }

        return redirect()->route('kartu.pengaturan')->with('message', lang('Kartu.saved'));
    }
}
