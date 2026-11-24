<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\TranslationInput;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Pendaftaran\Models\WaveModel;

/**
 * Registration waves per academic year (FR-REG-04). Waves of one year never
 * overlap; a wave that already has applicants cannot be deleted.
 */
class Gelombang extends BaseController
{
    private WaveModel $waves;

    public function __construct()
    {
        $this->waves = model(WaveModel::class);
    }

    public function index(): string
    {
        $current = (string) setting('Pendaftaran.academicYear');
        $years   = array_values(array_unique([$current, ...$this->waves->years()]));
        rsort($years);
        $year = (string) $this->request->getGet('tahun');
        $year = in_array($year, $years, true) ? $year : $current;
        $rows = $this->waves->forYear($year);

        foreach ($rows as &$row) {
            $row['applicants'] = $this->waves->applicantCount((int) $row['id']);
            $row['state']      = self::state($row);
        }
        unset($row);

        return view('Modules\Pendaftaran\Views\admin\gelombang\index', [
            'title'       => lang('Pendaftaran.wavesTitle'),
            'subtitle'    => lang('Pendaftaran.wavesIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Gelombang']],
            'waves'       => $rows,
            'years'       => $years,
            'year'        => $year,
            'currentYear' => $current,
        ]);
    }

    public function new(): string
    {
        return $this->form(null);
    }

    public function create(): RedirectResponse
    {
        return $this->save(null);
    }

    public function edit(int $id): RedirectResponse|string
    {
        $wave = $this->waves->find($id);

        if ($wave === null) {
            return redirect()->route('gelombang')->with('error', lang('Pendaftaran.waveNotFound'));
        }

        return $this->form($wave);
    }

    public function update(int $id): RedirectResponse
    {
        $wave = $this->waves->find($id);

        if ($wave === null) {
            return redirect()->route('gelombang')->with('error', lang('Pendaftaran.waveNotFound'));
        }

        return $this->save($wave);
    }

    public function delete(int $id): RedirectResponse
    {
        $wave = $this->waves->find($id);

        if ($wave === null) {
            return redirect()->route('gelombang')->with('error', lang('Pendaftaran.waveNotFound'));
        }

        $used = db_connect()->table('applicants')->where('wave_id', $id)->countAllResults();

        if ($used > 0) {
            return redirect()->route('gelombang')->with('error', lang('Pendaftaran.waveInUse', [$wave['name'], $used]));
        }

        $this->waves->delete($id);

        return redirect()->to(url_to('gelombang') . '?tahun=' . rawurlencode((string) $wave['academic_year']))
            ->with('message', lang('Pendaftaran.waveDeleted', [$wave['name']]));
    }

    /**
     * open | upcoming | finished | inactive | full (for the badge).
     *
     * @param array<string, mixed> $wave
     */
    public static function state(array $wave, ?string $today = null): string
    {
        $today ??= date('Y-m-d');

        if ((int) $wave['is_active'] !== 1) {
            return 'inactive';
        }

        if ($wave['ends_on'] < $today) {
            return 'finished';
        }

        if ($wave['starts_on'] > $today) {
            return 'upcoming';
        }

        return model(WaveModel::class)->isFull($wave) ? 'full' : 'open';
    }

    /**
     * @param array<string, mixed>|null $wave
     */
    private function form(?array $wave): string
    {
        return view('Modules\Pendaftaran\Views\admin\gelombang\form', [
            'title'    => lang($wave === null ? 'Pendaftaran.waveAddTitle' : 'Pendaftaran.waveEditTitle'),
            'subtitle' => $wave === null
                ? lang('Pendaftaran.waveYear') . ' ' . setting('Pendaftaran.academicYear')
                : $wave['name'] . ' · ' . lang('Pendaftaran.waveYear') . ' ' . $wave['academic_year'],
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Gelombang', 'url' => 'admin/pengaturan/gelombang'],
                ['label' => $wave === null ? 'Tambah' : 'Ubah'],
            ],
            'wave' => $wave,
        ]);
    }

    /**
     * @param array<string, mixed>|null $wave
     */
    private function save(?array $wave): RedirectResponse
    {
        $rules = [
            'name'         => ['label' => lang('Pendaftaran.waveName'), 'rules' => 'required|max_length[60]'],
            'starts_on'    => ['label' => lang('Pendaftaran.waveStartsOn'), 'rules' => 'required|valid_date[Y-m-d]'],
            'ends_on'      => ['label' => lang('Pendaftaran.waveEndsOn'), 'rules' => 'required|valid_date[Y-m-d]'],
            'quota'        => ['label' => lang('Pendaftaran.waveQuota'), 'rules' => 'permit_empty|is_natural_no_zero|less_than[100000]'],
            'quota_mode'   => ['label' => lang('Pendaftaran.waveQuotaMode'), 'rules' => 'required|in_list[none,close,online_limit]'],
            'online_quota' => ['label' => lang('Pendaftaran.waveOnlineQuota'), 'rules' => 'permit_empty|is_natural_no_zero|less_than[100000]'],
            'description'  => ['label' => lang('Pendaftaran.waveDescription'), 'rules' => 'permit_empty|max_length[1000]'],
        ];

        // Optional English/Arabic versions shown on the public pages (D-109).
        [$translations, $translationRules] = TranslationInput::read($this->request, [
            'name'        => ['max' => 60],
            'description' => ['max' => 1000, 'multiline' => true],
        ]);

        if (! $this->validate($rules + $translationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $year = $wave['academic_year'] ?? (string) setting('Pendaftaran.academicYear');
        $data = [
            'academic_year' => $year,
            'name'          => trim((string) $this->request->getPost('name')),
            'starts_on'     => (string) $this->request->getPost('starts_on'),
            'ends_on'       => (string) $this->request->getPost('ends_on'),
            'quota'         => ($q = trim((string) $this->request->getPost('quota'))) === '' ? null : (int) $q,
            'quota_mode'    => (string) $this->request->getPost('quota_mode'),
            'online_quota'  => ($o = trim((string) $this->request->getPost('online_quota'))) === '' ? null : (int) $o,
            'description'   => trim((string) $this->request->getPost('description')) ?: null,
            'is_active'     => $this->request->getPost('is_active') === '1' ? 1 : 0,
        ] + $translations;

        if ($data['quota_mode'] === 'close' && $data['quota'] === null) {
            return redirect()->back()->withInput()->with('errors', ['quota' => lang('Pendaftaran.waveQuotaNeeded')]);
        }

        if ($data['quota_mode'] === 'online_limit' && $data['online_quota'] === null) {
            return redirect()->back()->withInput()->with('errors', ['online_quota' => lang('Pendaftaran.waveOnlineQuotaNeeded')]);
        }

        if ($data['quota_mode'] !== 'online_limit') {
            $data['online_quota'] = null;
        }

        if ($data['ends_on'] < $data['starts_on']) {
            return redirect()->back()->withInput()->with('errors', ['ends_on' => lang('Pendaftaran.waveEndBeforeStart')]);
        }

        $other = $this->waves->overlapping($year, $data['starts_on'], $data['ends_on'], $wave === null ? null : (int) $wave['id']);

        if ($other !== null) {
            return redirect()->back()->withInput()->with('errors', ['starts_on' => lang('Pendaftaran.waveOverlap', [
                $other['name'], tanggal_indo($other['starts_on']), tanggal_indo($other['ends_on']),
            ])]);
        }

        if ($wave === null) {
            $this->waves->insert($data);
        } else {
            $this->waves->update((int) $wave['id'], $data);
        }

        return redirect()->to(url_to('gelombang') . '?tahun=' . rawurlencode($year))
            ->with('message', lang('Pendaftaran.waveSaved', [$data['name']]));
    }
}
