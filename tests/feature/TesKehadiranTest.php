<?php

declare(strict_types=1);

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Kartu\Controllers\Periksa;
use Modules\Kartu\Services\CardTokenService;
use Modules\Tes\Services\Scheduler;
use Tests\Support\Tes\TesFixtures;

/**
 * Attendance routes (FR-TEST-03, D-061): day overview, session roster,
 * recording (form and JSON), scanning, moving a walk-in, marking the rest
 * absent, the attendance sheet PDF and the card_check / card_lookup /
 * dashboard panels.
 *
 * @internal
 */
final class TesKehadiranTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use TesFixtures;

    protected $namespace;
    protected $refresh = true;

    /**
     * @var array<string, User>
     */
    private array $staff = [];

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION    = [];
        $this->staff = [];
        $this->resetServices();
        service('routes')->loadRoutes();
        cache()->delete(Periksa::throttleKey('0.0.0.0'));
        cache()->delete(Periksa::throttleKey('127.0.0.1'));
    }

    private function as(string $group): static
    {
        $this->staff[$group] ??= $this->makeStaff($group);

        // Headers stay between requests in FeatureTestTrait: start each request without JSON.
        return $this->withHeaders([])->actingAs($this->staff[$group]);
    }

    private function json(): static
    {
        return $this->withHeaders(['Accept' => 'application/json']);
    }

    /**
     * Today's session from 00:00 to 23:59 with one pending participant.
     *
     * @return array{session: int, applicant: array<string, mixed>}
     */
    private function todayWithParticipant(array $session = []): array
    {
        $id        = $this->makeSession($session + ['test_date' => date('Y-m-d'), 'starts_at' => '00:00:00', 'ends_at' => '23:59:59']);
        $applicant = $this->makeApplicant();
        $this->makeAssignment((int) $applicant['id'], $id, ['seat_number' => 1]);

        return ['session' => $id, 'applicant' => $applicant];
    }

    private function assignmentStatus(int $applicantId): ?string
    {
        $row = db_connect()->table('test_assignments')->where('applicant_id', $applicantId)->get()->getRowArray();

        return $row['status'] ?? null;
    }

    public function testGuestIsSentToSignIn(): void
    {
        foreach (['admin/tes/kehadiran', 'admin/tes/kehadiran/sesi/1', 'admin/tes/sesi/1/daftar-hadir'] as $url) {
            $this->get($url)->assertRedirectTo(site_url('masuk'));
        }

        foreach (['admin/tes/kehadiran/catat', 'admin/tes/kehadiran/sesi/1/pindai', 'admin/tes/kehadiran/sesi/1/pindahkan', 'admin/tes/kehadiran/sesi/1/tidak-hadir-semua'] as $url) {
            $this->post($url)->assertRedirectTo(site_url('masuk'));
        }
    }

    public function testStaffWithoutPermissionIsTurnedAway(): void
    {
        $fixture = $this->todayWithParticipant();
        $id      = $fixture['session'];

        foreach (['admin/tes/kehadiran', 'admin/tes/kehadiran/sesi/' . $id, 'admin/tes/sesi/' . $id . '/daftar-hadir'] as $url) {
            $this->as('kepsek')->get($url)->assertRedirectTo(site_url('admin/akses-ditolak'));
        }

        foreach (['admin/tes/kehadiran/sesi/' . $id . '/pindai', 'admin/tes/kehadiran/sesi/' . $id . '/pindahkan', 'admin/tes/kehadiran/sesi/' . $id . '/tidak-hadir-semua'] as $url) {
            $this->as('kepsek')->post($url, ['kode' => 'PMB-2027-0001', 'applicant_id' => $fixture['applicant']['id']])->assertRedirectTo(site_url('admin/akses-ditolak'));
        }

        $this->as('kepsek')->post('admin/tes/kehadiran/catat', ['applicant_id' => $fixture['applicant']['id'], 'status' => 'present'])->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->assertSame('pending', $this->assignmentStatus((int) $fixture['applicant']['id']));
    }

    public function testTodayShowsSessionsWithCounts(): void
    {
        $fixture = $this->todayWithParticipant(['room_id' => $this->makeRoom(['name' => 'Lab Bahasa'])]);
        $this->makeSession(['test_date' => '2027-06-12']);

        foreach (['petugas-tes', 'panitia', 'admin'] as $group) {
            $result = $this->as($group)->get('admin/tes/kehadiran');
            $result->assertOK();
            $result->assertSee('Lab Bahasa');
            $result->assertSee('0 dari 1 hadir');
            $result->assertSee('admin/tes/kehadiran/sesi/' . $fixture['session']);
            $result->assertSee('12 Juni 2027');
        }

        $other = $this->as('petugas-tes')->get('admin/tes/kehadiran?tanggal=2027-06-12');
        $other->assertSee('Sesi pada Sabtu, 12 Juni 2027');
        $other->assertDontSee('Lab Bahasa');

        $this->as('petugas-tes')->get('admin/tes/kehadiran?tanggal=bukan-tanggal')->assertSee('Sesi hari ini');
    }

    public function testNoSessionsShowsAFriendlyEmptyState(): void
    {
        $this->as('petugas-tes')->get('admin/tes/kehadiran')->assertSee('Hari ini tidak ada sesi tes');
    }

    public function testRosterListsParticipants(): void
    {
        $fixture = $this->todayWithParticipant();
        $other   = $this->makeApplicant(['full_name' => 'Budi Santoso']);
        $this->makeAssignment((int) $other['id'], $fixture['session'], ['seat_number' => 2]);

        $result = $this->as('petugas-tes')->get('admin/tes/kehadiran/sesi/' . $fixture['session']);
        $result->assertOK();
        $result->assertSee($fixture['applicant']['full_name']);
        $result->assertSee('Budi Santoso');
        $result->assertSee('Pindai Kartu');
        $result->assertSee('scanner.js');

        $search = $this->as('petugas-tes')->get('admin/tes/kehadiran/sesi/' . $fixture['session'] . '?q=budi');
        $search->assertSee('Budi Santoso');
        $search->assertDontSee($fixture['applicant']['full_name']);

        $this->as('petugas-tes')->get('admin/tes/kehadiran/sesi/999')->assertStatus(404);
    }

    public function testRecordWithAFormRedirectsBack(): void
    {
        $fixture = $this->todayWithParticipant();
        $id      = (int) $fixture['applicant']['id'];

        $result = $this->as('petugas-tes')->post('admin/tes/kehadiran/catat', [
            'applicant_id' => $id, 'status' => 'present', 'session_id' => $fixture['session'], 'kembali' => 'admin/tes/kehadiran/sesi/' . $fixture['session'],
        ]);

        $result->assertRedirectTo(site_url('admin/tes/kehadiran/sesi/' . $fixture['session']));
        $result->assertSessionHas('message', $fixture['applicant']['full_name'] . ' tercatat hadir.');
        $this->assertSame('present', $this->assignmentStatus($id));
        $this->seeInDatabase('audit_logs', ['event' => 'test_attendance', 'subject_id' => (string) $id]);
    }

    public function testUnsafeReturnAddressesAreIgnored(): void
    {
        $fixture = $this->todayWithParticipant();

        foreach (['https://jahat.test/', '//jahat.test', 'javascript:alert(1)'] as $back) {
            $result = $this->as('panitia')->post('admin/tes/kehadiran/catat', ['applicant_id' => $fixture['applicant']['id'], 'status' => 'absent', 'kembali' => $back]);
            $result->assertRedirect();
            $this->assertSame(site_url('admin/tes/kehadiran'), $result->getRedirectUrl(), $back);
        }
    }

    public function testRecordAsJsonReturnsTheNewRow(): void
    {
        $fixture = $this->todayWithParticipant();
        $id      = (int) $fixture['applicant']['id'];

        $result = $this->as('petugas-tes')->json()->post('admin/tes/kehadiran/catat', ['applicant_id' => $id, 'status' => 'present', 'session_id' => $fixture['session']]);

        $result->assertOK();
        $data = json_decode($result->getJSON(), true);
        $this->assertTrue($data['ok']);
        $this->assertSame($id, $data['applicantId']);
        $this->assertStringContainsString('id="peserta-' . $id . '"', $data['rowHtml']);
        $this->assertStringContainsString('Hadir pukul', $data['rowHtml']);
        $this->assertSame(['filled' => 1, 'present' => 1, 'absent' => 0, 'pending' => 0], $data['counts']);
        $this->assertArrayHasKey('csrf', $data);

        // Undo.
        $undo = json_decode($this->as('petugas-tes')->json()->post('admin/tes/kehadiran/catat', ['applicant_id' => $id, 'status' => 'pending', 'session_id' => $fixture['session']])->getJSON(), true);
        $this->assertStringContainsString('dibatalkan', $undo['message']);
        $this->assertSame('pending', $this->assignmentStatus($id));
    }

    public function testRecordRefusals(): void
    {
        $fixture = $this->todayWithParticipant();
        $other   = $this->makeSession();
        $free    = $this->makeApplicant();

        $wrongSession = $this->as('petugas-tes')->json()->post('admin/tes/kehadiran/catat', ['applicant_id' => $fixture['applicant']['id'], 'status' => 'present', 'session_id' => $other]);
        $wrongSession->assertStatus(422);
        $this->assertStringContainsString('sesi lain', json_decode($wrongSession->getJSON(), true)['message']);

        $this->as('petugas-tes')->post('admin/tes/kehadiran/catat', ['applicant_id' => $free['id'], 'status' => 'present'])->assertSessionHas('error');
        $this->as('petugas-tes')->post('admin/tes/kehadiran/catat', ['applicant_id' => $fixture['applicant']['id'], 'status' => 'hapus'])->assertSessionHas('error');
        $this->as('petugas-tes')->post('admin/tes/kehadiran/catat', ['applicant_id' => 999, 'status' => 'present'])->assertSessionHas('error');
        $this->assertSame('pending', $this->assignmentStatus((int) $fixture['applicant']['id']));
    }

    public function testScanMarksPresentAndConfirms(): void
    {
        $fixture = $this->todayWithParticipant();
        $id      = (int) $fixture['applicant']['id'];
        $token   = (new CardTokenService())->tokenFor($id);
        $url     = 'admin/tes/kehadiran/sesi/' . $fixture['session'] . '/pindai';

        $data = json_decode($this->as('petugas-tes')->json()->post($url, ['kode' => site_url('kartu/' . $token)])->getJSON(), true);
        $this->assertSame('marked', $data['state']);
        $this->assertTrue($data['ok']);
        $this->assertStringContainsString('tercatat hadir', $data['resultHtml']);
        $this->assertStringContainsString('Batalkan', $data['resultHtml']);
        $this->assertSame(1, $data['counts']['present']);
        $this->assertSame('present', $this->assignmentStatus($id));

        $again = json_decode($this->as('petugas-tes')->json()->post($url, ['kode' => 'pmb-2027-0001'])->getJSON(), true);
        $this->assertSame('already', $again['state']);
        $this->assertSame(1, db_connect()->table('audit_logs')->where('event', 'test_attendance')->countAllResults());
    }

    public function testScanWithoutJavascriptShowsTheResultAfterRedirect(): void
    {
        $fixture = $this->todayWithParticipant();
        $url     = 'admin/tes/kehadiran/sesi/' . $fixture['session'];

        $result = $this->as('panitia')->post($url . '/pindai', ['kode' => 'PMB-2027-0001']);
        $result->assertRedirectTo(site_url($url));
        $result->assertSessionHas('tesScan');

        $page = $this->as('panitia')->withSession(['tesScan' => session('tesScan'), '__ci_vars' => ['tesScan' => 'new']])->get($url);
        $page->assertSee($fixture['applicant']['full_name'] . ' tercatat hadir.');
    }

    public function testScanRefusals(): void
    {
        $fixture = $this->todayWithParticipant();
        $url     = 'admin/tes/kehadiran/sesi/' . $fixture['session'] . '/pindai';

        $elsewhere = $this->makeApplicant(['full_name' => 'Rina Lain Sesi']);
        $this->makeAssignment((int) $elsewhere['id'], $this->makeSession(['test_date' => '2027-06-12']));
        $unscheduled = $this->makeApplicant();
        $cancelled   = $this->makeApplicant(['status' => 'cancelled']);
        $revoked     = (new CardTokenService())->tokenFor((int) $unscheduled['id']);
        (new CardTokenService())->renew((int) $unscheduled['id'], (int) $this->makeStaff('admin')->id);

        $cases = [
            $elsewhere['registration_number']   => 'elsewhere',
            $unscheduled['registration_number'] => 'unscheduled',
            $cancelled['registration_number']   => 'cancelled',
            site_url('kartu/' . $revoked)       => 'revoked',
            'PMB-2027-9999'                     => 'notFound',
            ''                                  => 'notFound',
        ];

        foreach ($cases as $code => $state) {
            $data = json_decode($this->as('petugas-tes')->json()->post($url, ['kode' => $code])->getJSON(), true);
            $this->assertSame($state, $data['state'], (string) $code);
            $this->assertFalse($data['ok']);
        }

        $data = json_decode($this->as('petugas-tes')->json()->post($url, ['kode' => $elsewhere['registration_number']])->getJSON(), true);
        $this->assertStringContainsString('Sabtu, 12 Juni 2027', $data['resultHtml']);
        $this->assertSame('pending', $this->assignmentStatus((int) $elsewhere['id']));
    }

    public function testMoveHereUsesTheScheduler(): void
    {
        $fixture   = $this->todayWithParticipant();
        $elsewhere = $this->makeApplicant();
        $this->makeAssignment((int) $elsewhere['id'], $this->makeSession(['test_date' => date('Y-m-d', strtotime('+3 days'))]));
        $url = 'admin/tes/kehadiran/sesi/' . $fixture['session'] . '/pindahkan';

        if (! class_exists(Scheduler::class)) {
            // Package B not merged yet: the action explains it is not available and changes nothing.
            $this->as('petugas-tes')->post($url, ['applicant_id' => $elsewhere['id']])->assertSessionHas('error', lang('TesKehadiran.moveUnavailable'));
            $this->assertSame('pending', $this->assignmentStatus((int) $elsewhere['id']));

            return;
        }

        $data = json_decode($this->as('petugas-tes')->json()->post($url, ['applicant_id' => $elsewhere['id']])->getJSON(), true);
        $this->assertSame('marked', $data['state'], $data['message']);
        $this->assertSame('present', $this->assignmentStatus((int) $elsewhere['id']));
        $this->assertSame($fixture['session'], (int) db_connect()->table('test_assignments')->where('applicant_id', $elsewhere['id'])->get()->getRow()->session_id);
        $this->seeInDatabase('test_schedule_changes', ['applicant_id' => $elsewhere['id'], 'kind' => 'moved', 'reason' => 'Hadir di sesi lain']);

        // A walk-in without a seat gets one here.
        $walkIn = $this->makeApplicant();
        $this->as('petugas-tes')->json()->post($url, ['applicant_id' => $walkIn['id']])->assertOK();
        $this->assertSame('present', $this->assignmentStatus((int) $walkIn['id']));
    }

    public function testMoveHereRefusesCancelledApplicants(): void
    {
        $fixture   = $this->todayWithParticipant();
        $cancelled = $this->makeApplicant(['status' => 'cancelled']);

        $this->as('petugas-tes')->post('admin/tes/kehadiran/sesi/' . $fixture['session'] . '/pindahkan', ['applicant_id' => $cancelled['id']])->assertSessionHas('error');
        $this->dontSeeInDatabase('test_assignments', ['applicant_id' => $cancelled['id']]);
    }

    public function testMarkRemainingAbsent(): void
    {
        $session = $this->makeSession(['test_date' => date('Y-m-d', strtotime('-1 day'))]);
        $one     = $this->makeApplicant();
        $two     = $this->makeApplicant();
        $this->makeAssignment((int) $one['id'], $session);
        $this->makeAssignment((int) $two['id'], $session, ['status' => 'present']);

        $result = $this->as('panitia')->post('admin/tes/kehadiran/sesi/' . $session . '/tidak-hadir-semua');
        $result->assertRedirectTo(site_url('admin/tes/kehadiran/sesi/' . $session));
        $result->assertSessionHas('message', '1 peserta ditandai tidak hadir.');
        $this->assertSame('absent', $this->assignmentStatus((int) $one['id']));
        $this->assertSame('present', $this->assignmentStatus((int) $two['id']));

        $future = $this->makeSession(['test_date' => date('Y-m-d', strtotime('+1 day'))]);
        $this->as('panitia')->post('admin/tes/kehadiran/sesi/' . $future . '/tidak-hadir-semua')->assertSessionHas('error');
        $this->as('panitia')->post('admin/tes/kehadiran/sesi/999/tidak-hadir-semua')->assertStatus(404);
    }

    public function testAttendanceSheetPdf(): void
    {
        $fixture = $this->todayWithParticipant();

        $result = $this->as('petugas-tes')->get('admin/tes/sesi/' . $fixture['session'] . '/daftar-hadir');
        $result->assertOK();
        $result->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $result->response()->getBody());

        $this->as('petugas-tes')->get('admin/tes/sesi/999/daftar-hadir')->assertStatus(404);
    }

    public function testCardCheckPanelRecordsAttendance(): void
    {
        $fixture = $this->todayWithParticipant();
        $id      = (int) $fixture['applicant']['id'];
        $token   = (new CardTokenService())->tokenFor($id);

        $this->get('kartu/' . $token)->assertDontSee('Catat Hadir');
        $this->as('kepsek')->get('kartu/' . $token)->assertDontSee('Catat Hadir');

        $page = $this->as('petugas-tes')->get('kartu/' . $token);
        $page->assertSee('Catat Hadir');
        $page->assertSee('Belum tes');

        $result = $this->as('petugas-tes')->post('admin/tes/kehadiran/catat', ['applicant_id' => $id, 'status' => 'present', 'kembali' => 'kartu/' . $token]);
        $result->assertRedirectTo(site_url('kartu/' . $token));
        $this->assertSame('present', $this->assignmentStatus($id));

        $after = $this->as('petugas-tes')->get('kartu/' . $token);
        $after->assertSee('Tercatat hadir pukul');
        $after->assertDontSee('Catat Hadir');
    }

    public function testCardLookupPanelShowsTheSchedule(): void
    {
        $fixture = $this->todayWithParticipant(['room_id' => $this->makeRoom(['name' => 'Ruang Tulip', 'location' => 'Gedung B'])]);

        $result = $this->as('panitia')->get('admin/kartu?q=' . $fixture['applicant']['registration_number']);
        $result->assertOK();
        $result->assertSee('Ruang Tulip');
        $result->assertSee('Gedung B');
        $result->assertSee('Catat Hadir');
        $result->assertSee('admin/tes/kehadiran/sesi/' . $fixture['session']);
    }

    public function testDashboardPanel(): void
    {
        $this->todayWithParticipant(['room_id' => $this->makeRoom(['name' => 'Ruang Melati'])]);
        $this->makeApplicant();

        $tester = $this->as('petugas-tes')->get('admin');
        $tester->assertSee('Tes hari ini');
        $tester->assertSee('Ruang Melati');
        $tester->assertDontSee('Pendaftar belum dijadwalkan');

        $admin = $this->as('admin')->get('admin');
        $admin->assertSee('Pendaftar belum dijadwalkan');

        $this->as('kepsek')->get('admin')->assertDontSee('Tes hari ini');
    }
}
