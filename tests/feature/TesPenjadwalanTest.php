<?php

declare(strict_types=1);

use App\Libraries\ApplicantAccess;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Services\Scheduler;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Tes\TesFixtures;

/**
 * Package B pages: allocation, participants, giving/moving/removing a seat,
 * schedule change log and the applicant panels (FR-TEST-02, FR-TEST-04, D-062).
 *
 * @internal
 */
final class TesPenjadwalanTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use TesFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $this->resetServices();
        service('routes')->loadRoutes();
        setting('Pendaftaran.academicYear', '2027/2028');
    }

    protected function tearDown(): void
    {
        foreach (['Tes.scheduleVisible', 'Pendaftaran.academicYear'] as $key) {
            setting()->forget($key);
        }

        parent::tearDown();
    }

    /**
     * A staff member of $group, or a signed-in user without any group ('').
     */
    private function staffOrNobody(string $group): User
    {
        if ($group !== '') {
            return $this->makeStaff($group);
        }

        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'tanpaperan', 'email' => 'tanpa-peran@example.test', 'password' => 'Rahasia-Kuat-2027']));

        return $users->findById($users->getInsertID());
    }

    private function panel(string $name, array $applicant): string
    {
        return trim((string) preg_replace('/<!--.*?-->/s', '', view('Modules\\Tes\\Views\\panels\\' . $name, ['applicant' => $applicant], ['saveData' => false])));
    }

    #[DataProvider('providePagePermissions')]
    public function testPagePermissions(string $route, string $allowed, string $denied): void
    {
        $applicant = $this->makeApplicant();
        $url       = str_replace('{id}', (string) $applicant['id'], $route);

        $this->get($url)->assertRedirectTo(site_url('masuk'));
        $this->actingAs($this->staffOrNobody($denied))->get($url)->assertRedirectTo(site_url('admin/akses-ditolak'));

        $_SESSION = [];
        $ok       = $this->actingAs($this->makeStaff($allowed))->get($url);
        $ok->assertOK();
        $ok->assertSee('Dasbor');
    }

    // --- Permissions -------------------------------------------------------

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function providePagePermissions(): iterable
    {
        // route, allowed group, denied group ('' = signed in without a group)
        yield 'allocation' => ['admin/tes/penjadwalan', 'admin', 'panitia'];

        yield 'participants' => ['admin/tes/peserta', 'panitia', ''];

        yield 'change log' => ['admin/tes/riwayat-jadwal', 'panitia', ''];

        yield 'seat form' => ['admin/pendaftar/{id}/tes', 'panitia', ''];
    }

    #[DataProvider('provideWritePermissions')]
    public function testWritePermissions(string $route, string $allowed, string $denied): void
    {
        $applicant = $this->makeApplicant();
        $url       = str_replace('{id}', (string) $applicant['id'], $route);

        $this->post($url, [])->assertRedirectTo(site_url('masuk'));
        $this->actingAs($this->staffOrNobody($denied))->post($url, [])->assertRedirectTo(site_url('admin/akses-ditolak'));

        $_SESSION = [];
        $this->actingAs($this->makeStaff($allowed))->post($url, [])->assertRedirect();
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideWritePermissions(): iterable
    {
        yield 'run allocation' => ['admin/tes/penjadwalan', 'admin', 'panitia'];

        yield 'give or move a seat' => ['admin/pendaftar/{id}/tes', 'panitia', ''];

        yield 'remove a seat' => ['admin/pendaftar/{id}/tes/hapus', 'admin', 'panitia'];
    }

    // --- Allocation --------------------------------------------------------

    public function testPreviewShowsThePlanWithoutSaving(): void
    {
        $this->makeSession(['quota' => 1]);
        $this->makeApplicant();
        $this->makeApplicant();

        $result = $this->actingAs($this->makeStaff('admin'))->get('admin/tes/penjadwalan?lihat=1&mulai=2027-06-01');

        $result->assertOK();
        $result->assertSee('1 pendaftar akan mendapat jadwal');
        $result->assertSee('1 pendaftar belum kebagian kursi');
        $result->assertSee('PMB-2027-0002');
        $result->assertSee('Jadwalkan Sekarang');
        $this->assertSame(0, db_connect()->table('test_assignments')->countAllResults());
    }

    public function testRunPlacesAndReports(): void
    {
        $this->makeSession(['quota' => 5]);
        $this->makeApplicant();
        $this->makeApplicant();

        $result = $this->actingAs($this->makeStaff('admin'))->post('admin/tes/penjadwalan', ['gelombang' => '', 'mulai' => '2027-06-01']);

        $result->assertRedirectTo(site_url('admin/tes/penjadwalan'));
        $result->assertSessionHas('message', '2 pendaftar berhasil dijadwalkan.');
        $this->assertSame(2, db_connect()->table('test_assignments')->countAllResults());

        $again = $this->post('admin/tes/penjadwalan', ['gelombang' => '', 'mulai' => '2027-06-01']);
        $again->assertSessionHas('warning', lang('TesPenjadwalan.allocNothing'));
    }

    public function testRunRefusesABadDate(): void
    {
        $this->makeSession();
        $this->makeApplicant();

        $result = $this->actingAs($this->makeStaff('admin'))->post('admin/tes/penjadwalan', ['mulai' => '12/06/2027']);

        $result->assertSessionHas('error', lang('TesPenjadwalan.err.dateInvalid'));
        $this->assertSame(0, db_connect()->table('test_assignments')->countAllResults());
    }

    // --- Participants and log ----------------------------------------------

    public function testParticipantsListAndFilters(): void
    {
        $session   = $this->makeSession();
        $scheduled = $this->makeApplicant(['full_name' => 'Ahmad Fauzi']);
        $this->makeAssignment((int) $scheduled['id'], $session, ['seat_number' => 1, 'status' => 'present']);
        $this->makeApplicant(['full_name' => 'Bunga Lestari']);
        $this->makeApplicant(['full_name' => 'Citra Dibatalkan', 'status' => 'cancelled']);
        $this->actingAs($this->makeStaff('panitia'));

        $all = $this->get('admin/tes/peserta');
        $all->assertSee('Ahmad Fauzi');
        $all->assertSee('Bunga Lestari');
        $all->assertDontSee('Citra Dibatalkan');
        $all->assertSee('2 peserta');

        $unscheduled = $this->get('admin/tes/peserta?status=unscheduled');
        $unscheduled->assertDontSee('Ahmad Fauzi');
        $unscheduled->assertSee('Bunga Lestari');

        $bySession = $this->get('admin/tes/peserta?sesi=' . $session . '&status=present&tanggal=2027-06-12');
        $bySession->assertSee('Ahmad Fauzi');
        $bySession->assertDontSee('Bunga Lestari');

        $this->get('admin/tes/peserta?q=tidak-ada')->assertSee('Peserta tidak ditemukan');
    }

    public function testChangeLogShowsWhoFromToAndWhy(): void
    {
        $staff = $this->makeStaff('panitia');
        $from  = $this->makeSession(['room_id' => $this->makeRoom(['name' => 'Ruang Mawar'])]);
        $to    = $this->makeSession(['test_date' => '2027-06-13', 'room_id' => $this->makeRoom(['name' => 'Ruang Melati'])]);
        $a     = $this->makeApplicant(['full_name' => 'Dewi Sartika']);
        $b     = $this->makeApplicant(['full_name' => 'Eko Prasetyo']);
        (new Scheduler())->allocate(['from_date' => '2027-06-01']);
        (new Scheduler())->move((int) $a['id'], $to, (int) $staff->id, 'Ada acara keluarga');

        $result = $this->actingAs($staff)->get('admin/tes/riwayat-jadwal');
        $result->assertOK();
        $result->assertSee('Dewi Sartika');
        $result->assertSee('Ada acara keluarga');
        $result->assertSee('Ruang Mawar');
        $result->assertSee('Ruang Melati');
        $result->assertSee('Dipindah jadwal');
        $result->assertSee('Sistem');
        $result->assertSee('3 perubahan');

        $moved = $this->get('admin/tes/riwayat-jadwal?jenis=moved');
        $moved->assertSee('Dewi Sartika');
        $moved->assertDontSee('Eko Prasetyo');
        $this->get('admin/tes/riwayat-jadwal?q=Eko')->assertDontSee('Dewi Sartika');
        $this->get('admin/tes/riwayat-jadwal?dari=2030-01-01')->assertSee('Peserta tidak ditemukan');
        $this->assertNotSame($from, $to);
        $this->assertNotEmpty($b);
    }

    // --- One applicant's seat -----------------------------------------------

    public function testGiveMoveAndRemoveASeat(): void
    {
        $first  = $this->makeSession();
        $second = $this->makeSession(['test_date' => '2027-06-13']);
        $a      = $this->makeApplicant();
        $url    = 'admin/pendaftar/' . $a['id'] . '/tes';
        $this->actingAs($this->makeStaff('admin'));

        $form = $this->get($url);
        $form->assertSee('Beri Jadwal Tes');
        $form->assertSee('sisa 20 kursi');

        $this->post($url, ['sesi' => $first, 'alasan' => ''])
            ->assertRedirectTo(site_url('admin/pendaftar/' . $a['id']) . '#jadwal-tes');
        $this->assertSame((string) $first, (string) model(TestAssignmentModel::class)->forApplicant((int) $a['id'])['session_id']);

        $this->get($url)->assertSee('Pindah Jadwal Tes');

        $refused = $this->post($url, ['sesi' => $second, 'alasan' => 'x']);
        $refused->assertRedirectTo(site_url($url));
        $refused->assertSessionHas('error', lang('TesPenjadwalan.err.reason', [5]));

        $this->post($url, ['sesi' => $second, 'alasan' => 'Bentrok jadwal lomba'])->assertSessionHas('message');
        $row = model(TestAssignmentModel::class)->forApplicant((int) $a['id']);
        $this->assertSame([(string) $second, 'rescheduled'], [(string) $row['session_id'], $row['status']]);

        $this->post($url, [])->assertSessionHas('errors', ['sesi' => lang('TesPenjadwalan.err.sessionRequired')]);

        $this->post($url . '/hapus', ['alasan' => 'no'])->assertSessionHas('error');
        $this->post($url . '/hapus', ['alasan' => 'Mengundurkan diri'])->assertSessionHas('message');
        $this->assertNull(model(TestAssignmentModel::class)->forApplicant((int) $a['id']));
        $this->assertSame(['assigned', 'moved', 'removed'], array_column(db_connect()->table('test_schedule_changes')->orderBy('id')->get()->getResultArray(), 'kind'));
    }

    public function testFullSessionNeedsConfirmation(): void
    {
        $full = $this->makeSession(['quota' => 1]);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $full, ['seat_number' => 1]);
        $a   = $this->makeApplicant();
        $url = 'admin/pendaftar/' . $a['id'] . '/tes';
        $this->actingAs($this->makeStaff('panitia'));

        $this->get($url)->assertSee('penuh');
        $this->post($url, ['sesi' => $full])->assertSessionHas('error', lang('TesPenjadwalan.err.sessionFull'));
        $this->post($url, ['sesi' => $full, 'lebih_kuota' => '1'])->assertSessionHas('message');
        $this->assertSame(2, (int) model(TestAssignmentModel::class)->forApplicant((int) $a['id'])['seat_number']);
    }

    public function testRemoveButtonOnlyForTesAtur(): void
    {
        $a = $this->makeApplicant();
        $this->makeAssignment((int) $a['id'], $this->makeSession());

        $this->actingAs($this->makeStaff('panitia'))->get('admin/pendaftar/' . $a['id'] . '/tes')->assertDontSee('Keluarkan dari Jadwal');
        $_SESSION = [];
        $this->actingAs($this->makeStaff('admin'))->get('admin/pendaftar/' . $a['id'] . '/tes')->assertSee('Keluarkan dari Jadwal');
    }

    public function testUnknownApplicantIsNotFound(): void
    {
        $this->actingAs($this->makeStaff('admin'))->get('admin/pendaftar/999999/tes')->assertStatus(404);
    }

    // --- Panels ------------------------------------------------------------

    public function testAdminPanel(): void
    {
        $staff = $this->makeStaff('panitia');
        $a     = $this->makeApplicant();
        $this->actingAs($staff);
        $this->get('admin/pendaftar/' . $a['id'])->assertSee('Beri Jadwal');

        $session = $this->makeSession(['room_id' => $this->makeRoom(['name' => 'Lab Bahasa', 'location' => 'Gedung B'])]);
        (new Scheduler())->assign((int) $a['id'], $session, (int) $staff->id, 'Pendaftar offline');

        $page = $this->get('admin/pendaftar/' . $a['id']);
        $page->assertSee('Pindah Jadwal');
        $page->assertSee('Lab Bahasa');
        $page->assertSee('Gedung B');
        $page->assertSee('Dijadwalkan petugas');
        $page->assertSee('Pendaftar offline');
    }

    public function testPublicPanel(): void
    {
        $a = $this->makeApplicant();
        $this->assertStringContainsString('Calon murid belum mendapat jadwal tes', $this->panel('applicant_public', $a));

        $this->makeAssignment((int) $a['id'], $this->makeSession(['room_id' => $this->makeRoom(['name' => 'Ruang Kenanga', 'location' => 'Lantai 2'])]));
        $html = $this->panel('applicant_public', $a);
        $this->assertStringContainsString('Sabtu, 12 Juni 2027', $html);
        $this->assertStringContainsString('07.30–09.30 WIB', $html);
        $this->assertStringContainsString('Ruang Kenanga', $html);
        $this->assertStringContainsString('Lantai 2', $html);
        $this->assertStringContainsString('Belum tes', $html);
        $this->assertStringContainsString(esc(setting('Tes.instructions')), $html);

        setting('Tes.scheduleVisible', false);
        $hidden = $this->panel('applicant_public', $a);
        $this->assertStringNotContainsString('Ruang Kenanga', $hidden);
        $this->assertStringContainsString(lang('Tes.scheduleLater'), $hidden);

        model(ApplicantModel::class, false)->update((int) $a['id'], ['status' => 'cancelled']);
        $this->assertSame('', $this->panel('applicant_public', model(ApplicantModel::class)->find((int) $a['id'])));
    }

    public function testPublicPanelOnTheStatusPage(): void
    {
        $a = $this->makeApplicant();
        $this->makeAssignment((int) $a['id'], $this->makeSession(['room_id' => $this->makeRoom(['name' => 'Ruang Anggrek'])]));

        $result = $this->withSession([ApplicantAccess::SESSION_KEY => ['id' => (int) $a['id'], 'expires' => time() + 600]])->get('cek-status/data');

        $result->assertOK();
        $result->assertSee('Ruang Anggrek');
    }
}
