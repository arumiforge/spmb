<?php

declare(strict_types=1);

namespace Modules\Kartu\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Kartu\Models\CardTokenModel;
use Modules\Kartu\Services\CardPdf;
use Modules\Pendaftaran\Models\ApplicantModel;

/**
 * Card checks: the public page a QR code opens (/kartu/<token>, minimal data,
 * FR-CARD-02) and the staff lookup by card code or registration number.
 */
class Periksa extends BaseController
{
    /**
     * Checks per IP address per minute on the public page.
     */
    public const CHECKS_PER_MINUTE = 30;

    public static function throttleKey(string $ip): string
    {
        return 'kartu-' . md5($ip);
    }

    public function show(string $token): ResponseInterface|string
    {
        if (! service('throttler')->check(self::throttleKey($this->request->getIPAddress()), self::CHECKS_PER_MINUTE, MINUTE)) {
            $this->response->setStatusCode(429);

            return view('Modules\Kartu\Views\check', ['title' => lang('Kartu.checkTitle'), 'check' => ['state' => 'throttled']]);
        }

        $tokens    = model(CardTokenModel::class);
        $row       = $tokens->findByToken($token);
        $applicant = $row === null ? null : model(ApplicantModel::class)->find((int) $row['applicant_id']);
        $state     = 'invalid';

        if ($row !== null && $row['revoked_at'] === null && $applicant !== null) {
            $tokens->touch((int) $row['id']);
            $state = $applicant['status'] === 'cancelled' ? 'cancelled' : 'valid';
        }

        if ($state === 'invalid') {
            $this->response->setStatusCode(404);
        }

        $card = $state === 'invalid' ? null : CardPdf::details($applicant);
        $user = auth()->loggedIn() ? auth()->user() : null;

        // Never more than number, name and wave here (UU PDP); staff get a link to the full record.
        return view('Modules\Kartu\Views\check', [
            'title' => lang('Kartu.checkTitle'),
            'check' => [
                'state'     => $state,
                'number'    => $card['number'] ?? null,
                'name'      => $card['name'] ?? null,
                'wave'      => $card['wave'] ?? null,
                'staffLink' => $card !== null && $user !== null && $user->can('pendaftar.view') ? site_url('admin/pendaftar/' . $applicant['id']) : null,
            ],
        ]);
    }

    public function lookup(): string
    {
        $query  = trim((string) $this->request->getGet('q'));
        $result = null;

        if ($query !== '') {
            $result = $this->find($query);
        }

        return view('Modules\Kartu\Views\admin\lookup', [
            'title'       => lang('Kartu.lookupTitle'),
            'subtitle'    => lang('Kartu.lookupIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pendaftaran'], ['label' => lang('Kartu.lookupTitle')]],
            'lookup'      => ['query' => $query, 'result' => $result],
        ]);
    }

    /**
     * Resolves a pasted card URL, a bare card code or a registration number.
     *
     * @return array{found: bool, card?: string, applicant?: array<string, mixed>, details?: array<string, string>, token?: array<string, mixed>|null}
     */
    private function find(string $query): array
    {
        $code = $query;

        if (preg_match('#/kartu/([A-Za-z0-9_-]+)#', $query, $m) === 1) {
            $code = $m[1];
        }

        $tokens    = model(CardTokenModel::class);
        $applicant = null;
        $card      = 'none';
        $row       = preg_match('/\A[A-Za-z0-9_-]{20,64}\z/', $code) === 1 ? $tokens->findByToken($code) : null;

        if ($row !== null) {
            $applicant = model(ApplicantModel::class)->find((int) $row['applicant_id']);
            $card      = $row['revoked_at'] === null ? 'valid' : 'revoked';

            if ($card === 'valid') {
                $tokens->touch((int) $row['id']);
            }
        } else {
            $applicant = model(ApplicantModel::class)->where('registration_number', mb_substr($query, 0, 30))->first();
            $row       = $applicant === null ? null : $tokens->activeFor((int) $applicant['id']);
            $card      = $row === null ? 'none' : 'valid';
        }

        if ($applicant === null) {
            return ['found' => false];
        }

        return [
            'found'     => true,
            'card'      => $card,
            'applicant' => $applicant,
            'details'   => CardPdf::details($applicant),
            'token'     => $row,
        ];
    }
}
