<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Tes\Models\TestHolidayModel;
use Modules\Tes\Models\TestSessionModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Tes\TesFixtures;
use Tests\Support\Tes\TesJadwalHelpers;

/**
 * Package A — sessions: permissions, list and filters, add/edit with the
 * validation rules (D-059), confirmation of schedule changes, (de)activate, delete.
 *
 * @internal
 */
final class TesJadwalSesiTest extends CIUnitTestCase
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
        $id     = $this->makeSession();
        $routes = [
            ['get', 'admin/tes/sesi'],
            ['get', 'admin/tes/sesi/tambah'],
            ['post', 'admin/tes/sesi/tambah'],
            ['get', 'admin/tes/sesi/' . $id . '/ubah'],
            ['post', 'admin/tes/sesi/' . $id . '/ubah'],
            ['post', 'admin/tes/sesi/' . $id . '/nonaktifkan'],
            ['post', 'admin/tes/sesi/' . $id . '/aktifkan'],
            ['post', 'admin/tes/sesi/' . $id . '/hapus'],
            ['get', 'admin/tes/sesi/buat-banyak'],
            ['post', 'admin/tes/sesi/buat-banyak/pratinjau'],
            ['post', 'admin/tes/sesi/buat-banyak'],
        ];
        $panitia = $this->makeStaff('panitia');

        foreach ($routes as [$method, $url]) {
            $this->resetTesVisitor();
            $this->{$method}($url)->assertRedirectTo(site_url('masuk'));
            $this->actingAs($panitia)->{$method}($url)->assertRedirectTo(site_url('admin/akses-ditolak'));
        }

        $admin = $this->makeStaff('admin');
        $this->actingAs($admin)->get('admin/tes/sesi?lewat=1')->assertSee('Sabtu, 12 Juni 2027');
        $this->actingAs($admin)->get('admin/tes/sesi/tambah')->assertSee('Jam mulai');
        $this->actingAs($admin)->get('admin/tes/sesi/' . $id . '/ubah')->assertSee('Ubah Sesi Tes');
        $this->assertSame(1, model(TestSessionModel::class)->countAllResults());
    }

    public function testListFiltersAndCounts(): void
    {
        $admin = $this->makeStaff('admin');
        $this->actingAs($admin)->get('admin/tes/sesi')->assertSee('Belum ada sesi tes');

        $roomA = $this->makeRoom(['name' => 'Ruang Mawar']);
        $roomB = $this->makeRoom(['name' => 'Ruang Melati']);
        $s1    = $this->makeSession(['room_id' => $roomA, 'quota' => 2]);
        $this->makeSession(['room_id' => $roomB, 'test_date' => '2027-06-14', 'is_active' => 0]);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $s1);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $s1);

        $all = $this->actingAs($admin)->get('admin/tes/sesi?lewat=1');
        $all->assertSee('Ruang Mawar');
        $all->assertSee('Ruang Melati');
        $all->assertSee('Penuh');
        $all->assertSee('2 sesi · 2 dari 22 kursi terisi');

        $byRoom = $this->actingAs($admin)->get('admin/tes/sesi?ruang=' . $roomB);
        $byRoom->assertSee('Ruang Melati');
        $byRoom->assertDontSee('Gedung A lantai 1 </p>');
        $this->assertStringNotContainsString('Ruang Mawar</p>', (string) $byRoom->getBody());

        $inactive = $this->actingAs($admin)->get('admin/tes/sesi?status=0&tanggal=2027-06-14');
        $this->assertStringContainsString('1 sesi', (string) $inactive->getBody());

        $this->actingAs($admin)->get('admin/tes/sesi?tanggal=2027-01-01')->assertSee('Tidak ada sesi yang cocok');
    }

    public function testAdminAddsSessionWithRoomCapacityAsDefaultQuota(): void
    {
        $roomId = $this->makeRoom(['capacity' => 28]);
        $waveId = $this->makeWave();

        $this->actingAs($this->makeStaff('admin'))->post('admin/tes/sesi/tambah', [
            'test_date' => '2027-06-14', 'starts_at' => '07:30', 'ends_at' => '09:30', 'room_id' => (string) $roomId,
            'quota'     => '', 'label' => 'Sesi 1', 'wave_id' => (string) $waveId, 'notes' => 'Bawa pensil 2B', 'is_active' => '1',
        ])->assertRedirectTo(site_url('admin/tes/sesi') . '?tanggal=2027-06-14');

        $row = model(TestSessionModel::class)->first();
        $this->assertSame(['2027/2028', '2027-06-14', '07:30:00', '09:30:00', '28', (string) $waveId, 'Sesi 1'], [
            $row['academic_year'], $row['test_date'], $row['starts_at'], $row['ends_at'], (string) $row['quota'], (string) $row['wave_id'], $row['label'],
        ]);
        $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'test_sessions']);
    }

    /**
     * @param array<string, string> $override
     */
    #[DataProvider('provideInvalidSessionsAreRefused')]
    public function testInvalidSessionsAreRefused(array $override, string $field, string $message): void
    {
        $roomId = $this->makeRoom();
        $this->makeSession(['room_id' => $roomId, 'test_date' => '2027-06-14', 'starts_at' => '07:30:00', 'ends_at' => '09:30:00']);
        model(TestHolidayModel::class)->insert(['date' => '2027-06-17', 'note' => 'Idul Adha']);

        $this->actingAs($this->makeStaff('admin'))->post('admin/tes/sesi/tambah', $override + [
            'test_date' => '2027-06-14', 'starts_at' => '10:00', 'ends_at' => '12:00', 'room_id' => (string) $roomId, 'quota' => '20', 'is_active' => '1',
        ])->assertSessionHas('errors');

        $this->assertStringContainsString($message, session('errors')[$field] ?? '');
        $this->assertSame(1, model(TestSessionModel::class)->countAllResults());
    }

    /**
     * @return iterable<string, array{array<string, string>, string, string}>
     */
    public static function provideInvalidSessionsAreRefused(): iterable
    {
        yield 'end before start' => [['starts_at' => '10:00', 'ends_at' => '09:00'], 'ends_at', 'setelah jam mulai'];

        yield 'quota zero' => [['quota' => '0'], 'quota', '1 sampai 999'];

        yield 'quota too big' => [['quota' => '1000'], 'quota', '1 sampai 999'];

        yield 'bad date' => [['test_date' => '2027-02-30'], 'test_date', 'Tanggal'];

        yield 'holiday' => [['test_date' => '2027-06-17'], 'test_date', 'hari libur'];

        yield 'overlap' => [['starts_at' => '09:00', 'ends_at' => '11:00'], 'starts_at', 'bertabrakan'];
    }

    public function testSameTimeInAnotherRoomAndBackToBackAreAllowed(): void
    {
        $roomA = $this->makeRoom();
        $roomB = $this->makeRoom();
        $this->makeSession(['room_id' => $roomA]);
        $admin = $this->makeStaff('admin');
        $base  = ['test_date' => '2027-06-12', 'quota' => '20', 'is_active' => '1'];

        $this->actingAs($admin)->post('admin/tes/sesi/tambah', $base + ['starts_at' => '07:30', 'ends_at' => '09:30', 'room_id' => (string) $roomB])->assertSessionMissing('errors');
        $this->actingAs($admin)->post('admin/tes/sesi/tambah', $base + ['starts_at' => '09:30', 'ends_at' => '11:30', 'room_id' => (string) $roomA])->assertSessionMissing('errors');
        $this->assertSame(3, model(TestSessionModel::class)->countAllResults());
    }

    public function testQuotaNeverBelowSeatsTaken(): void
    {
        $id = $this->makeSession(['quota' => 20]);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $id);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $id);
        $this->makeAssignment((int) $this->makeApplicant(['status' => 'cancelled'])['id'], $id);
        $session = model(TestSessionModel::class)->find($id);
        $admin   = $this->makeStaff('admin');
        $post    = ['test_date' => '2027-06-12', 'starts_at' => '07:30', 'ends_at' => '09:30', 'room_id' => (string) $session['room_id'], 'is_active' => '1'];

        $this->actingAs($admin)->post('admin/tes/sesi/' . $id . '/ubah', $post + ['quota' => '1'])->assertSessionHas('errors');
        $this->assertStringContainsString('Sudah ada 2 peserta', session('errors')['quota']);

        $this->resetTesVisitor();
        $this->actingAs($admin)->post('admin/tes/sesi/' . $id . '/ubah', $post + ['quota' => '2'])->assertSessionMissing('errors');
        $this->assertSame('2', (string) model(TestSessionModel::class)->find($id)['quota'], 'cancelled applicants free their seat');
    }

    public function testScheduleChangeWithParticipantsNeedsConfirmation(): void
    {
        $id        = $this->makeSession();
        $applicant = $this->makeApplicant();
        $this->makeAssignment((int) $applicant['id'], $id, ['seat_number' => 4]);
        $session = model(TestSessionModel::class)->find($id);
        $admin   = $this->makeStaff('admin');
        $post    = ['test_date' => '2027-06-14', 'starts_at' => '08:00', 'ends_at' => '10:00', 'room_id' => (string) $session['room_id'], 'quota' => '20', 'is_active' => '1'];

        $this->actingAs($admin)->post('admin/tes/sesi/' . $id . '/ubah', $post)->assertSessionHas('confirmChange');
        $this->assertSame('2027-06-12', model(TestSessionModel::class)->find($id)['test_date'], 'not changed before confirmation');
        $this->assertStringContainsString('tanggal tes, jam mulai, jam selesai', session('confirmChange')['fields']);

        $page = $this->actingAs($admin)->withSession(['confirmChange' => session('confirmChange')])->get('admin/tes/sesi/' . $id . '/ubah');
        $page->assertSee('Peserta perlu diberi tahu');
        $page->assertSee('Ya, Ubah Jadwal Sesi');

        $this->resetTesVisitor();
        $this->actingAs($admin)->post('admin/tes/sesi/' . $id . '/ubah', $post + ['confirm' => '1'])->assertSessionHas('message');
        $row = model(TestSessionModel::class)->find($id);
        $this->assertSame(['2027-06-14', '08:00:00'], [$row['test_date'], $row['starts_at']]);
        $this->seeInDatabase('test_assignments', ['applicant_id' => $applicant['id'], 'session_id' => $id, 'seat_number' => 4]);
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'test_sessions', 'subject_id' => (string) $id]);

        // Label and quota changes do not ask.
        $this->resetTesVisitor();
        $this->actingAs($admin)->post('admin/tes/sesi/' . $id . '/ubah', $post + ['label' => 'Sesi Pagi'])->assertSessionHas('message');
        $this->assertSame('Sesi Pagi', model(TestSessionModel::class)->find($id)['label']);
    }

    public function testDeactivateActivateAndDelete(): void
    {
        $admin = $this->makeStaff('admin');
        $id    = $this->makeSession();

        $this->actingAs($admin)->post('admin/tes/sesi/' . $id . '/nonaktifkan')->assertSessionHas('message');
        $this->assertSame('0', (string) model(TestSessionModel::class)->find($id)['is_active']);
        $this->actingAs($admin)->post('admin/tes/sesi/' . $id . '/aktifkan');
        $this->assertSame('1', (string) model(TestSessionModel::class)->find($id)['is_active']);

        $used = $this->makeSession(['starts_at' => '10:00:00', 'ends_at' => '12:00:00']);
        $this->makeAssignment((int) $this->makeApplicant(['status' => 'cancelled'])['id'], $used);
        $this->actingAs($admin)->post('admin/tes/sesi/' . $used . '/hapus')->assertSessionHas('error');
        $this->assertNotNull(model(TestSessionModel::class)->find($used), 'kept while any applicant is recorded');

        $this->actingAs($admin)->post('admin/tes/sesi/' . $id . '/hapus')->assertSessionHas('message');
        $this->assertNull(model(TestSessionModel::class)->find($id));
        $this->assertNotNull(model(TestSessionModel::class)->withDeleted()->find($id), 'soft delete');
        $this->actingAs($admin)->get('admin/tes/sesi/' . $id . '/ubah')->assertRedirectTo(site_url('admin/tes/sesi'));
    }
}
