<?php

declare(strict_types=1);

namespace Modules\Berkas\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\DocumentStorage;

/**
 * Sends a stored document inline with headers that keep it private and stop
 * the browser from guessing another content type (FR-DOC-03).
 */
trait ServesDocuments
{
    /**
     * @param array<string, mixed> $document  applicant_documents row
     * @param array<string, mixed> $applicant applicants row
     */
    protected function sendDocument(array $document, array $applicant): ResponseInterface
    {
        $path = (new DocumentStorage())->path($document['file_path'] ?? null);

        if ($path === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $type = model(DocumentTypeModel::class, false)->withDeleted()->find((int) $document['document_type_id']);
        $ext  = str_ends_with($path, '.pdf') ? 'pdf' : 'jpg';
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', ($type['code'] ?? 'berkas') . '-' . ($applicant['registration_number'] ?? $applicant['id'])) . '.' . $ext;
        $mime = in_array($document['mime'], ['image/jpeg', 'application/pdf'], true) ? $document['mime'] : ($ext === 'pdf' ? 'application/pdf' : 'image/jpeg');

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $name . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Content-Length', (string) filesize($path))
            ->setBody((string) file_get_contents($path));
    }
}
