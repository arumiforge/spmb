<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Pendaftaran\Controllers\Concerns\HandlesApplicantForm;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Pendaftaran\Services\ApplicantService;
use Modules\Pendaftaran\Services\RegistrationNumber;

/**
 * Public status check without an account (FR-REG-07, D-008, D-034):
 * registration number + date of birth (throttled per IP, one generic error),
 * then the applicant's own page, corrections of contact/address fields and sign-out.
 */
class CekStatus extends BaseController
{
    use HandlesApplicantForm;

    public function index(): RedirectResponse|string
    {
        if ($this->current() !== null) {
            return redirect()->route('cek-status.data');
        }

        return view('Modules\Pendaftaran\Views\public\cek_status', [
            'title'       => lang('Pendaftaran.checkTitle'),
            'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('Pendaftaran.checkCrumb')]],
            'example'     => RegistrationNumber::format(
                RegistrationNumber::isValidFormat((string) setting('Pendaftaran.numberFormat')) ? (string) setting('Pendaftaran.numberFormat') : 'PMB-{YEAR}-{0000}',
                (string) setting('Pendaftaran.academicYear'),
                12,
            ),
        ]);
    }

    public function check(): RedirectResponse
    {
        $attempts = max(1, (int) setting('Pendaftaran.statusCheckAttempts'));

        if (! service('throttler')->check('cek-status-' . md5($this->request->getIPAddress()), $attempts, MINUTE)) {
            return redirect()->back()->withInput()->with('check_error', lang('Pendaftaran.checkThrottled'));
        }

        $number = (string) $this->request->getPost('number');
        $birth  = (string) $this->request->getPost('birth_date');
        $row    = model(ApplicantModel::class)->findByNumberAndBirthDate($number, $birth);

        if ($row === null) {
            return redirect()->back()->withInput()->with('check_error', lang('Pendaftaran.checkFailed'));
        }

        service('applicantAccess')->grant((int) $row['id']);
        service('audit')->log('status_checked', 'applicants', (int) $row['id'], null, null, 'Cek status dengan nomor dan tanggal lahir');

        return redirect()->route('cek-status.data');
    }

    public function show(): RedirectResponse|string
    {
        $row = $this->current();

        if ($row === null) {
            return $this->ended();
        }

        $wave = $row['wave_id'] === null ? null : model(WaveModel::class)->withDeleted()->find((int) $row['wave_id']);

        return view('Modules\Pendaftaran\Views\public\data', [
            'title'       => lang('Pendaftaran.myTitle'),
            'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('Pendaftaran.checkCrumb'), 'url' => 'cek-status'], ['label' => lang('Pendaftaran.myTitle')]],
            'applicant'   => $row,
            'waveName'    => $wave !== null ? localized($wave, 'name') : '-',
            'schoolName'  => self::schoolName($row),
            'form'        => new ApplicantForm(),
        ]);
    }

    public function edit(): RedirectResponse|string
    {
        $row = $this->current();

        if ($row === null) {
            return $this->ended();
        }

        if ($row['status'] === 'cancelled') {
            return redirect()->route('cek-status.data')->with('error', lang('Pendaftaran.editCancelled'));
        }

        return view('Modules\Pendaftaran\Views\public\ubah', [
            'title'       => lang('Pendaftaran.editTitlePublic'),
            'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('Pendaftaran.myTitle'), 'url' => 'cek-status/data'], ['label' => lang('Pendaftaran.edit')]],
            'applicant'   => $row,
            'form'        => new ApplicantForm(),
            'fields'      => $this->editableFields(new ApplicantForm()),
        ]);
    }

    public function update(): RedirectResponse
    {
        $row = $this->current();

        if ($row === null) {
            return $this->ended();
        }

        if ($row['status'] === 'cancelled') {
            return redirect()->route('cek-status.data')->with('error', lang('Pendaftaran.editCancelled'));
        }

        $form            = new ApplicantForm();
        [$data, $errors] = $this->validateApplicant($form, $this->editableFields($form));

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $before  = $row;
        $updated = (new ApplicantService())->update((int) $row['id'], $data);

        return redirect()->route('cek-status.data')->with('message', lang($updated === $before ? 'Pendaftaran.editNothing' : 'Pendaftaran.editSaved'));
    }

    public function signOut(): RedirectResponse
    {
        service('applicantAccess')->revoke();

        return redirect()->route('cek-status')->with('message', lang('Pendaftaran.mySignedOutDone'));
    }

    /**
     * The applicant this visitor may see, or null (grant missing, expired or applicant deleted).
     *
     * @return array<string, mixed>|null
     */
    private function current(): ?array
    {
        $id = service('applicantAccess')->id();

        if ($id === null) {
            return null;
        }

        $row = model(ApplicantModel::class)->find($id);

        if ($row === null) {
            service('applicantAccess')->revoke();
        }

        return $row;
    }

    private function ended(): RedirectResponse
    {
        return redirect()->route('cek-status')->with('warning', lang('Pendaftaran.sessionEnded'));
    }

    /**
     * Contact and address fields shown on the form (D-037); identity stays with staff.
     *
     * @return list<string>
     */
    private function editableFields(ApplicantForm $form): array
    {
        return array_values(array_filter(ApplicantForm::SELF_EDITABLE, $form->shows(...)));
    }
}
