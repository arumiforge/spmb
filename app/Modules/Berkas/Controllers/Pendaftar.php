<?php

declare(strict_types=1);

namespace Modules\Berkas\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\DocumentService;
use Modules\Berkas\Services\UploadInput;
use Modules\Berkas\Services\UploadRejected;
use Modules\Pendaftaran\Models\ApplicantModel;

/**
 * Applicant side of documents (no account, D-008/D-034): upload, mark
 * "menyusul" and view their own files. Access comes only from the session
 * grant of service('applicantAccess'); anything else is 404.
 */
class Pendaftar extends BaseController
{
    use ServesDocuments;

    /**
     * POST /cek-status/berkas/<typeId> — upload a file, or aksi=menyusul.
     */
    public function upload(int $typeId): RedirectResponse
    {
        $applicantId = service('applicantAccess')->id();

        if ($applicantId === null) {
            return redirect()->to(site_url('cek-status'))->with('error', lang('Berkas.sessionExpired'));
        }

        $applicant = model(ApplicantModel::class)->find($applicantId);
        $type      = model(DocumentTypeModel::class)->where('is_active', 1)->find($typeId);

        if ($applicant === null || $type === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $back = redirect()->to(site_url('cek-status/data') . '#berkas-' . $typeId);

        try {
            if ($this->request->getPost('aksi') === 'menyusul') {
                (new DocumentService())->markToFollow($applicant, $type, null);

                return $back->with('message', lang('Berkas.markedToFollow', [localized($type, 'name')]));
            }

            $input = UploadInput::read($this->request);
            (new DocumentService())->upload($applicant, $type, $input['tmp'], $input['name'], null);
        } catch (UploadRejected $e) {
            return $back->with('berkas_error', [$typeId => $e->getMessage()]);
        }

        return $back->with('message', lang('Berkas.uploaded', [localized($type, 'name')]));
    }

    /**
     * GET /cek-status/berkas/<docId> — the applicant's own current file.
     */
    public function show(int $docId): ResponseInterface
    {
        $document = model(ApplicantDocumentModel::class)->find($docId);

        if ($document === null || $document['file_path'] === null || ! service('applicantAccess')->allows((int) $document['applicant_id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $applicant = model(ApplicantModel::class)->find((int) $document['applicant_id']);

        if ($applicant === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->sendDocument($document, $applicant);
    }
}
