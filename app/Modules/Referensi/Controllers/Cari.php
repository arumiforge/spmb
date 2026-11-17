<?php

declare(strict_types=1);

namespace Modules\Referensi\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Referensi\Models\SchoolModel;

/**
 * GET /sekolah-asal/cari?q=<text> — public school search for the registration
 * form. JSON list (max 20, active schools, configured provinces first),
 * throttled per visitor IP (Referensi.searchPerMinute, default 30/min).
 */
class Cari extends BaseController
{
    public function index(): ResponseInterface
    {
        $perMinute = max(1, (int) config('Referensi')->searchPerMinute);
        $key       = 'referensi-cari-' . md5((string) $this->request->getIPAddress());

        if (! service('throttler')->check($key, $perMinute, MINUTE)) {
            return $this->response->setStatusCode(429)
                ->setHeader('Retry-After', (string) max(1, service('throttler')->getTokenTime()))
                ->setJSON(['message' => lang('Referensi.tooMany')]);
        }

        $q = mb_substr(trim((string) $this->request->getGet('q')), 0, 100);

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(model(SchoolModel::class)->search($q));
    }
}
