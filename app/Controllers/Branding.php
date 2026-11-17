<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Serves the school logo and favicon uploaded in settings.
 *
 * Files live in writable/uploads/branding/ (outside public/). They are public
 * by nature (shown on the sign-in page), so no permission is needed; only
 * paths inside the branding folder are ever served.
 */
class Branding extends BaseController
{
    public const DIR = WRITEPATH . 'uploads/branding/';

    public function logo(): ResponseInterface
    {
        return $this->serve((string) setting('School.logoPath'));
    }

    public function favicon(): ResponseInterface
    {
        return $this->serve((string) setting('School.faviconPath'));
    }

    private function serve(string $name): ResponseInterface
    {
        $name = basename($name);
        $file = self::DIR . $name;

        if ($name === '' || ! is_file($file)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $mime = mime_content_type($file) ?: 'application/octet-stream';

        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'], true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'public, max-age=604800')
            ->setBody((string) file_get_contents($file));
    }
}
