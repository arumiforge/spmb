<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Tes\Models\TestRoomModel;
use Tests\Support\Tes\TesFixtures;
use Tests\Support\Tes\TesJadwalHelpers;

/**
 * Package A — test rooms: permissions, CRUD, (de)activation, delete only when unused.
 *
 * @internal
 */
final class TesJadwalRuangTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use TesFixtures;
    use TesJadwalHelpers;

    protected $namespace;
    protected $refresh = true;

    public function testPermissions(): void
    {
        $roomId = $this->makeRoom();
        $routes = [
            ['get', 'admin/tes/ruang'],
            ['get', 'admin/tes/ruang/tambah'],
            ['post', 'admin/tes/ruang/tambah'],
            ['get', 'admin/tes/ruang/' . $roomId . '/ubah'],
            ['post', 'admin/tes/ruang/' . $roomId . '/ubah'],
            ['post', 'admin/tes/ruang/' . $roomId . '/nonaktifkan'],
            ['post', 'admin/tes/ruang/' . $roomId . '/aktifkan'],
            ['post', 'admin/tes/ruang/' . $roomId . '/hapus'],
        ];
        $panitia = $this->makeStaff('panitia');

        foreach ($routes as [$method, $url]) {
            $this->resetTesVisitor();
            $this->{$method}($url)->assertRedirectTo(site_url('masuk'));
            $this->actingAs($panitia)->{$method}($url)->assertRedirectTo(site_url('admin/akses-ditolak'));
        }

        $admin = $this->makeStaff('admin');
        $this->actingAs($admin)->get('admin/tes/ruang')->assertSee('Ruang 1');
        $this->actingAs($admin)->get('admin/tes/ruang/tambah')->assertSee('Kapasitas');
        $this->assertNotNull(model(TestRoomModel::class)->find($roomId), 'denied requests change nothing');
    }

    public function testEmptyState(): void
    {
        $this->actingAs($this->makeStaff('admin'))->get('admin/tes/ruang')->assertSee('Belum ada ruang tes');
    }

    public function testAdminCreatesAndEditsRoom(): void
    {
        $admin = $this->makeStaff('admin');
        $this->actingAs($admin)->post('admin/tes/ruang/tambah', [
            'name' => 'Lab Komputer', 'capacity' => '24', 'location' => 'Gedung B', 'sort_order' => '3', 'is_active' => '1',
        ])->assertRedirectTo(site_url('admin/tes/ruang'));

        $room = model(TestRoomModel::class)->where('name', 'Lab Komputer')->first();
        $this->assertSame(['24', 'Gedung B', '3', '1'], [(string) $room['capacity'], $room['location'], (string) $room['sort_order'], (string) $room['is_active']]);
        $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'test_rooms']);

        $this->actingAs($admin)->get('admin/tes/ruang/' . $room['id'] . '/ubah')->assertSee('Lab Komputer');
        $this->actingAs($admin)->post('admin/tes/ruang/' . $room['id'] . '/ubah', [
            'name' => 'Lab Komputer 1', 'capacity' => '30', 'location' => '', 'sort_order' => '', 'is_active' => '0',
        ])->assertRedirect();

        $room = model(TestRoomModel::class)->find($room['id']);
        $this->assertSame(['Lab Komputer 1', '30', null, '0'], [$room['name'], (string) $room['capacity'], $room['location'], (string) $room['is_active']]);
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'test_rooms', 'subject_id' => (string) $room['id']]);
    }

    public function testInvalidRoomsAreRefused(): void
    {
        $admin = $this->makeStaff('admin');
        $this->makeRoom(['name' => 'Aula']);

        $this->actingAs($admin)->post('admin/tes/ruang/tambah', ['name' => '', 'capacity' => '0'])->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/tes/ruang/tambah', ['name' => 'Ruang Besar', 'capacity' => '1000'])->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/tes/ruang/tambah', ['name' => 'aula', 'capacity' => '40'])->assertSessionHas('errors');
        $this->assertStringContainsString('sudah dipakai', session('errors')['name']);
        $this->assertSame(1, model(TestRoomModel::class)->countAllResults());
    }

    public function testDeactivateActivateAndDelete(): void
    {
        $admin  = $this->makeStaff('admin');
        $roomId = $this->makeRoom();

        $this->actingAs($admin)->post('admin/tes/ruang/' . $roomId . '/nonaktifkan')->assertSessionHas('message');
        $this->assertSame('0', (string) model(TestRoomModel::class)->find($roomId)['is_active']);
        $this->actingAs($admin)->post('admin/tes/ruang/' . $roomId . '/aktifkan');
        $this->assertSame('1', (string) model(TestRoomModel::class)->find($roomId)['is_active']);

        $this->actingAs($admin)->post('admin/tes/ruang/' . $roomId . '/hapus')->assertSessionHas('message');
        $this->assertNull(model(TestRoomModel::class)->find($roomId));
        $this->assertNotNull(model(TestRoomModel::class)->withDeleted()->find($roomId), 'soft delete');

        $this->actingAs($admin)->post('admin/tes/ruang/' . $roomId . '/hapus')->assertSessionHas('error');
        $this->actingAs($admin)->get('admin/tes/ruang/999/ubah')->assertRedirectTo(site_url('admin/tes/ruang'));
    }

    public function testRoomWithSessionsCannotBeDeleted(): void
    {
        $roomId = $this->makeRoom(['name' => 'Ruang 7']);
        $this->makeSession(['room_id' => $roomId]);

        $this->actingAs($this->makeStaff('admin'))->post('admin/tes/ruang/' . $roomId . '/hapus')->assertSessionHas('error');
        $this->assertStringContainsString('dipakai di 1 sesi', session('error'));
        $this->assertNotNull(model(TestRoomModel::class)->find($roomId));
    }
}
