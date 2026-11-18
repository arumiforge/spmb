<?php

declare(strict_types=1);

namespace Modules\Kartu\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Kartu\Services\CardAvailability;
use Modules\Kartu\Services\CardPdf;
use Modules\Kartu\Services\CardTokenService;
use Modules\Pendaftaran\Models\ApplicantModel;

/**
 * The applicant downloads their own card (after the status check or private
 * link, D-034), when the availability rule allows it (FR-CARD-04).
 */
class Unduh extends BaseController
{
    public function index(): ResponseInterface|string
    {
        $id        = service('applicantAccess')->id();
        $applicant = $id === null ? null : model(ApplicantModel::class)->find($id);

        if ($applicant === null) {
            return redirect()->to(site_url('cek-status'))->with('warning', lang('Kartu.needCheck'));
        }

        if (! CardAvailability::allows($applicant)) {
            $cancelled = $applicant['status'] === 'cancelled';

            return view('Modules\Kartu\Views\unavailable', [
                'title'       => lang('Kartu.title'),
                'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => 'Cek Status', 'url' => 'cek-status/data'], ['label' => lang('Kartu.title')]],
                'notice'      => [
                    'title' => lang($cancelled ? 'Kartu.cancelledTitle' : 'Kartu.notYetTitle'),
                    'text'  => lang($cancelled ? 'Kartu.cancelledApplicant' : 'Kartu.notYetText'),
                ],
            ]);
        }

        $token = (new CardTokenService())->tokenFor((int) $applicant['id']);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . self::fileName($applicant) . '"')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((new CardPdf())->render($applicant, $token));
    }

    /**
     * @param array<string, mixed> $applicant
     */
    public static function fileName(array $applicant): string
    {
        $number = preg_replace('/[^A-Za-z0-9-]+/', '-', (string) $applicant['registration_number']) ?? 'kartu';

        return lang('Kartu.fileName', [strtolower(trim($number, '-'))]);
    }
}
