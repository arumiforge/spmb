<?php

declare(strict_types=1);

use App\Libraries\ApplicantAccess;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Services\ApplicantService;
use Tests\Support\Pendaftaran\PendaftaranFixtures;

/**
 * Public pages: /daftar, /daftar/selesai, /cek-status (+ data, ubah, keluar)
 * and /tautan/<token> (FR-REG-01, FR-REG-07, D-008, D-032, D-034).
 *
 * @internal
 */
final class PendaftaranPublicTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PendaftaranFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetVisitor();
        service('throttler')->remove('cek-status-' . md5('0.0.0.0'));
        service('throttler')->remove('daftar-' . md5('0.0.0.0'));
    }

    protected function tearDown(): void
    {
        $this->forgetPendaftaranSettings();
        $this->resetVisitor();
        parent::tearDown();
    }

    private function registered(array $overrides = []): array
    {
        $data = ApplicantForm::clean($this->applicantInput($overrides), [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE]);

        return (new ApplicantService())->register($data, 'offline');
    }

    private function grantedId(): ?int
    {
        $grant = $_SESSION[ApplicantAccess::SESSION_KEY] ?? null;

        return is_array($grant) ? (int) $grant['id'] : null;
    }

    public function testFormIsClosedWithoutWaveOrWhenSwitchedOff(): void
    {
        $this->get('daftar')->assertSee('Pendaftaran online sedang ditutup');

        $this->makeWave();
        $open = $this->get('daftar');
        $open->assertSee('Formulir Pendaftaran Murid Baru');
        $open->assertSee('assets/img/form/daftar-online.webp');
        $open->assertDontSee('Nomor Kartu Keluarga');

        setting('Pendaftaran.onlineOpen', false);
        $this->get('daftar')->assertSee('Pendaftaran online sedang tidak dibuka');

        $this->post('daftar', $this->applicantInput())->assertRedirectTo(site_url('daftar'));
        $this->assertSame(0, model(ApplicantModel::class)->countAllResults());
    }

    public function testOnlineRegistrationShowsNumberAndLinkOnce(): void
    {
        $this->makeWave();

        $result = $this->post('daftar', $this->applicantInput());
        $result->assertRedirectTo(site_url('daftar/selesai'));

        $row = model(ApplicantModel::class)->first();
        $this->assertSame('online', $row['method']);
        $this->assertSame((int) $row['id'], $this->grantedId());

        $done = $this->withSession($_SESSION)->get('daftar/selesai');
        $done->assertSee($row['registration_number']);
        $done->assertSee('/tautan/');
        $done->assertSee('assets/img/state/sukses-pendaftaran.webp');

        // Shown once: the flash data is gone afterwards.
        unset($_SESSION['pendaftaran_selesai'], $_SESSION['__ci_vars']);
        $this->withSession($_SESSION)->get('daftar/selesai')->assertRedirectTo(site_url('cek-status/data'));
    }

    public function testDuplicateIsFlaggedWithoutRevealingTheOtherApplicant(): void
    {
        $this->makeWave();
        $first = $this->registered(['full_name' => 'Rahasia Pertama'])['applicant'];

        $this->post('daftar', $this->applicantInput(['full_name' => 'Anak Kedua']))->assertRedirectTo(site_url('daftar/selesai'));
        $second = model(ApplicantModel::class)->where('full_name', 'Anak Kedua')->first();
        $this->assertSame('1', (string) $second['possible_duplicate']);

        $done = $this->withSession($_SESSION)->get('daftar/selesai');
        $done->assertDontSee('Rahasia Pertama');
        $done->assertDontSee($first['registration_number']);
    }

    public function testValidationErrorsAreFriendly(): void
    {
        $this->makeWave();
        $this->post('daftar', $this->applicantInput(['nisn' => '12', 'birth_date' => '2030-01-01']))->assertSessionHas('errors');
        $this->assertSame('NISN harus 10 angka.', session('errors')['nisn']);
        $this->assertArrayHasKey('birth_date', session('errors'));
        $this->assertSame(0, model(ApplicantModel::class)->countAllResults());
    }

    public function testStatusCheckGrantsAccessWithGenericError(): void
    {
        $row = $this->registered()['applicant'];

        $this->get('cek-status')->assertSee('Cek Status Pendaftaran');

        $wrong = $this->post('cek-status', ['number' => $row['registration_number'], 'birth_date' => '2001-01-01']);
        $wrong->assertSessionHas('check_error', 'Data tidak ditemukan. Periksa kembali nomor pendaftaran dan tanggal lahir.');
        $this->assertNull($this->grantedId());

        $unknown = $this->post('cek-status', ['number' => 'PMB-2099-9999', 'birth_date' => $row['birth_date']]);
        $unknown->assertSessionHas('check_error', 'Data tidak ditemukan. Periksa kembali nomor pendaftaran dan tanggal lahir.');

        $ok = $this->post('cek-status', ['number' => strtolower($row['registration_number']), 'birth_date' => $row['birth_date']]);
        $ok->assertRedirectTo(site_url('cek-status/data'));
        $this->assertSame((int) $row['id'], $this->grantedId());
        $this->seeInDatabase('audit_logs', ['event' => 'status_checked', 'subject_id' => (string) $row['id']]);

        $page = $this->withSession($_SESSION)->get('cek-status/data');
        $page->assertSee('Ahmad Fauzi');
        $page->assertSee('3319••••••••0001');
        $page->assertDontSee('3319011705150001');

        $this->withSession($_SESSION)->get('cek-status')->assertRedirectTo(site_url('cek-status/data'));
        $this->withSession($_SESSION)->post('cek-status/keluar')->assertRedirectTo(site_url('cek-status'));
        $this->assertNull($this->grantedId());
    }

    public function testStatusCheckIsThrottled(): void
    {
        setting('Pendaftaran.statusCheckAttempts', 2);
        $row = $this->registered()['applicant'];

        $this->post('cek-status', ['number' => 'X', 'birth_date' => '2001-01-01']);
        $this->post('cek-status', ['number' => 'X', 'birth_date' => '2001-01-01']);
        $blocked = $this->post('cek-status', ['number' => $row['registration_number'], 'birth_date' => $row['birth_date']]);

        $blocked->assertSessionHas('check_error', 'Terlalu banyak percobaan. Tunggu satu menit lalu coba lagi.');
        $this->assertNull($this->grantedId());
    }

    public function testPagesNeedAGrant(): void
    {
        foreach (['cek-status/data', 'cek-status/ubah'] as $url) {
            $this->get($url)->assertRedirectTo(site_url('cek-status'));
        }

        $this->post('cek-status/ubah', ['address' => 'X'])->assertRedirectTo(site_url('cek-status'));
    }

    public function testPrivateLink(): void
    {
        $result = $this->registered();

        $this->get('tautan/' . $result['token'])->assertRedirectTo(site_url('cek-status/data'));
        $this->assertSame((int) $result['applicant']['id'], $this->grantedId());

        $this->resetVisitor();
        $this->get('tautan/salah-tautan-yang-tidak-ada-123456')->assertRedirectTo(site_url('cek-status'));
        $this->assertNull($this->grantedId());
    }

    public function testApplicantCorrectsContactButNotIdentity(): void
    {
        $result = $this->registered();
        $id     = (int) $result['applicant']['id'];
        $this->get('tautan/' . $result['token']);

        $this->withSession($_SESSION)->get('cek-status/ubah')->assertSee('Perbaiki Alamat dan Kontak');

        $this->withSession($_SESSION)->post('cek-status/ubah', [
            'address' => 'Jl. Pindahan 5', 'parent_phone' => '0899 1111 2222', 'full_name' => 'Nama Diganti', 'nik' => '1111111111111111',
        ])->assertRedirectTo(site_url('cek-status/data'));

        $row = model(ApplicantModel::class)->find($id);
        $this->assertSame('Jl. Pindahan 5', $row['address']);
        $this->assertSame('089911112222', $row['parent_phone']);
        $this->assertSame('Ahmad Fauzi', $row['full_name']);
        $this->assertSame('3319011705150001', $row['nik']);
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'applicants', 'subject_id' => (string) $id]);

        $this->withSession($_SESSION)->post('cek-status/ubah', ['address' => 'Jl. Pindahan 5', 'parent_phone' => '123'])->assertSessionHas('errors');
    }
}
