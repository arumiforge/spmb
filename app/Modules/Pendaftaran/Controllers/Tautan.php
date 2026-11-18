<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Pendaftaran\Models\ApplicantModel;

/**
 * Private link /tautan/<token> (D-034): opens the applicant's own page without
 * typing the number. Only the SHA-256 of the token is stored.
 */
class Tautan extends BaseController
{
    public function open(string $token): RedirectResponse
    {
        $row = model(ApplicantModel::class)->findByAccessToken($token);

        if ($row === null) {
            return redirect()->route('cek-status')->with('error', lang('Pendaftaran.linkInvalid'));
        }

        service('applicantAccess')->grant((int) $row['id']);
        service('audit')->log('status_checked', 'applicants', (int) $row['id'], null, null, 'Dibuka lewat tautan pribadi');

        return redirect()->route('cek-status.data');
    }
}
