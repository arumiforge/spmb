<?php

declare(strict_types=1);

namespace Modules\Kartu\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Kartu\Services\CardPdf;
use Modules\Kartu\Services\CardTokenService;
use Modules\Pendaftaran\Models\ApplicantModel;

/**
 * Staff print and renew cards (kartu.cetak). Staff may always print, whatever
 * the availability rule, except for cancelled registrations.
 */
class Cetak extends BaseController
{
    public function show(int $id): ResponseInterface
    {
        $applicant = $this->applicant($id);

        if ($applicant['status'] === 'cancelled') {
            return redirect()->back()->with('error', lang('Kartu.cancelledStaff'));
        }

        $staffId = (int) auth()->id();
        $token   = (new CardTokenService())->tokenFor($id, $staffId);
        service('audit')->log('card_printed', 'applicants', $id, null, null, 'Kartu ' . $applicant['registration_number'] . ' dicetak', $staffId);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . Unduh::fileName($applicant) . '"')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((new CardPdf())->render($applicant, $token));
    }

    public function renew(int $id): RedirectResponse
    {
        $applicant = $this->applicant($id);

        if ($applicant['status'] === 'cancelled') {
            return redirect()->back()->with('error', lang('Kartu.cancelledStaff'));
        }

        (new CardTokenService())->renew($id, (int) auth()->id());

        return redirect()->back()->with('message', lang('Kartu.renewed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function applicant(int $id): array
    {
        $applicant = model(ApplicantModel::class)->find($id);

        if ($applicant === null) {
            throw PageNotFoundException::forPageNotFound(lang('Kartu.notFound'));
        }

        return $applicant;
    }
}
