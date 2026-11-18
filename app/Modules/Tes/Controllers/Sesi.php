<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use InvalidArgumentException;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Tes\Libraries\SessionGenerator;
use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestRoomModel;
use Modules\Tes\Models\TestSessionModel;

/**
 * Test sessions (FR-TEST-01, D-059): list with filters and seat counts, add,
 * edit, (de)activate, delete, and the generator for many sessions at once.
 * Rules live in TestSessionModel::validateSession(); writes go through the
 * audited model (the generator logs one `sessions_generated` event).
 */
class Sesi extends BaseController
{
    /**
     * Columns whose change moves every participant (asks for confirmation).
     *
     * @var array<string, string> column => language key of its label
     */
    private const SCHEDULE_FIELDS = [
        'test_date' => 'TesJadwal.sessionDate',
        'starts_at' => 'TesJadwal.sessionStart',
        'ends_at'   => 'TesJadwal.sessionEnd',
        'room_id'   => 'TesJadwal.sessionRoom',
    ];

    private TestSessionModel $sessions;

    public function __construct()
    {
        $this->sessions = model(TestSessionModel::class);
    }

    public function index(): string
    {
        $year    = (string) setting('Pendaftaran.academicYear');
        $today   = date('Y-m-d');
        $get     = $this->request->getGet();
        $date    = (string) ($get['tanggal'] ?? '');
        $wave    = (string) ($get['gelombang'] ?? '');
        $room    = (string) ($get['ruang'] ?? '');
        $active  = (string) ($get['status'] ?? '');
        $past    = ($get['lewat'] ?? '') === '1';
        $filters = ['academic_year' => $year];

        if (TestSessionModel::isDate($date)) {
            $filters['date'] = $date;
        } else {
            $date = '';

            if (! $past) {
                $filters['date_from'] = $today;
            }
        }

        if ($wave === 'none' || ctype_digit($wave)) {
            $filters['wave_id'] = $wave;
        }

        if (ctype_digit($room)) {
            $filters['room_id'] = (int) $room;
        }

        if ($active === '1' || $active === '0') {
            $filters['active'] = $active === '1';
        }

        $rows     = $this->sessions->withStats($filters);
        $filtered = $date !== '' || isset($filters['wave_id']) || isset($filters['room_id']) || isset($filters['active']);
        $all      = $filtered    || $past ? null : $this->sessions->where('academic_year', $year)->countAllResults();

        return view('Modules\Tes\Views\sesi\index', [
            'title'       => lang('TesJadwal.sessionsTitle'),
            'subtitle'    => lang('TesJadwal.sessionsIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Tes.breadcrumb')], ['label' => lang('TesJadwal.sessionsTitle')]],
            'sessions'    => $rows,
            'days'        => $this->groupByDate($rows),
            'filters'     => ['tanggal' => $date, 'gelombang' => $wave, 'ruang' => $room, 'status' => $active, 'lewat' => $past ? '1' : ''],
            'filtered'    => $filtered,
            'hasAny'      => $rows !== [] || $all === null || $all > 0,
            'waves'       => $this->waveOptions($year),
            'rooms'       => model(TestRoomModel::class)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll(),
            'today'       => $today,
        ]);
    }

    public function new(): string
    {
        $date = (string) $this->request->getGet('tanggal');
        $room = model(TestRoomModel::class)->active()[0] ?? null;

        return $this->form(null, [
            'test_date' => TestSessionModel::isDate($date) ? $date : '',
            'starts_at' => '07:30',
            'ends_at'   => SessionGenerator::addMinutes('07:30', SessionGenerator::duration()),
            'room_id'   => $room['id'] ?? '',
            'quota'     => '',
            'label'     => '',
            'wave_id'   => '',
            'notes'     => '',
            'is_active' => '1',
        ]);
    }

    public function create(): RedirectResponse
    {
        return $this->save(null);
    }

    public function edit(int $id): RedirectResponse|string
    {
        $session = $this->sessions->find($id);

        if ($session === null) {
            return $this->notFound();
        }

        return $this->form($session, [
            'test_date' => $session['test_date'],
            'starts_at' => substr((string) $session['starts_at'], 0, 5),
            'ends_at'   => substr((string) $session['ends_at'], 0, 5),
            'room_id'   => $session['room_id'],
            'quota'     => $session['quota'],
            'label'     => $session['label'] ?? '',
            'wave_id'   => $session['wave_id'] ?? '',
            'notes'     => $session['notes'] ?? '',
            'is_active' => (string) $session['is_active'],
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $session = $this->sessions->find($id);

        return $session === null ? $this->notFound() : $this->save($session);
    }

    public function activate(int $id): RedirectResponse
    {
        return $this->setActive($id, true);
    }

    public function deactivate(int $id): RedirectResponse
    {
        return $this->setActive($id, false);
    }

    public function delete(int $id): RedirectResponse
    {
        $session = $this->sessions->find($id);

        if ($session === null) {
            return $this->notFound();
        }

        $label = $this->label($session);
        $used  = $this->sessions->assignmentCount($id);

        if ($used > 0) {
            return redirect()->back()->with('error', lang('TesJadwal.sessionInUse', [$label, $used]));
        }

        $this->sessions->delete($id);

        return redirect()->back()->with('message', lang('TesJadwal.sessionDeleted', [$label]));
    }

    public function generator(): string
    {
        $old  = session('_ci_old_input');
        $form = is_array($old) && isset($old['post']['date_from']) ? $this->generatorInput($old['post']) : SessionGenerator::defaults();

        return $this->generatorPage($form, null, []);
    }

    public function preview(): string
    {
        $input   = $this->generatorInput($this->request->getPost());
        $preview = (new SessionGenerator())->preview($input);

        return $this->generatorPage($input, $preview['errors'] === [] ? $preview : null, $preview['errors']);
    }

    public function generate(): RedirectResponse
    {
        $input = $this->generatorInput($this->request->getPost());

        try {
            $count = (new SessionGenerator())->create($input, (int) auth()->id());
        } catch (InvalidArgumentException $e) {
            return redirect()->route('tes.sesi.buat-banyak')->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('tes.sesi')->with('message', lang('TesJadwal.genDone', [$count]));
    }

    /**
     * Sessions grouped by date for the list: date => ['day', 'sessions', 'filled', 'quota'].
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, array<string, mixed>>
     */
    private function groupByDate(array $rows): array
    {
        $days = [];

        foreach ($rows as $row) {
            $date = (string) $row['test_date'];
            $days[$date] ??= ['day' => TestSchedule::dayLabel($date), 'sessions' => [], 'filled' => 0, 'quota' => 0];
            $days[$date]['sessions'][] = $row;
            $days[$date]['filled'] += $row['filled'];
            $days[$date]['quota'] += (int) $row['quota'];
        }

        return $days;
    }

    /**
     * Raw POST of the generator form → SessionGenerator input.
     *
     * @param array<string, mixed> $post
     *
     * @return array<string, mixed>
     */
    private function generatorInput(array $post): array
    {
        $starts = array_values((array) ($post['slot_start'] ?? []));
        $ends   = array_values((array) ($post['slot_end'] ?? []));
        $slots  = [];

        foreach ($starts as $i => $start) {
            $slots[] = ['start' => trim((string) $start), 'end' => trim((string) ($ends[$i] ?? ''))];
        }

        return [
            'date_from' => trim((string) ($post['date_from'] ?? '')),
            'date_to'   => trim((string) ($post['date_to'] ?? '')),
            'weekdays'  => array_map('intval', (array) ($post['weekdays'] ?? [])),
            'slots'     => $slots,
            'rooms'     => array_map('intval', (array) ($post['rooms'] ?? [])),
            'wave_id'   => ($post['wave_id'] ?? '') === '' ? null : (int) $post['wave_id'],
            'label'     => trim((string) ($post['label'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed>      $form
     * @param array<string, mixed>|null $preview
     * @param array<string, string>     $errors
     */
    private function generatorPage(array $form, ?array $preview, array $errors): string
    {
        $year = (string) setting('Pendaftaran.academicYear');

        // Times are shown as HH:MM in the form.
        $form['slots'] = array_map(static fn (array $s): array => ['start' => substr($s['start'], 0, 5), 'end' => substr($s['end'], 0, 5)], $form['slots'] ?: [['start' => '', 'end' => '']]);

        return view('Modules\Tes\Views\sesi\generator', [
            'title'       => lang('TesJadwal.genTitle'),
            'subtitle'    => lang('TesJadwal.genIntro'),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => lang('TesJadwal.sessionsTitle'), 'url' => 'admin/tes/sesi'],
                ['label' => lang('TesJadwal.genTitle')],
            ],
            'form'      => $form,
            'preview'   => $preview,
            'genErrors' => $errors,
            'rooms'     => model(TestRoomModel::class)->active(),
            'waves'     => $this->waveOptions($year),
            'duration'  => SessionGenerator::duration(),
        ]);
    }

    private function setActive(int $id, bool $active): RedirectResponse
    {
        $session = $this->sessions->find($id);

        if ($session === null) {
            return $this->notFound();
        }

        if ((int) $session['is_active'] !== ($active ? 1 : 0)) {
            $this->sessions->update($id, ['is_active' => $active ? 1 : 0]);
        }

        return redirect()->back()->with('message', lang($active ? 'TesJadwal.sessionActivated' : 'TesJadwal.sessionDeactivated', [$this->label($session)]));
    }

    private function notFound(): RedirectResponse
    {
        return redirect()->route('tes.sesi')->with('error', lang('TesJadwal.sessionNotFound'));
    }

    /**
     * "Sesi 1 · Sabtu, 12 Juni 2027 · 07.30–09.30 WIB · Ruang 1".
     *
     * @param array<string, mixed> $session
     */
    private function label(array $session): string
    {
        $room = model(TestRoomModel::class)->withDeleted()->find((int) $session['room_id']);
        $line = TestSchedule::sessionLabel($session + ['room_name' => $room['name'] ?? '']);

        return ($session['label'] ?? '') !== '' ? $session['label'] . ' · ' . $line : $line;
    }

    /**
     * Waves of the academic year for selects: id => name.
     *
     * @return array<int, string>
     */
    private function waveOptions(string $year): array
    {
        $options = [];

        foreach (model(WaveModel::class)->forYear($year) as $wave) {
            $options[(int) $wave['id']] = (string) $wave['name'];
        }

        return $options;
    }

    /**
     * @param array<string, mixed>|null $session
     * @param array<string, mixed>      $values
     */
    private function form(?array $session, array $values): string
    {
        $rooms = model(TestRoomModel::class)->active();

        // Keep the current room selectable even when it was deactivated later.
        if ($session !== null && ! in_array((int) $session['room_id'], array_map('intval', array_column($rooms, 'id')), true)) {
            $current = model(TestRoomModel::class)->find((int) $session['room_id']);

            if ($current !== null) {
                $rooms[] = $current;
            }
        }

        $stats = $session === null ? null : $this->sessions->findWithStats((int) $session['id']);

        return view('Modules\Tes\Views\sesi\form', [
            'title'       => lang($session === null ? 'TesJadwal.sessionAddTitle' : 'TesJadwal.sessionEditTitle'),
            'subtitle'    => $session === null ? lang('TesJadwal.yearLabel', [setting('Pendaftaran.academicYear')]) : $this->label($session),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => lang('TesJadwal.sessionsTitle'), 'url' => 'admin/tes/sesi'],
                ['label' => $session === null ? 'Tambah' : 'Ubah'],
            ],
            'session' => $session,
            'values'  => $values,
            'rooms'   => $rooms,
            'waves'   => $this->waveOptions((string) ($session['academic_year'] ?? setting('Pendaftaran.academicYear'))),
            'filled'  => $stats['filled'] ?? 0,
            'confirm' => session('confirmChange'),
        ]);
    }

    /**
     * @param array<string, mixed>|null $session
     */
    private function save(?array $session): RedirectResponse
    {
        $post   = $this->request->getPost();
        $roomId = (int) ($post['room_id'] ?? 0);
        $room   = model(TestRoomModel::class)->find($roomId);
        $quota  = trim((string) ($post['quota'] ?? ''));
        $data   = [
            'academic_year' => $session['academic_year'] ?? (string) setting('Pendaftaran.academicYear'),
            'wave_id'       => ($post['wave_id'] ?? '') === '' ? null : (int) $post['wave_id'],
            'test_date'     => trim((string) ($post['test_date'] ?? '')),
            'starts_at'     => trim((string) ($post['starts_at'] ?? '')),
            'ends_at'       => trim((string) ($post['ends_at'] ?? '')),
            'room_id'       => $roomId,
            'quota'         => $quota === '' && $room !== null ? min((int) $room['capacity'], TestSessionModel::QUOTA_MAX) : $quota,
            'label'         => mb_substr(trim((string) ($post['label'] ?? '')), 0, 60) ?: null,
            'is_active'     => ($post['is_active'] ?? '') === '1' ? 1 : 0,
            'notes'         => mb_substr(trim((string) ($post['notes'] ?? '')), 0, 255) ?: null,
        ];

        $errors = $this->sessions->validateSession($data, $session);

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data['quota']     = (int) $data['quota'];
        $data['starts_at'] = TestSessionModel::time($data['starts_at']);
        $data['ends_at']   = TestSessionModel::time($data['ends_at']);

        if ($session !== null) {
            $changed = [];

            foreach (self::SCHEDULE_FIELDS as $field => $key) {
                if ((string) $session[$field] !== (string) $data[$field]) {
                    $changed[] = mb_strtolower(lang($key));
                }
            }

            $filled = $this->sessions->seatsTaken((int) $session['id']);

            if ($changed !== [] && $filled > 0 && ($post['confirm'] ?? '') !== '1') {
                return redirect()->back()->withInput()->with('confirmChange', ['count' => $filled, 'fields' => implode(', ', $changed)]);
            }

            $this->sessions->update((int) $session['id'], $data);
        } else {
            $this->sessions->insert($data);
        }

        return redirect()->to(url_to('tes.sesi') . '?tanggal=' . $data['test_date'])
            ->with('message', lang('TesJadwal.sessionSaved', [$this->label($data)]));
    }
}
