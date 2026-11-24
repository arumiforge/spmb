<?php

declare(strict_types=1);

namespace Modules\Berkas\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Berkas\Services\TemplateStorage;

/**
 * Sends a document type's example file as a download under the school's own
 * file name.
 */
trait ServesTemplates
{
    /**
     * @param array<string, mixed> $type document_types row
     */
    protected function sendTemplate(array $type): ResponseInterface
    {
        $path = (new TemplateStorage())->path($type['template_path'] ?? null);

        if ($path === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $name  = TemplateStorage::downloadName($type);
        $ascii = trim((string) preg_replace('/[^A-Za-z0-9._ -]+/', '-', $name), ' -') ?: 'contoh';

        return $this->response
            ->setHeader('Content-Type', TemplateStorage::contentType((string) $type['template_path']))
            ->setHeader('Content-Disposition', 'attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name))
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'no-cache')
            ->setHeader('Content-Length', (string) filesize($path))
            ->setBody((string) file_get_contents($path));
    }
}
