<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Services\RegistrationNumber;

/**
 * Registration settings (setting('Pendaftaran.*')): academic year, online switch,
 * number format and scope (D-031), required and hidden form fields (D-037).
 */
class Pengaturan extends BaseController
{
    public function index(): string
    {
        $form  = new ApplicantForm();
        $modes = [];

        foreach (ApplicantForm::CONFIGURABLE as $field) {
            $modes[$field] = $form->mode($field);
        }

        return view('Modules\Pendaftaran\Views\admin\pengaturan', [
            'title'       => lang('Pendaftaran.settingsTitle'),
            'subtitle'    => lang('Pendaftaran.settingsIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Pendaftaran']],
            'values'      => [
                'academicYear' => (string) setting('Pendaftaran.academicYear'),
                'onlineOpen'   => setting('Pendaftaran.onlineOpen') ? '1' : '0',
                'numberFormat' => (string) setting('Pendaftaran.numberFormat'),
                'numberScope'  => (string) setting('Pendaftaran.numberScope'),
            ],
            'modes' => $modes,
        ]);
    }

    public function update(): RedirectResponse
    {
        $rules = [
            'academicYear' => ['label' => lang('Pendaftaran.academicYear'), 'rules' => 'required|regex_match[/\A\d{4}\/\d{4}\z/]', 'errors' => ['regex_match' => lang('Pendaftaran.academicYearInvalid')]],
            'numberFormat' => ['label' => lang('Pendaftaran.numberFormat'), 'rules' => 'required|max_length[40]'],
            'numberScope'  => ['label' => lang('Pendaftaran.numberScope'), 'rules' => 'required|in_list[year,never]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $year   = (string) $this->request->getPost('academicYear');
        $format = trim((string) $this->request->getPost('numberFormat'));
        $errors = [];

        if ((int) substr($year, 5) !== (int) substr($year, 0, 4) + 1) {
            $errors['academicYear'] = lang('Pendaftaran.academicYearInvalid');
        }

        if (! RegistrationNumber::isValidFormat($format)) {
            $errors['numberFormat'] = lang('Pendaftaran.numberFormatInvalid');
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $posted   = (array) $this->request->getPost('fields');
        $required = [];
        $hidden   = [];

        foreach (ApplicantForm::CONFIGURABLE as $field) {
            $mode = $posted[$field] ?? 'optional';

            if ($mode === 'required') {
                $required[] = $field;
            } elseif ($mode === 'hidden') {
                $hidden[] = $field;
            }
        }

        // At least one parent/guardian name must stay on the form.
        if (array_diff(ApplicantForm::PARENT_NAMES, $hidden) === []) {
            $hidden = array_values(array_diff($hidden, ['mother_name']));
        }

        $values = [
            'academicYear'   => $year,
            'onlineOpen'     => $this->request->getPost('onlineOpen') === '1',
            'numberFormat'   => $format,
            'numberScope'    => (string) $this->request->getPost('numberScope'),
            'requiredFields' => $required,
            'hiddenFields'   => $hidden,
        ];

        $old = [];
        $new = [];

        foreach ($values as $key => $value) {
            $current = setting('Pendaftaran.' . $key);

            if ($current !== $value) {
                $old[$key] = $current;
                $new[$key] = $value;
                setting('Pendaftaran.' . $key, $value);
            }
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'Pendaftaran', $old, $new, 'Pengaturan pendaftaran diubah');
        }

        return redirect()->route('pengaturan.pendaftaran')->with('message', lang('Pendaftaran.settingsSaved'));
    }
}
