<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Pendaftaran\Controllers\Concerns\HandlesApplicantForm;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Pendaftaran\Services\ApplicantService;
use Modules\Pendaftaran\Services\DuplicateChecker;

/**
 * Applicant data for staff: list with filters, offline registration
 * (FR-REG-02), detail, edit, status, duplicate review (D-032), private link
 * and soft delete. Other modules add sections to the detail page through the
 * applicant_admin panel.
 */
class Pendaftar extends BaseController
{
    use HandlesApplicantForm;

    private const PER_PAGE = 25;

    private ApplicantModel $applicants;
    private WaveModel $waves;

    public function __construct()
    {
        $this->applicants = model(ApplicantModel::class);
        $this->waves      = model(WaveModel::class);
    }

    public function index(): string
    {
        $year    = (string) setting('Pendaftaran.academicYear');
        $filters = [
            'q'         => trim((string) $this->request->getGet('q')),
            'gelombang' => (string) $this->request->getGet('gelombang'),
            'cara'      => (string) $this->request->getGet('cara'),
            'status'    => (string) $this->request->getGet('status'),
            'berkas'    => (string) $this->request->getGet('berkas'),
            'ganda'     => (string) $this->request->getGet('ganda'),
        ];
        $waves = $this->waves->forYear($year);

        $builder = $this->applicants->where('academic_year', $year);

        if ($filters['q'] !== '') {
            $q      = $filters['q'];
            $digits = preg_replace('/\D/', '', $q) ?? '';
            $builder->groupStart()
                ->like('full_name', $q)
                ->orLike('registration_number', $q);

            if ($digits !== '' && strlen($digits) >= 10) {
                $builder->orWhere('nisn', $digits)->orWhere('nik', $digits);
            }

            $builder->groupEnd();
        }

        if ($filters['gelombang'] === 'tanpa') {
            $builder->where('wave_id', null);
        } elseif (in_array($filters['gelombang'], array_map('strval', array_column($waves, 'id')), true)) {
            $builder->where('wave_id', (int) $filters['gelombang']);
        }

        if (in_array($filters['cara'], ApplicantService::METHODS, true)) {
            $builder->where('method', $filters['cara']);
        }

        if (in_array($filters['status'], ApplicantService::STATUSES, true)) {
            $builder->where('status', $filters['status']);
        }

        if (in_array($filters['berkas'], ['incomplete', 'pending', 'to_follow', 'complete'], true)) {
            $builder->where('document_status', $filters['berkas']);
        }

        if ($filters['ganda'] === '1') {
            $builder->where('possible_duplicate', 1);
        }

        $rows = $builder->orderBy('registered_at', 'DESC')->orderBy('id', 'DESC')->paginate(self::PER_PAGE);

        return view('Modules\Pendaftaran\Views\admin\pendaftar\index', [
            'title'       => lang('Pendaftaran.listTitle'),
            'subtitle'    => lang('Pendaftaran.listIntro', [$year]),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Data Pendaftar']],
            'applicants'  => $rows,
            'pager'       => $this->applicants->pager,
            'waves'       => array_column($waves, 'name', 'id'),
            'filters'     => $filters,
            'canCreate'   => auth()->user()->can('pendaftar.create'),
        ]);
    }

    public function new(): string
    {
        $current = $this->waves->current();

        return view('Modules\Pendaftaran\Views\admin\pendaftar\form', [
            'title'       => lang('Pendaftaran.addTitle'),
            'subtitle'    => lang('Pendaftaran.addIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Data Pendaftar', 'url' => 'admin/pendaftar'], ['label' => 'Tambah']],
            'applicant'   => null,
            'form'        => new ApplicantForm(),
            'values'      => ['wave_id' => $current['id'] ?? ''],
            'fields'      => null,
            'waves'       => $this->waveOptions(),
            'duplicates'  => session('duplicates') ?? [],
        ]);
    }

    public function create(): RedirectResponse
    {
        $form            = new ApplicantForm();
        $fields          = [...$form->visible(), ...ApplicantForm::STAFF_ONLY];
        [$data, $errors] = $this->validateApplicant($form, $fields);
        $errors += $this->waveError($data);

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $duplicates = (new DuplicateChecker())->find($data);

        if ($duplicates !== [] && $this->request->getPost('confirm_duplicate') !== '1') {
            return redirect()->back()->withInput()
                ->with('duplicates', $duplicates)
                ->with('warning', lang('Pendaftaran.duplicateConfirmNeeded'));
        }

        $result = (new ApplicantService())->register($data, 'offline', (int) auth()->id());
        $row    = $result['applicant'];

        return redirect()->route('pendaftar.detail', [$row['id']])
            ->with('message', lang('Pendaftaran.createdOffline', [$row['registration_number']]))
            ->with('private_link', url_to('tautan', $result['token']));
    }

    public function show(int $id): RedirectResponse|string
    {
        $row = $this->applicants->find($id);

        if ($row === null) {
            return redirect()->route('pendaftar')->with('error', lang('Pendaftaran.notFound'));
        }

        $wave    = $row['wave_id'] === null ? null : $this->waves->withDeleted()->find((int) $row['wave_id']);
        $creator = $row['created_by'] === null ? null : model(UserModel::class)->find((int) $row['created_by']);
        $user    = auth()->user();

        return view('Modules\Pendaftaran\Views\admin\pendaftar\show', [
            'title'       => $row['full_name'],
            'subtitle'    => lang('Pendaftaran.numberLabel') . ' ' . $row['registration_number'],
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Data Pendaftar', 'url' => 'admin/pendaftar'], ['label' => 'Detail']],
            'applicant'   => $row,
            'waveName'    => $wave['name'] ?? lang('Pendaftaran.noWave'),
            'schoolName'  => self::schoolName($row),
            'creatorName' => $creator === null ? null : ($creator->full_name ?: $creator->username),
            'duplicates'  => (int) $row['possible_duplicate'] === 1 ? (new DuplicateChecker())->find($row, $id) : [],
            'canEdit'     => $user->can('pendaftar.edit'),
            'canDelete'   => $user->can('pendaftar.delete'),
            'privateLink' => session('private_link'),
        ]);
    }

    public function edit(int $id): RedirectResponse|string
    {
        $row = $this->applicants->find($id);

        if ($row === null) {
            return redirect()->route('pendaftar')->with('error', lang('Pendaftaran.notFound'));
        }

        return view('Modules\Pendaftaran\Views\admin\pendaftar\form', [
            'title'       => lang('Pendaftaran.editTitle'),
            'subtitle'    => $row['full_name'] . ' · ' . $row['registration_number'],
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Data Pendaftar', 'url' => 'admin/pendaftar'],
                ['label' => 'Detail', 'url' => 'admin/pendaftar/' . $id],
                ['label' => 'Ubah'],
            ],
            'applicant'  => $row,
            'form'       => new ApplicantForm(),
            'values'     => $row,
            'fields'     => self::editableFields(new ApplicantForm(), $row),
            'waves'      => $this->waveOptions((string) $row['academic_year']),
            'duplicates' => [],
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $row = $this->applicants->find($id);

        if ($row === null) {
            return redirect()->route('pendaftar')->with('error', lang('Pendaftaran.notFound'));
        }

        $form            = new ApplicantForm();
        $fields          = [...self::editableFields($form, $row), ...ApplicantForm::STAFF_ONLY];
        [$data, $errors] = $this->validateApplicant($form, $fields);
        $errors += $this->waveError($data, (string) $row['academic_year']);

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data['wave_id'] = empty($data['wave_id']) ? null : (int) $data['wave_id'];
        $updated         = (new ApplicantService())->update($id, $data);

        $redirect = redirect()->route('pendaftar.detail', [$id])->with('message', lang('Pendaftaran.updated'));

        return (int) $updated['possible_duplicate'] === 1 && (int) $row['possible_duplicate'] === 0
            ? $redirect->with('warning', lang('Pendaftaran.nowDuplicate'))
            : $redirect;
    }

    public function status(int $id): RedirectResponse
    {
        $row    = $this->applicants->find($id);
        $status = (string) $this->request->getPost('status');

        if ($row === null) {
            return redirect()->route('pendaftar')->with('error', lang('Pendaftaran.notFound'));
        }

        if (! in_array($status, ApplicantService::STATUSES, true)) {
            return redirect()->route('pendaftar.detail', [$id])->with('error', lang('Pendaftaran.statusInvalid'));
        }

        $note = mb_substr(trim((string) $this->request->getPost('note')), 0, 255);
        (new ApplicantService())->changeStatus($id, $status, $note);

        return redirect()->route('pendaftar.detail', [$id])->with('message', lang('Pendaftaran.statusChanged', [lang('Status.applicant.' . $status)]));
    }

    public function reviewDuplicate(int $id): RedirectResponse
    {
        $row  = $this->applicants->find($id);
        $note = trim((string) $this->request->getPost('note'));

        if ($row === null) {
            return redirect()->route('pendaftar')->with('error', lang('Pendaftaran.notFound'));
        }

        if ($note === '') {
            return redirect()->route('pendaftar.detail', [$id])->with('errors', ['note' => lang('Pendaftaran.reviewNoteRequired')]);
        }

        (new ApplicantService())->reviewDuplicate($id, $note);

        return redirect()->route('pendaftar.detail', [$id])->with('message', lang('Pendaftaran.reviewed'));
    }

    public function privateLink(int $id): RedirectResponse
    {
        if ($this->applicants->find($id) === null) {
            return redirect()->route('pendaftar')->with('error', lang('Pendaftaran.notFound'));
        }

        $token = (new ApplicantService())->renewPrivateLink($id);

        return redirect()->route('pendaftar.detail', [$id])
            ->with('message', lang('Pendaftaran.linkRenewed'))
            ->with('private_link', url_to('tautan', $token));
    }

    public function delete(int $id): RedirectResponse
    {
        $row = $this->applicants->find($id);

        if ($row === null) {
            return redirect()->route('pendaftar')->with('error', lang('Pendaftaran.notFound'));
        }

        $this->applicants->delete($id);

        return redirect()->route('pendaftar')->with('message', lang('Pendaftaran.deleted', [$row['full_name'], $row['registration_number']]));
    }

    /**
     * Fields staff may edit: the visible ones plus hidden ones that already hold a value.
     *
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private static function editableFields(ApplicantForm $form, array $row): array
    {
        return [...ApplicantForm::CORE, ...array_values(array_filter(ApplicantForm::CONFIGURABLE, static fn (string $f): bool => $form->shows($f) || ! empty($row[$f])))];
    }

    /**
     * Wave options for staff: waves of the academic year plus "no wave".
     *
     * @return array<string, string>
     */
    private function waveOptions(?string $year = null): array
    {
        $options = ['' => lang('Pendaftaran.noWave')];

        foreach ($this->waves->forYear($year ?? (string) setting('Pendaftaran.academicYear')) as $wave) {
            $options[(string) $wave['id']] = $wave['name'] . ' (' . tanggal_indo($wave['starts_on']) . ' – ' . tanggal_indo($wave['ends_on']) . ')';
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    private function waveError(array $data, ?string $year = null): array
    {
        if (empty($data['wave_id'])) {
            return [];
        }

        $wave = $this->waves->find((int) $data['wave_id']);

        return $wave !== null && $wave['academic_year'] === ($year ?? (string) setting('Pendaftaran.academicYear'))
            ? []
            : ['wave_id' => lang('Pendaftaran.waveNotFound')];
    }
}
