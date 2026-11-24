<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Berkas\Database\Seeds\InstallSeeder;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;
use Tests\Support\Berkas\BerkasTestHelpers;

/**
 * Required documents and image settings (/admin/pengaturan/berkas).
 *
 * @internal
 */
final class BerkasPengaturanTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use BerkasTestHelpers;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $this->resetServices();
        $this->seedTypes();
    }

    protected function tearDown(): void
    {
        $this->removeStoredFiles();
        parent::tearDown();
    }

    public function testInstallSeederIsIdempotentAndOrdered(): void
    {
        (new InstallSeeder(config('Database')))->run();

        $active = model(DocumentTypeModel::class)->active();
        $this->assertSame(['kk', 'akta', 'foto', 'sk-aktif', 'rapor-skl'], array_column($active, 'code'));
        $this->assertSame('1', (string) $active[4]['may_follow']);
    }

    public function testPermissions(): void
    {
        $this->get('admin/pengaturan/berkas')->assertRedirectTo(site_url('masuk'));
        $this->actingAs($this->makeUser('panitia1', 'panitia'))->get('admin/pengaturan/berkas')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($this->makeUser('panitia2', 'panitia'))->post('admin/pengaturan/berkas/tambah', ['name' => 'X'])->assertRedirectTo(site_url('admin/akses-ditolak'));

        $page = $this->actingAs($this->makeUser('admin1', 'admin'))->get('admin/pengaturan/berkas');
        $page->assertOK();
        $page->assertSee('Kartu Keluarga');
        $page->assertSee('Boleh menyusul');
        $page->assertSee('Ukuran Foto dan Batas Waktu');
    }

    public function testAdminAddsAndEditsAType(): void
    {
        $admin = $this->makeUser('admin1', 'admin');

        $this->actingAs($admin)->get('admin/pengaturan/berkas/tambah')->assertOK();
        $this->actingAs($admin)->post('admin/pengaturan/berkas/tambah', [
            'name'        => 'Surat Keterangan Sehat', 'code' => 'Sehat', 'description' => 'Dari puskesmas',
            'max_size_kb' => '1024', 'formats' => ['jpg', 'pdf'], 'is_required' => '0', 'may_follow' => '1', 'is_active' => '1',
        ])->assertRedirectTo(site_url('admin/pengaturan/berkas'));

        $type = $this->type('sehat');
        $this->assertSame('jpg,jpeg,pdf', $type['extensions']);
        $this->assertSame('0', (string) $type['is_required']);
        $this->assertSame('sehat', array_column(model(DocumentTypeModel::class)->active(), 'code')[5]);

        $this->actingAs($admin)->get("admin/pengaturan/berkas/{$type['id']}/ubah")->assertSee('Surat Keterangan Sehat');
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$type['id']}/ubah", [
            'name' => 'Surat Sehat', 'code' => 'sehat', 'max_size_kb' => '2048', 'formats' => ['pdf'], 'is_required' => '1', 'is_active' => '0',
        ])->assertRedirectTo(site_url('admin/pengaturan/berkas'));

        $type = $this->type('sehat');
        $this->assertSame(['Surat Sehat', 'pdf', '1', '0', '0'], [$type['name'], $type['extensions'], (string) $type['is_required'], (string) $type['is_active'], (string) $type['may_follow']]);
        $this->assertNotContains('sehat', array_column(model(DocumentTypeModel::class)->active(), 'code'));
    }

    public function testValidationErrors(): void
    {
        $admin  = $this->makeUser('admin1', 'admin');
        $result = $this->actingAs($admin)->post('admin/pengaturan/berkas/tambah', [
            'name' => '', 'code' => 'kk', 'max_size_kb' => '50', 'formats' => [],
        ]);

        $result->assertRedirect();
        $errors = $_SESSION['errors'] ?? [];
        $this->assertArrayHasKey('name', $errors);
        $this->assertSame(lang('Berkas.fieldCodeTaken'), $errors['code']);
        $this->assertArrayHasKey('max_size_kb', $errors);
        $this->assertSame(lang('Berkas.fieldFormatsError'), $errors['formats']);
    }

    public function testMoveSwapsNeighbours(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $foto  = $this->type('foto');

        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$foto['id']}/pindah", ['arah' => 'naik'])->assertRedirect();
        $this->assertSame(['kk', 'foto', 'akta', 'sk-aktif', 'rapor-skl'], array_column(model(DocumentTypeModel::class)->active(), 'code'));

        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$foto['id']}/pindah", ['arah' => 'naik'])->assertRedirect();
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$foto['id']}/pindah", ['arah' => 'naik'])->assertRedirect();
        $this->assertSame(['foto', 'kk', 'akta', 'sk-aktif', 'rapor-skl'], array_column(model(DocumentTypeModel::class)->active(), 'code'));
    }

    public function testDeleteOnlyWhenUnused(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $kk    = $this->type('kk');
        $akta  = $this->type('akta');
        $app   = $this->makeApplicant();
        model(ApplicantDocumentModel::class)->insert(['applicant_id' => $app['id'], 'document_type_id' => $kk['id'], 'status' => 'to_follow']);

        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$kk['id']}/hapus")->assertSessionHas('error', lang('Berkas.deleteInUse'));
        $this->assertNotNull(model(DocumentTypeModel::class)->find($kk['id']));

        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$akta['id']}/hapus")->assertSessionHas('message');
        $this->assertNull(model(DocumentTypeModel::class)->find($akta['id']));
    }

    public function testImageSettingsAreSavedAndAudited(): void
    {
        $admin = $this->makeUser('admin1', 'admin');

        $this->actingAs($admin)->post('admin/pengaturan/berkas', ['imageMaxEdge' => '1200', 'imageQuality' => '75', 'deadline' => '2027-03-31'])
            ->assertRedirectTo(site_url('admin/pengaturan/berkas'));

        $this->assertSame(1200, (int) setting('Berkas.imageMaxEdge'));
        $this->assertSame(75, (int) setting('Berkas.imageQuality'));
        $this->assertSame('2027-03-31', setting('Berkas.deadline'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_id' => 'Berkas']);

        $this->actingAs($admin)->post('admin/pengaturan/berkas', ['imageMaxEdge' => '100', 'imageQuality' => '99', 'deadline' => 'besok'])->assertSessionHas('errors');
        $this->assertSame(1200, (int) setting('Berkas.imageMaxEdge'));
    }
}
