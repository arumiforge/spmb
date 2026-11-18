<?php

declare(strict_types=1);

namespace Modules\Referensi\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Referensi\Models\SchoolModel;

/**
 * Previous-school list: search, filter, add, edit, deactivate (referensi.kelola).
 */
class Sekolah extends BaseController
{
    private const PER_PAGE = 25;

    /**
     * @var list<string>
     */
    private const FIELDS = ['npsn', 'name', 'level', 'ownership', 'province', 'regency', 'district', 'village', 'address'];

    private SchoolModel $schools;

    public function __construct()
    {
        $this->schools = model(SchoolModel::class);
    }

    public function index(): string
    {
        $q       = trim((string) $this->request->getGet('q'));
        $level   = (string) $this->request->getGet('jenjang');
        $regency = trim((string) $this->request->getGet('kabupaten'));
        $status  = (string) $this->request->getGet('status');

        $builder = $this->schools;

        if ($q !== '') {
            $words = SchoolModel::searchWords($q);

            foreach ($words === [] ? [mb_strtolower($q)] : $words as $word) {
                $builder->groupStart()
                    ->like('name', $word)
                    ->orLike('npsn', $word, 'after')
                    ->orLike('district', $word)
                    ->orLike('regency', $word)
                    ->groupEnd();
            }
        }

        if (in_array($level, SchoolModel::LEVELS, true)) {
            $builder->where('level', $level);
        }

        if ($regency !== '') {
            $builder->where('regency', $regency);
        }

        if ($status === 'aktif') {
            $builder->where('is_active', 1);
        } elseif ($status === 'nonaktif') {
            $builder->where('is_active', 0);
        }

        $rows = $builder->orderBy('name', 'ASC')->orderBy('id', 'ASC')->paginate(self::PER_PAGE);

        return view('Modules\Referensi\Views\sekolah\index', [
            'title'       => lang('Referensi.listTitle'),
            'subtitle'    => lang('Referensi.listIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Sekolah Asal']],
            'schools'     => $rows,
            'pager'       => $this->schools->pager,
            'regencies'   => $this->schools->regencies(),
            'filters'     => ['q' => $q, 'jenjang' => $level, 'kabupaten' => $regency, 'status' => $status],
            'unmapped'    => SekolahLain::unmappedCount(),
        ]);
    }

    public function new(): string
    {
        return $this->form(null);
    }

    public function create(): RedirectResponse
    {
        $data = $this->posted();
        $data += ['source' => 'manual', 'is_active' => 1];

        if (! $this->schools->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->schools->errors());
        }

        return redirect()->route('referensi.sekolah')->with('message', lang('Referensi.created', [$data['name']]));
    }

    public function edit(int $id): string
    {
        return $this->form($this->findOr404($id));
    }

    public function update(int $id): RedirectResponse
    {
        $this->findOr404($id);
        $data = $this->posted();

        // is_unique needs the id placeholder value.
        if (! $this->schools->update($id, $data + ['id' => $id])) {
            return redirect()->back()->withInput()->with('errors', $this->schools->errors());
        }

        return redirect()->route('referensi.sekolah.ubah', [$id])->with('message', lang('Referensi.updated', [$data['name']]));
    }

    public function deactivate(int $id): RedirectResponse
    {
        $school = $this->findOr404($id);
        $this->schools->skipValidation()->update($id, ['is_active' => 0]);

        return redirect()->route('referensi.sekolah.ubah', [$id])->with('message', lang('Referensi.deactivated', [$school['name']]));
    }

    public function activate(int $id): RedirectResponse
    {
        $school = $this->findOr404($id);
        $this->schools->skipValidation()->update($id, ['is_active' => 1]);

        return redirect()->route('referensi.sekolah.ubah', [$id])->with('message', lang('Referensi.activated', [$school['name']]));
    }

    /**
     * @param array<string, mixed>|null $school
     */
    private function form(?array $school): string
    {
        $usedBy = 0;

        if ($school !== null && db_connect()->tableExists('applicants')) {
            $usedBy = db_connect()->table('applicants')->where('previous_school_id', $school['id'])->where('deleted_at', null)->countAllResults();
        }

        return view('Modules\Referensi\Views\sekolah\form', [
            'title'       => $school === null ? lang('Referensi.addTitle') : lang('Referensi.editTitle'),
            'subtitle'    => $school['name'] ?? null,
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Sekolah Asal', 'url' => 'admin/referensi/sekolah'],
                ['label' => $school === null ? 'Tambah' : 'Ubah'],
            ],
            'school'    => $school,
            'usedBy'    => $usedBy,
            'provinces' => array_values(array_map('strval', (array) setting('Referensi.provinces'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function posted(): array
    {
        $data = [];

        foreach (self::FIELDS as $field) {
            $data[$field] = (string) $this->request->getPost($field);
        }

        $data['npsn'] = preg_replace('/\s+/', '', $data['npsn']);

        /** @var array<string, mixed> */
        return SchoolModel::normalise($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function findOr404(int $id): array
    {
        $school = $this->schools->find($id);

        if ($school === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $school;
    }
}
