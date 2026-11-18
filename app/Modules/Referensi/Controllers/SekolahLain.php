<?php

declare(strict_types=1);

namespace Modules\Referensi\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Referensi\Models\SchoolModel;

/**
 * Schools typed by applicants because they were not in the list ("Sekolah lain").
 * Staff map each typed name to a list entry (or add it to the list); the
 * applicants are updated through ApplicantModel, so every change is audited.
 */
class SekolahLain extends BaseController
{
    private const PER_PAGE = 25;

    /**
     * Number of applicants still waiting for a mapping (0 when Pendaftaran has no table yet).
     */
    public static function unmappedCount(): int
    {
        $db = db_connect();

        if (! $db->tableExists('applicants')) {
            return 0;
        }

        return self::unmapped($db->table('applicants'))->countAllResults();
    }

    public function index(): string
    {
        $builder = self::unmapped(db_connect()->table('applicants'))
            ->select('MIN(TRIM(previous_school_name)) AS name, COUNT(*) AS total')
            ->groupBy('TRIM(previous_school_name)')
            ->orderBy('total', 'DESC')
            ->orderBy('name', 'ASC');

        $page   = max(1, (int) $this->request->getGet('page'));
        $total  = count((clone $builder)->get()->getResultArray());
        $groups = $builder->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)->get()->getResultArray();

        return view('Modules\Referensi\Views\sekolah_lain\index', [
            'title'       => lang('Referensi.othersTitle'),
            'subtitle'    => lang('Referensi.othersIntro'),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Sekolah Asal', 'url' => 'admin/referensi/sekolah'],
                ['label' => 'Sekolah Lain'],
            ],
            'groups'     => $groups,
            'groupTotal' => $total,
            'links'      => service('pager')->makeLinks($page, self::PER_PAGE, $total),
        ]);
    }

    public function map(): RedirectResponse|string
    {
        $name = $this->typedName((string) $this->request->getGet('nama'));

        if ($name === '') {
            return redirect()->route('referensi.lain');
        }

        $count = count($this->applicantIds($name));

        if ($count === 0) {
            return redirect()->route('referensi.lain')->with('warning', lang('Referensi.mapNothing'));
        }

        $q          = trim((string) ($this->request->getGet('q') ?? $name));
        $candidates = model(SchoolModel::class)->search($q);

        return view('Modules\Referensi\Views\sekolah_lain\map', [
            'title'       => lang('Referensi.mapTitle'),
            'subtitle'    => $name,
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Sekolah Asal', 'url' => 'admin/referensi/sekolah'],
                ['label' => 'Sekolah Lain', 'url' => 'admin/referensi/sekolah-lain'],
                ['label' => 'Petakan'],
            ],
            'map' => [
                'name'       => $name,
                'count'      => $count,
                'q'          => $q,
                'candidates' => $candidates,
                'provinces'  => array_values(array_map('strval', (array) setting('Referensi.provinces'))),
            ],
        ]);
    }

    public function assign(): RedirectResponse
    {
        $name     = $this->typedName((string) $this->request->getPost('nama'));
        $schoolId = (int) $this->request->getPost('school_id');
        $school   = $schoolId > 0 ? model(SchoolModel::class)->find($schoolId) : null;

        if ($school === null) {
            return redirect()->back()->with('error', lang('Referensi.mapChooseFirst'));
        }

        return $this->mapTo($name, $school);
    }

    public function addAndAssign(): RedirectResponse
    {
        $name    = $this->typedName((string) $this->request->getPost('nama'));
        $schools = model(SchoolModel::class);
        $data    = [];

        foreach (['name', 'level', 'ownership', 'province', 'regency', 'district'] as $field) {
            $data[$field] = (string) $this->request->getPost($field);
        }

        $data = SchoolModel::normalise($data) + ['source' => 'manual', 'is_active' => 1];

        if (! $schools->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $schools->errors());
        }

        return $this->mapTo($name, $schools->find($schools->getInsertID()));
    }

    /**
     * Points every unmapped applicant who typed $name to $school.
     *
     * @param array<string, mixed> $school
     */
    private function mapTo(string $name, array $school): RedirectResponse
    {
        $ids = $this->applicantIds($name);

        if ($ids === []) {
            return redirect()->route('referensi.lain')->with('warning', lang('Referensi.mapNothing'));
        }

        $applicants = model(ApplicantModel::class);

        foreach ($ids as $id) {
            $applicants->update($id, ['previous_school_id' => (int) $school['id']]);
        }

        service('audit')->log('school_mapped', 'ref_sekolah', (int) $school['id'], ['previous_school_name' => $name], [
            'previous_school_id' => (int) $school['id'],
            'applicants'         => count($ids),
        ], $name . ' → ' . $school['name']);

        return redirect()->route('referensi.lain')
            ->with('message', lang('Referensi.mapped', [count($ids), $school['name']]));
    }

    /**
     * @return list<int>
     */
    private function applicantIds(string $name): array
    {
        $rows = self::unmapped(db_connect()->table('applicants'))
            ->select('id')
            ->where('TRIM(previous_school_name)', $name)
            ->get()->getResultArray();

        return array_map('intval', array_column($rows, 'id'));
    }

    private function typedName(string $name): string
    {
        return mb_substr(trim($name), 0, 150);
    }

    /**
     * @template T of \CodeIgniter\Database\BaseBuilder
     *
     * @param T $builder
     *
     * @return T
     */
    private static function unmapped($builder)
    {
        return $builder->where('previous_school_id', null)
            ->where('previous_school_name IS NOT NULL')
            ->where("TRIM(previous_school_name) != ''")
            ->where('deleted_at', null);
    }
}
