<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Tes\Models\TestRoomModel;
use Modules\Tes\Models\TestSessionModel;

/**
 * Test rooms (FR-TEST-01): name, capacity (the default session quota),
 * location shown to applicants, display order. A room used by a session can
 * only be deactivated, not deleted. Writes go through the audited model.
 */
class Ruang extends BaseController
{
    private TestRoomModel $rooms;

    public function __construct()
    {
        $this->rooms = model(TestRoomModel::class);
    }

    public function index(): string
    {
        return view('Modules\Tes\Views\ruang\index', [
            'title'       => lang('TesJadwal.roomsTitle'),
            'subtitle'    => lang('TesJadwal.roomsIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Tes.breadcrumb')], ['label' => lang('TesJadwal.roomsTitle')]],
            'rooms'       => $this->rooms->withSessionCounts(),
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
        $room = $this->rooms->find($id);

        return $room === null ? $this->notFound() : $this->form($room);
    }

    public function update(int $id): RedirectResponse
    {
        $room = $this->rooms->find($id);

        return $room === null ? $this->notFound() : $this->save($room);
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
        $room = $this->rooms->find($id);

        if ($room === null) {
            return $this->notFound();
        }

        $used = $this->rooms->sessionCount($id);

        if ($used > 0) {
            return redirect()->route('tes.ruang')->with('error', lang('TesJadwal.roomInUse', [$room['name'], $used]));
        }

        $this->rooms->delete($id);

        return redirect()->route('tes.ruang')->with('message', lang('TesJadwal.roomDeleted', [$room['name']]));
    }

    private function setActive(int $id, bool $active): RedirectResponse
    {
        $room = $this->rooms->find($id);

        if ($room === null) {
            return $this->notFound();
        }

        if ((int) $room['is_active'] !== ($active ? 1 : 0)) {
            $this->rooms->update($id, ['is_active' => $active ? 1 : 0]);
        }

        return redirect()->route('tes.ruang')->with('message', lang($active ? 'TesJadwal.roomActivated' : 'TesJadwal.roomDeactivated', [$room['name']]));
    }

    private function notFound(): RedirectResponse
    {
        return redirect()->route('tes.ruang')->with('error', lang('TesJadwal.roomNotFound'));
    }

    /**
     * @param array<string, mixed>|null $room
     */
    private function form(?array $room): string
    {
        return view('Modules\Tes\Views\ruang\form', [
            'title'       => lang($room === null ? 'TesJadwal.roomAddTitle' : 'TesJadwal.roomEditTitle'),
            'subtitle'    => $room['name'] ?? null,
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => lang('TesJadwal.roomsTitle'), 'url' => 'admin/tes/ruang'],
                ['label' => $room === null ? 'Tambah' : 'Ubah'],
            ],
            'room' => $room,
        ]);
    }

    /**
     * @param array<string, mixed>|null $room
     */
    private function save(?array $room): RedirectResponse
    {
        $rules = [
            'name'       => ['label' => lang('TesJadwal.roomName'), 'rules' => 'required|max_length[60]'],
            'capacity'   => ['label' => lang('TesJadwal.roomCapacity'), 'rules' => 'required|is_natural_no_zero|less_than_equal_to[' . TestSessionModel::QUOTA_MAX . ']'],
            'location'   => ['label' => lang('TesJadwal.roomLocation'), 'rules' => 'permit_empty|max_length[100]'],
            'sort_order' => ['label' => lang('TesJadwal.roomOrder'), 'rules' => 'permit_empty|is_natural|less_than_equal_to[9999]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'       => trim((string) $this->request->getPost('name')),
            'capacity'   => (int) $this->request->getPost('capacity'),
            'location'   => trim((string) $this->request->getPost('location')) ?: null,
            'sort_order' => (int) $this->request->getPost('sort_order'),
            'is_active'  => $this->request->getPost('is_active') === '1' ? 1 : 0,
        ];

        if ($this->rooms->findByName($data['name'], $room === null ? null : (int) $room['id']) !== null) {
            return redirect()->back()->withInput()->with('errors', ['name' => lang('TesJadwal.roomNameTaken')]);
        }

        if ($room === null) {
            $this->rooms->insert($data);
        } else {
            $this->rooms->update((int) $room['id'], $data);
        }

        return redirect()->route('tes.ruang')->with('message', lang('TesJadwal.roomSaved', [$data['name']]));
    }
}
