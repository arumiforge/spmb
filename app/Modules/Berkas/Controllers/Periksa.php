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
use Modules\Berkas\Services\PanelData;
use Modules\Berkas\Services\UploadInput;
use Modules\Berkas\Services\UploadRejected;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;

/**
 * Staff side of documents: the queue of documents waiting for a check, the
 * review screen, viewing a file, verifying, and uploading on an applicant's behalf.
 */
class Periksa extends BaseController
{
    use ServesDocuments;

    private const PER_PAGE = 20;

    /**
     * Status filter values (query string) → stored status codes.
     *
     * @var array<string, string>
     */
    private const STATUS_FILTERS = [
        'menunggu'      => 'pending',
        'perlu-diganti' => 'invalid',
        'sesuai'        => 'valid',
        'menyusul'      => 'to_follow',
    ];

    /**
     * GET /admin/berkas — documents waiting for a check, newest applicants first.
     */
    public function index(): string
    {
        $filters = [
            'q'         => trim((string) $this->request->getGet('q')),
            'jenis'     => (int) $this->request->getGet('jenis'),
            'gelombang' => (int) $this->request->getGet('gelombang'),
            'status'    => (string) ($this->request->getGet('status') ?? 'menunggu'),
        ];

        if ($filters['status'] !== 'semua' && ! isset(self::STATUS_FILTERS[$filters['status']])) {
            $filters['status'] = 'menunggu';
        }

        $model = $this->queueQuery();

        if ($filters['status'] !== 'semua') {
            $model->where('applicant_documents.status', self::STATUS_FILTERS[$filters['status']]);
        }

        if ($filters['jenis'] > 0) {
            $model->where('applicant_documents.document_type_id', $filters['jenis']);
        }

        if ($filters['gelombang'] > 0) {
            $model->where('a.wave_id', $filters['gelombang']);
        }

        if ($filters['q'] !== '') {
            $model->groupStart()->like('a.full_name', $filters['q'])->orLike('a.registration_number', $filters['q'])->groupEnd();
        }

        $rows = $model->paginate(self::PER_PAGE);

        return view('Modules\Berkas\Views\queue', [
            'title'         => lang('Berkas.queueTitle'),
            'subtitle'      => lang('Berkas.queueIntro'),
            'breadcrumbs'   => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Periksa Berkas']],
            'rows'          => $rows,
            'pager'         => $model->pager,
            'types'         => model(DocumentTypeModel::class)->ordered(),
            'waves'         => model(WaveModel::class)->orderBy('starts_on', 'DESC')->findAll(),
            'filters'       => $filters,
            'statusFilters' => self::STATUS_FILTERS,
            'filtered'      => $filters['q'] !== '' || $filters['jenis'] > 0 || $filters['gelombang'] > 0 || $filters['status'] !== 'menunggu',
        ]);
    }

    /**
     * GET /admin/berkas/<docId>/periksa — file preview next to the applicant's data.
     */
    public function review(int $docId): RedirectResponse|string
    {
        [$document, $applicant, $type] = $this->load($docId);

        $others = array_values(array_filter(
            PanelData::rows((int) $applicant['id']),
            static fn (array $row): bool => $row['doc'] !== null && (int) $row['doc']['id'] !== $docId,
        ));

        return view('Modules\Berkas\Views\review', [
            'title'       => lang('Berkas.reviewTitle'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Periksa Berkas', 'url' => 'admin/berkas'], ['label' => $type['name']]],
            'review'      => [
                'document'  => $document,
                'applicant' => $applicant,
                'type'      => $type,
                'others'    => $others,
                'verifier'  => PanelData::staffNames([$document['verified_by']])[(int) $document['verified_by']] ?? null,
                'canViewApplicant' => auth()->user()->can('pendaftar.view'),
            ],
        ]);
    }

    /**
     * GET /admin/berkas/<docId>/lihat — the stored file (pendaftar.view).
     */
    public function show(int $docId): ResponseInterface
    {
        [$document, $applicant] = $this->load($docId, true);

        return $this->sendDocument($document, $applicant);
    }

    /**
     * POST /admin/berkas/<docId>/periksa — status valid | invalid with a note.
     */
    public function verify(int $docId): RedirectResponse
    {
        [$document, $applicant, $type] = $this->load($docId);
        $status = (string) $this->request->getPost('status');
        $back   = $this->request->getPost('kembali') === 'antrean' ? 'antrean' : 'pendaftar';

        try {
            (new DocumentService())->verify($document, $status, (string) $this->request->getPost('note'), (int) auth()->id());
        } catch (UploadRejected $e) {
            if ($back === 'antrean') {
                return redirect()->back()->withInput()->with('errors', ['note' => $e->getMessage()]);
            }

            return redirect()->to($this->applicantUrl((int) $applicant['id'], (int) $type['id']))->with('berkas_error', [(int) $type['id'] => $e->getMessage()]);
        }

        $message = lang($status === 'valid' ? 'Berkas.verifiedValid' : 'Berkas.verifiedInvalid', [$type['name'], $applicant['full_name']]);

        if ($back === 'pendaftar') {
            return redirect()->to($this->applicantUrl((int) $applicant['id'], (int) $type['id']))->with('message', $message);
        }

        $next = $this->queueQuery()
            ->where('applicant_documents.status', 'pending')
            ->where('applicant_documents.id !=', $docId)
            ->first();

        return $next === null
            ? redirect()->route('berkas.antrean')->with('message', $message . ' ' . lang('Berkas.queueDone'))
            : redirect()->route('berkas.periksa', [(int) $next['id']])->with('message', $message);
    }

    /**
     * POST /admin/pendaftar/<id>/berkas/<typeId> — upload on behalf of the
     * applicant, or mark the document "menyusul" (aksi=menyusul).
     */
    public function upload(int $applicantId, int $typeId): RedirectResponse
    {
        $applicant = model(ApplicantModel::class)->find($applicantId);
        $type      = model(DocumentTypeModel::class)->where('is_active', 1)->find($typeId);

        if ($applicant === null || $type === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $back = redirect()->to($this->applicantUrl($applicantId, $typeId));

        try {
            if ($this->request->getPost('aksi') === 'menyusul') {
                (new DocumentService())->markToFollow($applicant, $type, (int) auth()->id());

                return $back->with('message', lang('Berkas.markedToFollow', [$type['name']]));
            }

            $input = UploadInput::read($this->request);
            (new DocumentService())->upload($applicant, $type, $input['tmp'], $input['name'], (int) auth()->id());
        } catch (UploadRejected $e) {
            return $back->with('berkas_error', [$typeId => $e->getMessage()]);
        }

        return $back->with('message', lang('Berkas.uploadedStaff', [$type['name']]));
    }

    /**
     * Current documents joined with their applicant and type, in queue order.
     */
    private function queueQuery(): ApplicantDocumentModel
    {
        return model(ApplicantDocumentModel::class, false)
            ->select('applicant_documents.*, a.full_name, a.registration_number, a.registered_at, a.wave_id, t.name AS type_name')
            ->join('applicants a', 'a.id = applicant_documents.applicant_id')
            ->join('document_types t', 't.id = applicant_documents.document_type_id')
            ->where('a.deleted_at', null)
            ->orderBy('a.registered_at', 'DESC')
            ->orderBy('a.id', 'DESC')
            ->orderBy('t.sort_order', 'ASC')
            ->orderBy('applicant_documents.id', 'ASC');
    }

    /**
     * The current document with its applicant and type, or 404.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function load(int $docId, bool $withFile = false): array
    {
        $document  = model(ApplicantDocumentModel::class)->find($docId);
        $applicant = $document === null ? null : model(ApplicantModel::class)->find((int) $document['applicant_id']);
        $type      = $document === null ? null : model(DocumentTypeModel::class)->withDeleted()->find((int) $document['document_type_id']);

        if ($document === null || $applicant === null || $type === null || ($withFile && $document['file_path'] === null)) {
            throw PageNotFoundException::forPageNotFound(lang('Berkas.documentNotFound'));
        }

        return [$document, $applicant, $type];
    }

    private function applicantUrl(int $applicantId, int $typeId): string
    {
        return site_url('admin/pendaftar/' . $applicantId) . '#berkas-' . $typeId;
    }
}
