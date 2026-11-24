<?php

declare(strict_types=1);

namespace Modules\Referensi\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Referensi\Models\RegionModel;

/**
 * Public region lookups for the address on the registration form (D-111).
 * Applicants have no account, so these are open, throttled per visitor IP and
 * cached by the browser (the list changes only with an update).
 *
 * GET /wilayah/daftar?induk=<code>  children of a region (no induk: provinces)
 * GET /wilayah/cari?q=<text>        districts and villages by name, with the full path
 */
class Wilayah extends BaseController
{
    public function children(): ResponseInterface
    {
        if (($blocked = $this->throttle('wilayah-daftar-', (int) config('Referensi')->regionListPerMinute)) !== null) {
            return $blocked;
        }

        $parent = trim((string) $this->request->getGet('induk'));

        if ($parent !== '' && (RegionModel::levelOf($parent) === null || RegionModel::levelOf($parent) === 4)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => lang('Referensi.regionBadCode')]);
        }

        $etag = '"wilayah-' . md5((string) setting('Referensi.regionsVersion') . '|' . $parent) . '"';

        $this->response
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setHeader('ETag', $etag)
            ->setHeader('Vary', 'Accept-Encoding');

        if (trim($this->request->getHeaderLine('If-None-Match')) === $etag) {
            return $this->response->setStatusCode(304)->setBody('');
        }

        return $this->response->setJSON(model(RegionModel::class)->children($parent === '' ? null : $parent));
    }

    public function search(): ResponseInterface
    {
        if (($blocked = $this->throttle('wilayah-cari-', (int) config('Referensi')->regionSearchPerMinute)) !== null) {
            return $blocked;
        }

        $q = mb_substr(trim((string) $this->request->getGet('q')), 0, 100);

        return $this->response
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setJSON(model(RegionModel::class)->search($q));
    }

    private function throttle(string $prefix, int $perMinute): ?ResponseInterface
    {
        $key = $prefix . md5((string) $this->request->getIPAddress());

        if (service('throttler')->check($key, max(1, $perMinute), MINUTE)) {
            return null;
        }

        return $this->response->setStatusCode(429)
            ->setHeader('Retry-After', (string) max(1, service('throttler')->getTokenTime()))
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(['message' => lang('Referensi.tooMany')]);
    }
}
