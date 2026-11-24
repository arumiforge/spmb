<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Pendaftaran\Controllers\Concerns\HandlesApplicantForm;
use Modules\Pendaftaran\Exceptions\RegistrationClosed;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Pendaftaran\Services\ApplicantService;

/**
 * Public online registration (FR-REG-01). Open while Pendaftaran.onlineOpen is on
 * and a wave covers today. A possible duplicate never blocks or reveals another
 * applicant (D-032); the success page shows the number and the private link once (D-034).
 */
class Daftar extends BaseController
{
    use HandlesApplicantForm;

    /**
     * Submissions per IP address per minute (spam protection).
     */
    private const SUBMITS_PER_MINUTE = 5;

    private const DONE_KEY = 'pendaftaran_selesai';

    public function index(): string
    {
        $service = new ApplicantService();
        $reason  = $service->onlineClosedReason();
        $crumbs  = [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('Pendaftaran.publicFormCrumb')]];

        if ($reason !== null) {
            return view('Modules\Pendaftaran\Views\public\tutup', [
                'title'       => lang('Pendaftaran.closedTitle'),
                'breadcrumbs' => $crumbs,
                'reason'      => $reason,
                'next'        => $this->nextWave(),
            ]);
        }

        return view('Modules\Pendaftaran\Views\public\daftar', [
            'title'       => lang('Pendaftaran.publicFormTitle'),
            'breadcrumbs' => $crumbs,
            'form'        => new ApplicantForm(),
            'wave'        => model(WaveModel::class)->current(),
        ]);
    }

    public function submit(): RedirectResponse
    {
        if ((string) $this->request->getPost('website') !== '') {
            // Honeypot field filled in: an automated submission.
            return redirect()->route('daftar');
        }

        $throttler = service('throttler');

        if (! $throttler->check('daftar-' . md5($this->request->getIPAddress()), self::SUBMITS_PER_MINUTE, MINUTE)) {
            return redirect()->back()->withInput()->with('error', lang('Pendaftaran.tooManySubmits'));
        }

        $form            = new ApplicantForm();
        [$data, $errors] = $this->validateApplicant($form, $form->visible());

        // Consent checkbox of the last step ("Periksa dan kirim").
        if ((string) $this->request->getPost('agree') !== '1') {
            $errors['agree'] = lang('Pendaftaran.agreeRequired');
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        try {
            $result = (new ApplicantService())->register($data, 'online');
        } catch (RegistrationClosed) {
            return redirect()->route('daftar');
        }

        $row = $result['applicant'];
        service('applicantAccess')->grant((int) $row['id']);
        session()->setFlashdata(self::DONE_KEY, [
            'number' => $row['registration_number'],
            'name'   => $row['full_name'],
            'birth'  => $row['birth_date'],
            'link'   => url_to('tautan', $result['token']),
        ]);

        return redirect()->route('daftar.selesai');
    }

    /**
     * Shown once after a registration; a reload goes to the status check.
     */
    public function done(): RedirectResponse|string
    {
        $done = session()->getFlashdata(self::DONE_KEY);

        if (! is_array($done)) {
            return redirect()->route(service('applicantAccess')->id() === null ? 'cek-status' : 'cek-status.data');
        }

        return view('Modules\Pendaftaran\Views\public\selesai', [
            'title'       => lang('Pendaftaran.doneTitle'),
            'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('Pendaftaran.publicFormCrumb'), 'url' => 'daftar'], ['label' => lang('Pendaftaran.doneCrumb')]],
            'done'        => $done,
        ]);
    }

    /**
     * The next active wave of the academic year that has not started yet.
     *
     * @return array<string, mixed>|null
     */
    private function nextWave(): ?array
    {
        $today = date('Y-m-d');

        foreach (model(WaveModel::class)->forYear((string) setting('Pendaftaran.academicYear')) as $wave) {
            if ((int) $wave['is_active'] === 1 && $wave['starts_on'] > $today) {
                return $wave;
            }
        }

        return null;
    }
}
