<?php

declare(strict_types=1);

use App\Libraries\ApplicantAccess;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Kartu\Controllers\Periksa;
use Modules\Kartu\Services\CardTokenService;
use Tests\Support\Kartu\KartuFixtures;

/**
 * Card routes: staff print/renew/lookup/settings, the public QR check page
 * and the applicant's own download (FR-CARD-01…04, D-034, D-035).
 *
 * @internal
 */
final class KartuTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use KartuFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        // Start every test signed out (Shield keeps the signed-in user in the session).
        $_SESSION = [];
        $this->resetServices();
        service('routes')->loadRoutes();
        cache()->delete(Periksa::throttleKey('0.0.0.0'));
        cache()->delete(Periksa::throttleKey('127.0.0.1'));
    }

    protected function tearDown(): void
    {
        foreach (['availableWhen', 'headerText', 'footerNote'] as $key) {
            setting()->forget('Kartu.' . $key);
        }

        cache()->delete(Periksa::throttleKey('0.0.0.0'));
        cache()->delete(Periksa::throttleKey('127.0.0.1'));
        parent::tearDown();
    }

    private function panel(string $name, array $applicant): string
    {
        $html = view('Modules\\Kartu\\Views\\panels\\' . $name, ['applicant' => $applicant], ['saveData' => false]);

        // Drop the debug toolbar's view markers.
        return trim((string) preg_replace('/<!--.*?-->/s', '', $html));
    }

    private function grant(int $applicantId): static
    {
        return $this->withSession([ApplicantAccess::SESSION_KEY => ['id' => $applicantId, 'expires' => time() + 600]]);
    }

    public function testStaffPrintsTheSameCardAgain(): void
    {
        $applicant = $this->makeApplicant();
        $panitia   = $this->makeUser('panitia');

        $first = $this->actingAs($panitia)->get('admin/pendaftar/' . $applicant['id'] . '/kartu');
        $first->assertOK();
        $first->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $first->response()->getBody());

        $this->actingAs($panitia)->get('admin/pendaftar/' . $applicant['id'] . '/kartu')->assertOK();

        $this->assertSame(1, db_connect()->table('card_tokens')->countAllResults());
        $this->assertSame(2, db_connect()->table('audit_logs')->where('event', 'card_printed')->where('subject_id', (string) $applicant['id'])->countAllResults());
    }

    public function testCancelledApplicantGetsNoCard(): void
    {
        $applicant = $this->makeApplicant(['status' => 'cancelled']);

        $result = $this->actingAs($this->makeUser('panitia'))->get('admin/pendaftar/' . $applicant['id'] . '/kartu');

        $result->assertRedirect();
        $result->assertSessionHas('error');
        $this->dontSeeInDatabase('card_tokens', ['applicant_id' => $applicant['id']]);
    }

    public function testUnknownApplicantIsNotFound(): void
    {
        $this->actingAs($this->makeUser('panitia'))->get('admin/pendaftar/999/kartu')->assertStatus(404);
    }

    public function testStaffRenewsALostCard(): void
    {
        $applicant = $this->makeApplicant();
        $old       = (new CardTokenService())->tokenFor((int) $applicant['id']);

        $result = $this->actingAs($this->makeUser('panitia'))->post('admin/pendaftar/' . $applicant['id'] . '/kartu/baru');

        $result->assertRedirect();
        $result->assertSessionHas('message');
        $this->assertTrue((new CardTokenService())->isRevoked($old));
        $this->seeInDatabase('audit_logs', ['event' => 'card_revoked', 'subject_id' => (string) $applicant['id']]);
        $this->get('kartu/' . $old)->assertSee('Kartu tidak berlaku');
    }

    public function testStaffWithoutPermissionIsTurnedAway(): void
    {
        $applicant = $this->makeApplicant();

        foreach (['kepsek', 'petugas-daftar-ulang'] as $group) {
            $user = $this->makeUser($group);
            $this->actingAs($user)->get('admin/pendaftar/' . $applicant['id'] . '/kartu')->assertRedirectTo(site_url('admin/akses-ditolak'));
            $this->actingAs($user)->post('admin/pendaftar/' . $applicant['id'] . '/kartu/baru')->assertRedirectTo(site_url('admin/akses-ditolak'));
            $this->actingAs($user)->get('admin/kartu')->assertRedirectTo(site_url('admin/akses-ditolak'));
        }

        $this->actingAs($this->makeUser('panitia'))->get('admin/pengaturan/kartu')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->dontSeeInDatabase('card_tokens', ['applicant_id' => $applicant['id']]);
    }

    public function testGuestIsSentToSignIn(): void
    {
        foreach (['admin/pendaftar/1/kartu', 'admin/kartu', 'admin/pengaturan/kartu'] as $url) {
            $this->get($url)->assertRedirectTo(site_url('masuk'));
        }

        $this->post('admin/pendaftar/1/kartu/baru')->assertRedirectTo(site_url('masuk'));
    }

    public function testPublicCheckShowsMinimalData(): void
    {
        $applicant = $this->makeApplicant();
        $token     = (new CardTokenService())->tokenFor((int) $applicant['id']);

        $result = $this->get('kartu/' . $token);

        $result->assertOK();
        $result->assertSee('Kartu sah');
        $result->assertSee($applicant['registration_number']);
        $result->assertSee($applicant['full_name']);
        $result->assertSee('Gelombang Uji');

        foreach (['3319011705150001', '0151234567', '081234567890', 'Jl. Melati', '17 Mei 2015', '2015-05-17', 'admin/pendaftar/'] as $private) {
            $result->assertDontSee($private);
        }

        $this->assertNotNull(db_connect()->table('card_tokens')->get()->getRowArray()['last_seen_at']);
    }

    public function testSignedInStaffGetALinkToTheApplicant(): void
    {
        $applicant = $this->makeApplicant();
        $token     = (new CardTokenService())->tokenFor((int) $applicant['id']);

        $this->actingAs($this->makeUser('petugas-tes'))->get('kartu/' . $token)->assertSee(site_url('admin/pendaftar/' . $applicant['id']));
    }

    public function testUnknownAndCancelledCards(): void
    {
        $this->get('kartu/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA')->assertStatus(404);
        $this->get('kartu/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA')->assertSee('Kartu tidak berlaku');

        $applicant = $this->makeApplicant(['status' => 'cancelled']);
        $token     = (new CardTokenService())->tokenFor((int) $applicant['id']);
        $result    = $this->get('kartu/' . $token);
        $result->assertSee('Kartu tidak berlaku');
        $result->assertSee('dibatalkan');
    }

    public function testPublicCheckIsThrottled(): void
    {
        $ip = service('request')->getIPAddress();

        for ($i = 0; $i < Periksa::CHECKS_PER_MINUTE; $i++) {
            service('throttler')->check(Periksa::throttleKey($ip), Periksa::CHECKS_PER_MINUTE, MINUTE);
        }

        $result = $this->get('kartu/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
        $result->assertStatus(429);
        $result->assertSee('Coba lagi sebentar');
    }

    public function testApplicantDownloadsOwnCard(): void
    {
        $applicant = $this->makeApplicant();

        $result = $this->grant((int) $applicant['id'])->get('cek-status/kartu');

        $result->assertOK();
        $result->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $result->response()->getBody());
        $this->seeInDatabase('card_tokens', ['applicant_id' => $applicant['id'], 'created_by' => null]);
    }

    public function testDownloadNeedsAStatusCheck(): void
    {
        $this->get('cek-status/kartu')->assertRedirectTo(site_url('cek-status'));
        $this->grant(999)->get('cek-status/kartu')->assertRedirectTo(site_url('cek-status'));
    }

    public function testDownloadWaitsForVerificationWhenConfigured(): void
    {
        setting('Kartu.availableWhen', 'verified');
        $waiting  = $this->makeApplicant();
        $verified = $this->makeApplicant(['status' => 'verified']);

        $result = $this->grant((int) $waiting['id'])->get('cek-status/kartu');
        $result->assertOK();
        $result->assertSee('Kartu belum bisa diunduh');
        $this->dontSeeInDatabase('card_tokens', ['applicant_id' => $waiting['id']]);

        $this->grant((int) $verified['id'])->get('cek-status/kartu')->assertHeader('Content-Type', 'application/pdf');
    }

    public function testCancelledApplicantCannotDownload(): void
    {
        $applicant = $this->makeApplicant(['status' => 'cancelled']);

        $this->grant((int) $applicant['id'])->get('cek-status/kartu')->assertSee('Pendaftaran dibatalkan');
        $this->dontSeeInDatabase('card_tokens', ['applicant_id' => $applicant['id']]);
    }

    public function testLookupByNumberUrlAndRevokedCode(): void
    {
        $applicant = $this->makeApplicant();
        $panitia   = $this->makeUser('panitia');
        $service   = new CardTokenService();

        $result = $this->actingAs($panitia)->get('admin/kartu');
        $result->assertOK();
        $result->assertSee('Belum ada yang diperiksa');

        $result = $this->actingAs($panitia)->get('admin/kartu?q=' . $applicant['registration_number']);
        $result->assertSee($applicant['full_name']);
        $result->assertSee('Calon murid ini belum punya kartu.');

        $old = $service->tokenFor((int) $applicant['id']);
        $result = $this->actingAs($panitia)->get('admin/kartu?q=' . rawurlencode(site_url('kartu/' . $old)));
        $result->assertSee($applicant['full_name']);
        $result->assertSee('Kartu aktif');

        $service->renew((int) $applicant['id'], (int) $panitia->id);
        $this->actingAs($panitia)->get('admin/kartu?q=' . $old)->assertSee('sudah tidak berlaku');

        $this->actingAs($panitia)->get('admin/kartu?q=PMB-9999-9999')->assertSee('tidak ditemukan');
    }

    public function testAdminChangesCardSettings(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('admin/pengaturan/kartu')->assertOK();

        $result = $this->actingAs($admin)->post('admin/pengaturan/kartu', [
            'availableWhen' => 'verified',
            'headerText'    => 'Kartu Peserta PPDB',
            'footerNote'    => 'Datang 15 menit sebelum tes.',
        ]);

        $result->assertRedirectTo(site_url('admin/pengaturan/kartu'));
        $this->assertSame('verified', setting('Kartu.availableWhen'));
        $this->assertSame('Kartu Peserta PPDB', setting('Kartu.headerText'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_id' => 'Kartu']);

        $this->actingAs($admin)->post('admin/pengaturan/kartu', ['availableWhen' => 'kapan-saja', 'headerText' => ''])->assertSessionHas('errors');
        $this->assertSame('verified', setting('Kartu.availableWhen'));
    }

    public function testAdminPanel(): void
    {
        $applicant = $this->makeApplicant();

        $this->actingAs($this->makeUser('kepsek'));
        $this->assertSame('', $this->panel('applicant_admin', $applicant));

        $this->actingAs($this->makeUser('panitia'));
        $html = view('Modules\Kartu\Views\panels\applicant_admin', ['applicant' => $applicant], ['saveData' => false]);
        $this->assertStringContainsString('Cetak Kartu', $html);
        $this->assertStringContainsString('belum pernah dicetak', $html);
        $this->assertStringNotContainsString('Buat Kartu Baru', $html);

        (new CardTokenService())->tokenFor((int) $applicant['id']);
        $html = view('Modules\Kartu\Views\panels\applicant_admin', ['applicant' => $applicant], ['saveData' => false]);
        $this->assertStringContainsString('Buat Kartu Baru', $html);
        $this->assertStringContainsString('Kartu aktif', $html);
    }

    public function testPublicPanel(): void
    {
        $applicant = $this->makeApplicant();

        $html = view('Modules\Kartu\Views\panels\applicant_public', ['applicant' => $applicant], ['saveData' => false]);
        $this->assertStringContainsString('Unduh Kartu', $html);
        $this->assertStringContainsString(site_url('cek-status/kartu'), $html);

        setting('Kartu.availableWhen', 'verified');
        $html = view('Modules\Kartu\Views\panels\applicant_public', ['applicant' => $applicant], ['saveData' => false]);
        $this->assertStringNotContainsString('Unduh Kartu', $html);
        $this->assertStringContainsString('setelah berkas diperiksa panitia', $html);

        $this->assertSame('', $this->panel('applicant_public', []));
    }
}
