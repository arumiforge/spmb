<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\BrandingAssets;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Serves the school's brand images uploaded in settings (logos, icons, link preview image).
 *
 * Files live in writable/uploads/branding/ (outside public/). They are public
 * by nature (shown on the sign-in page), so no permission is needed; only
 * paths inside the branding folder are ever served.
 */
class Branding extends BaseController
{
    /**
     * Same folder as BrandingAssets::DIR (kept for existing callers).
     */
    public const DIR = BrandingAssets::DIR;

    public function logo(): ResponseInterface
    {
        return $this->show('logo');
    }

    public function favicon(): ResponseInterface
    {
        return $this->show('favicon');
    }

    /**
     * Any brand image by its public slug (see BrandingAssets::ASSETS).
     */
    public function show(string $slug): ResponseInterface
    {
        $key  = BrandingAssets::keyForSlug($slug);
        $name = $key === null ? '' : BrandingAssets::file($key);
        $file = BrandingAssets::DIR . $name;

        if ($name === '' || ! is_file($file)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $mime = mime_content_type($file) ?: 'application/octet-stream';

        if (! in_array($mime, BrandingAssets::MIMES, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'public, max-age=604800')
            ->setBody((string) file_get_contents($file));
    }
}
