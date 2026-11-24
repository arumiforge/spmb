<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Services\ApplicantService;
use Tests\Support\Pendaftaran\PendaftaranFixtures;
use Tests\Support\ViewerAccounts;

/**
 * Staff pages for applicants: list, offline form, detail, edit, status,
 * duplicate review, private link and delete (FR-REG-02, FR-REG-05, D-032).
 *
 * @internal
 */
final class PendaftaranAdminTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PendaftaranFixtures;
    use ViewerAccounts;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetVisitor();
    }

    protected function tearDown(): void
    {
        $this->forgetPendaftaranSettings();
        $this->resetVisitor();
        parent::tearDown();
    }

    private function register(array $overrides = []): array
    {
        $data = ApplicantForm::clean($this->applicantInput($overrides), [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE]);

        return (new ApplicantService())->register($data, 'offline', null)['applicant'];
    }

    public function testRoutePermissions(): void
    {
        $id = (int) $this->register()['id'];

        foreach (['admin/pendaftar', 'admin/pendaftar/tambah', 'admin/pendaftar/' . $id, 'admin/pendaftar/' . $id . '/ubah'] as $url) {
            $this->get($url)->assertRedirectTo(site_url('masuk'));
        }

        $seragam = $this->viewer('lihat', ['dasbor.view', 'pendaftar.view', 'seragam.proses']);
        $this->actingAs($seragam)->get('admin/pendaftar')->assertOK();
        $this->actingAs($seragam)->get('admin/pendaftar/' . $id)->assertOK();
        $this->actingAs($seragam)->get('admin/pendaftar/tambah')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($seragam)->get('admin/pendaftar/' . $id . '/ubah')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($seragam)->post('admin/pendaftar/' . $id . '/status', ['status' => 'cancelled'])->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($seragam)->post('admin/pendaftar/' . $id . '/tautan')->assertRedirectTo(site_url('admin/akses-ditolak'));

        $panitia = $this->makeUser('panitia');
        $this->actingAs($panitia)->get('admin/pendaftar/tambah')->assertOK();
        $this->actingAs($panitia)->get('admin/pendaftar/' . $id . '/ubah')->assertOK();
        $this->actingAs($panitia)->post('admin/pendaftar/' . $id . '/hapus')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->assertNotNull(model(ApplicantModel::class)->find($id));

        $this->actingAs($this->makeUser('admin'))->post('admin/pendaftar/' . $id . '/hapus')->assertRedirectTo(site_url('admin/pendaftar'));
        $this->assertNull(model(ApplicantModel::class)->find($id));
        $this->assertNotNull(model(ApplicantModel::class)->withDeleted()->find($id));
    }

    public function testListSearchFiltersAndMaskedNik(): void
    {
        $waveId = $this->makeWave();
        $this->register(['wave_id' => $waveId]);
        $this->register(['full_name' => 'Citra Lestari', 'nik' => '3319000000000009', 'nisn' => '8888888888']);
        $user = $this->viewer('lihat');

        $all = $this->actingAs($user)->get('admin/pendaftar');
        $all->assertSee('Ahmad Fauzi');
        $all->assertSee('Citra Lestari');
        $all->assertSee('3319••••••••0001');
        $all->assertDontSee('3319011705150001');

        $search = $this->actingAs($user)->get('admin/pendaftar?q=citra');
        $search->assertSee('Citra Lestari');
        $search->assertDontSee('Ahmad Fauzi');

        $this->actingAs($user)->get('admin/pendaftar?q=8888888888')->assertSee('Citra Lestari');
        $this->actingAs($user)->get('admin/pendaftar?ganda=1')->assertSee('Pendaftar tidak ditemukan');
        $this->actingAs($user)->get('admin/pendaftar?cara=online')->assertSee('Pendaftar tidak ditemukan');
    }

    public function testOfflineRegistrationAsksToConfirmDuplicates(): void
    {
        $this->makeWave();
        $panitia = $this->makeUser('panitia');
        $this->register();

        $first = $this->actingAs($panitia)->post('admin/pendaftar/tambah', $this->applicantInput(['nisn' => '7777777777', 'full_name' => 'Anak Lain']));
        $first->assertRedirect();
        $first->assertSessionHas('duplicates');
        $this->assertSame(1, model(ApplicantModel::class)->countAllResults());

        $second = $this->actingAs($panitia)->post('admin/pendaftar/tambah', $this->applicantInput(['nisn' => '7777777777', 'full_name' => 'Anak Lain', 'confirm_duplicate' => '1', 'notes' => 'Kembar']));
        $second->assertSessionHas('private_link');
        $row = model(ApplicantModel::class)->where('full_name', 'Anak Lain')->first();
        $this->assertSame('offline', $row['method']);
        $this->assertSame((int) $panitia->id, (int) $row['created_by']);
        $this->assertSame('1', (string) $row['possible_duplicate']);
        $second->assertRedirectTo(site_url('admin/pendaftar/' . $row['id']));
    }

    public function testOfflineValidation(): void
    {
        $result = $this->actingAs($this->makeUser('panitia'))->post('admin/pendaftar/tambah', $this->applicantInput([
            'full_name' => '', 'nik' => '123', 'nisn' => '', 'parent_phone' => '12345', 'father_name' => '', 'mother_name' => '', 'previous_school_name' => '',
        ]));

        $result->assertSessionHas('errors');
        $errors = session('errors');

        foreach (['full_name', 'nik', 'nisn', 'parent_phone', 'father_name', 'previous_school_name'] as $field) {
            $this->assertArrayHasKey($field, $errors, $field);
        }

        $this->assertSame('NIK harus 16 angka.', $errors['nik']);
        $this->assertSame(0, model(ApplicantModel::class)->countAllResults());
    }

    public function testDetailEditStatusReviewAndLink(): void
    {
        $this->makeWave();
        $admin = $this->makeUser('admin');
        $this->register();
        $b  = $this->register(['full_name' => 'Ahmad Fauzi', 'nik' => '', 'nisn' => '5555555555']);
        $id = (int) $b['id'];

        $detail = $this->actingAs($admin)->get('admin/pendaftar/' . $id);
        $detail->assertSee($b['registration_number']);
        $detail->assertSee('Mungkin data ganda');
        $detail->assertSee('Nama dan tanggal lahir sama');

        $this->actingAs($admin)->post('admin/pendaftar/' . $id . '/ubah', $this->applicantInput(['full_name' => 'Ahmad Fauzan', 'nik' => '', 'nisn' => '5555555555', 'address' => 'Jl. Baru 9']))
            ->assertRedirectTo(site_url('admin/pendaftar/' . $id));
        $row = model(ApplicantModel::class)->find($id);
        $this->assertSame('Jl. Baru 9', $row['address']);
        $this->assertSame('ahmad fauzan', $row['name_key']);

        $this->actingAs($admin)->post('admin/pendaftar/' . $id . '/data-ganda', ['note' => ''])->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/pendaftar/' . $id . '/data-ganda', ['note' => 'Anak berbeda'])->assertSessionHas('message');
        $this->assertSame('0', (string) model(ApplicantModel::class)->find($id)['possible_duplicate']);

        $this->actingAs($admin)->post('admin/pendaftar/' . $id . '/status', ['status' => 'bogus'])->assertSessionHas('error');
        $this->actingAs($admin)->post('admin/pendaftar/' . $id . '/status', ['status' => 'verified', 'note' => 'OK'])->assertSessionHas('message');
        $this->assertSame('verified', model(ApplicantModel::class)->find($id)['status']);

        $old = model(ApplicantModel::class)->find($id)['access_token_hash'];
        $this->actingAs($admin)->post('admin/pendaftar/' . $id . '/tautan')->assertSessionHas('private_link');
        $this->assertNotSame($old, model(ApplicantModel::class)->find($id)['access_token_hash']);
        $this->seeInDatabase('audit_logs', ['event' => 'private_link_created', 'subject_id' => (string) $id]);

        $this->actingAs($admin)->get('admin/pendaftar/999999')->assertRedirectTo(site_url('admin/pendaftar'));
    }

    public function testOfflineFormAsksAboutTheGuardianAndTheDetailShowsIt(): void
    {
        $this->makeWave();
        $panitia = $this->makeUser('panitia');
        setting('Pendaftaran.requiredFields', ['nisn', 'guardian_relation']);

        $form = $this->actingAs($panitia)->get('admin/pendaftar/tambah');
        $form->assertSee('Apakah calon murid tinggal bersama atau diasuh oleh wali?');
        $form->assertSee('Kontak orang tua atau wali');

        // "Ada wali" without the required guardian fields is refused.
        $this->actingAs($panitia)->post('admin/pendaftar/tambah', $this->applicantInput(['has_guardian' => '1']))->assertSessionHas('errors');
        $this->assertArrayHasKey('guardian_relation', session('errors'));
        $this->assertSame(0, model(ApplicantModel::class)->countAllResults());

        // "Tidak ada wali": the required guardian fields are not asked and stray values are dropped.
        $this->actingAs($panitia)->post('admin/pendaftar/tambah', $this->applicantInput(['has_guardian' => '0', 'guardian_name' => 'Salah isi']))->assertSessionHas('message');
        $row = model(ApplicantModel::class)->first();
        $this->assertSame('0', (string) $row['has_guardian']);
        $this->assertNull($row['guardian_name']);

        $detail = $this->actingAs($panitia)->get('admin/pendaftar/' . $row['id']);
        $detail->assertSee('Tidak ada wali');
        $detail->assertDontSee('Hubungan wali dengan calon murid');

        // Staff switch to "Ada wali" on the edit page.
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post('admin/pendaftar/' . $row['id'] . '/ubah', $this->applicantInput([
            'has_guardian' => '1', 'guardian_name' => 'Khadijah Umar', 'guardian_relation' => 'Nenek',
        ]))->assertRedirectTo(site_url('admin/pendaftar/' . $row['id']));

        $detail = $this->actingAs($admin)->get('admin/pendaftar/' . $row['id']);
        $detail->assertSee('Ada wali');
        $detail->assertSee('Khadijah Umar');
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'applicants', 'subject_id' => (string) $row['id']]);
    }
}
