<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Berkas\Database\Migrations\AddSkAktifDocumentType;
use Modules\Berkas\Database\Seeds\InstallSeeder;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\TemplateStorage;
use Tests\Support\Berkas\BerkasTestHelpers;

/**
 * Default type "Surat Keterangan Aktif dari SD/MI" and example files per
 * document type ("Unduh contoh"): upload in the type form, switch, public
 * download and links. The client pack part is in BerkasContohBrandingTest.
 *
 * @internal
 */
final class BerkasContohTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use BerkasTestHelpers;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    /**
     * @var list<string>
     */
    private array $madeFiles = [];

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

        foreach ($this->madeFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    // ---------- Default type sk-aktif ----------

    public function testSeederAddsSkAktifBeforeRaporAndStaysIdempotent(): void
    {
        (new InstallSeeder(config('Database')))->run();

        $active = model(DocumentTypeModel::class)->active();
        $this->assertSame(['kk', 'akta', 'foto', 'sk-aktif', 'rapor-skl'], array_column($active, 'code'));

        $sk = $this->type('sk-aktif');
        $this->assertSame(['Surat Keterangan Aktif dari SD/MI', '1', '1', '2048', 'jpg,jpeg,png,pdf'], [$sk['name'], (string) $sk['is_required'], (string) $sk['may_follow'], (string) $sk['max_size_kb'], $sk['extensions']]);
        $this->assertStringContainsString('kepala SD/MI', (string) $sk['description']);
        $this->assertSame('0', (string) $sk['template_enabled']);
    }

    public function testPasFotoAcceptsImagesOnly(): void
    {
        $foto = $this->type('foto');
        $this->assertSame(['image/jpeg', 'image/png'], DocumentTypeModel::mimesOf($foto));
        $this->assertStringContainsString('latar polos', (string) $foto['description']);

        $applicant = $this->makeApplicant();
        $this->fakeUpload('sample.pdf', 'foto.pdf');
        $this->withSession($this->grantSession((int) $applicant['id']))->post("cek-status/berkas/{$foto['id']}")
            ->assertSessionHas('berkas_error', [(int) $foto['id'] => lang('Berkas.errWrongFormat', ['JPG atau PNG'])]);
    }

    public function testMigrationAddsSkAktifToOlderInstallationsOnce(): void
    {
        $db = db_connect();
        $db->table('document_types')->where('code', 'sk-aktif')->delete();
        // An older installation where the school moved things around.
        $db->table('document_types')->where('code', 'rapor-skl')->update(['sort_order' => 20]);
        $db->table('document_types')->where('code', 'akta')->update(['sort_order' => 35]);

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame(1, $db->table('document_types')->where('code', 'sk-aktif')->countAllResults());
        $this->assertSame(['kk', 'sk-aktif', 'rapor-skl', 'foto', 'akta'], array_column(model(DocumentTypeModel::class)->active(), 'code'));
        // The migration's frozen copy matches the seeder's default.
        $sk = $this->type('sk-aktif');

        foreach ($this->defaultSkAktif() as $key => $value) {
            $this->assertSame($value, (string) $sk[$key], $key);
        }
    }

    public function testMigrationKeepsADeletedSkAktifDeleted(): void
    {
        model(DocumentTypeModel::class)->delete($this->type('sk-aktif')['id']);

        $this->migration()->up();

        $this->assertNotContains('sk-aktif', array_column(model(DocumentTypeModel::class)->active(), 'code'));
        $this->assertSame(1, db_connect()->table('document_types')->where('code', 'sk-aktif')->countAllResults());
    }

    public function testMigrationLeavesAnEmptyTableToTheSeeder(): void
    {
        db_connect()->table('document_types')->emptyTable();

        $this->migration()->up();

        $this->assertSame(0, db_connect()->table('document_types')->countAllResults());
    }

    public function testMigrationPutsSkAktifLastWithoutRapor(): void
    {
        $db = db_connect();
        $db->table('document_types')->whereIn('code', ['sk-aktif', 'rapor-skl'])->delete();

        $this->migration()->up();

        $this->assertSame(['kk', 'akta', 'foto', 'sk-aktif'], array_column(model(DocumentTypeModel::class)->active(), 'code'));
    }

    // ---------- Example file in the type form ----------

    public function testAdminUploadsTemplateAndApplicantsDownloadIt(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $sk    = $this->type('sk-aktif');

        $this->fakeUpload('sample.pdf', 'Contoh Surat Aktif.pdf', UPLOAD_ERR_OK, 'template');
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$sk['id']}/ubah", $this->form($sk, ['template_enabled' => '1', 'template_label' => 'Unduh contoh surat']))
            ->assertRedirectTo(site_url('admin/pengaturan/berkas'));

        $sk = $this->type('sk-aktif');
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{32}\.pdf\z/', (string) $sk['template_path']);
        $this->assertSame(['Contoh Surat Aktif.pdf', '1', 'Unduh contoh surat'], [$sk['template_name'], (string) $sk['template_enabled'], $sk['template_label']]);
        $this->assertFileEquals($this->fixture('sample.pdf'), WRITEPATH . 'uploads/berkas-contoh/' . $sk['template_path']);
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'document_types', 'subject_id' => $sk['id']]);
        $log = db_connect()->table('audit_logs')->where('subject_type', 'document_types')->where('event', 'updated')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertStringContainsString('template_enabled', (string) $log['new_values']);

        $this->clearUpload();
        $result = $this->get('berkas/contoh/sk-aktif');
        $result->assertOK();
        $result->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('attachment; filename="Contoh Surat Aktif.pdf"', $result->response()->getHeaderLine('Content-Disposition'));
        $result->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame(file_get_contents($this->fixture('sample.pdf')), $result->response()->getBody());

        // The admin list shows the badge.
        $this->actingAs($admin)->get('admin/pengaturan/berkas')->assertSee(lang('Berkas.badgeTemplateOn'));
    }

    public function testSwitchedOffTemplateIs404ButTheSchoolCanStillOpenIt(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $sk    = $this->withTemplate('sk-aktif', enabled: false);

        $this->get('berkas/contoh/sk-aktif')->assertStatus(404);
        $this->get('berkas/contoh/tidak-ada')->assertStatus(404);
        $this->get('berkas/contoh/kk')->assertStatus(404);

        $this->actingAs($admin)->get("admin/pengaturan/berkas/{$sk['id']}/contoh")->assertOK();
        $this->actingAs($admin)->get('admin/pengaturan/berkas')->assertSee(lang('Berkas.badgeTemplateOff'));

        // Switched on but the type hidden from applicants: still 404.
        model(DocumentTypeModel::class)->update($sk['id'], ['template_enabled' => 1, 'is_active' => 0]);
        $this->get('berkas/contoh/sk-aktif')->assertStatus(404);

        model(DocumentTypeModel::class)->update($sk['id'], ['is_active' => 1]);
        $this->get('berkas/contoh/sk-aktif')->assertOK();

        // The file is gone from disk: 404, not an error.
        @unlink(WRITEPATH . 'uploads/berkas-contoh/' . $sk['template_path']);
        $this->get('berkas/contoh/sk-aktif')->assertStatus(404);
    }

    public function testTemplatePermissions(): void
    {
        $sk = $this->withTemplate('sk-aktif');

        $this->get("admin/pengaturan/berkas/{$sk['id']}/contoh")->assertRedirectTo(site_url('masuk'));
        $this->actingAs($this->makeUser('panitia1', 'panitia'))->get("admin/pengaturan/berkas/{$sk['id']}/contoh")->assertRedirectTo(site_url('admin/akses-ditolak'));
    }

    public function testToggleOffKeepsTheFileAndHidesTheDownload(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $sk    = $this->withTemplate('sk-aktif');

        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$sk['id']}/ubah", $this->form($sk, ['template_enabled' => '0']))
            ->assertRedirectTo(site_url('admin/pengaturan/berkas'));

        $after = $this->type('sk-aktif');
        $this->assertSame([$sk['template_path'], '0'], [$after['template_path'], (string) $after['template_enabled']]);
        $this->assertFileExists(WRITEPATH . 'uploads/berkas-contoh/' . $sk['template_path']);
        $this->get('berkas/contoh/sk-aktif')->assertStatus(404);
    }

    public function testReplaceAndRemoveDeleteTheOldFile(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $sk    = $this->withTemplate('sk-aktif');
        $old   = WRITEPATH . 'uploads/berkas-contoh/' . $sk['template_path'];

        $this->fakeUpload($this->makeDocx(), 'format-surat.docx', UPLOAD_ERR_OK, 'template');
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$sk['id']}/ubah", $this->form($sk, ['template_enabled' => '1']))
            ->assertRedirectTo(site_url('admin/pengaturan/berkas'));

        $sk = $this->type('sk-aktif');
        $this->assertFileDoesNotExist($old);
        $this->assertStringEndsWith('.docx', (string) $sk['template_path']);
        $this->get('berkas/contoh/sk-aktif')->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->clearUpload();
        $current = WRITEPATH . 'uploads/berkas-contoh/' . $sk['template_path'];
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$sk['id']}/ubah", $this->form($sk, ['template_enabled' => '1', 'remove_template' => '1']))
            ->assertRedirectTo(site_url('admin/pengaturan/berkas'));

        $sk = $this->type('sk-aktif');
        $this->assertSame([null, null, '0'], [$sk['template_path'], $sk['template_name'], (string) $sk['template_enabled']]);
        $this->assertFileDoesNotExist($current);
    }

    public function testTemplateValidation(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $kk    = $this->type('kk');

        // Switched on without a file.
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$kk['id']}/ubah", $this->form($kk, ['template_enabled' => '1']))
            ->assertSessionHas('errors', ['template' => lang('Berkas.templateErrNeedFile')]);

        // A text file named .pdf: the real format decides.
        $fake = $this->madeFile('pdf', 'Bukan PDF.');
        $this->fakeUpload($fake, 'contoh.pdf', UPLOAD_ERR_OK, 'template');
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$kk['id']}/ubah", $this->form($kk, ['template_enabled' => '1']))
            ->assertSessionHas('errors', ['template' => lang('Berkas.templateErrFormat')]);

        // A format that is not offered.
        $this->fakeUpload('sample.pdf', 'contoh.exe', UPLOAD_ERR_OK, 'template');
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$kk['id']}/ubah", $this->form($kk))
            ->assertSessionHas('errors', ['template' => lang('Berkas.templateErrFormat')]);

        // A zip that is not a Word document.
        $zip     = $this->madeFile('docx', '');
        $archive = new ZipArchive();
        $archive->open($zip, ZipArchive::OVERWRITE);
        $archive->addFromString('isi.txt', 'bukan dokumen');
        $archive->close();
        $this->fakeUpload($zip, 'contoh.docx', UPLOAD_ERR_OK, 'template');
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$kk['id']}/ubah", $this->form($kk))
            ->assertSessionHas('errors', ['template' => lang('Berkas.templateErrFormat')]);

        // Too large.
        $big = $this->madeFile('pdf', "%PDF-1.4\n" . str_repeat('0', TemplateStorage::MAX_KB * 1024));
        $this->fakeUpload($big, 'besar.pdf', UPLOAD_ERR_OK, 'template');
        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$kk['id']}/ubah", $this->form($kk))
            ->assertSessionHas('errors', ['template' => lang('Berkas.errTooLarge', ['5 MB'])]);

        $this->assertNull($this->type('kk')['template_path']);
        $this->assertSame([], glob(WRITEPATH . 'uploads/berkas-contoh/*.exe') ?: []);
    }

    public function testNewTypeWithTemplate(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $this->fakeUpload('transparent.png', 'contoh-foto.png', UPLOAD_ERR_OK, 'template');

        $this->actingAs($admin)->post('admin/pengaturan/berkas/tambah', [
            'name'        => 'Foto Rumah', 'code' => 'rumah', 'max_size_kb' => '1024', 'formats' => ['jpg'],
            'is_required' => '0', 'is_active' => '1', 'template_enabled' => '1',
        ])->assertRedirectTo(site_url('admin/pengaturan/berkas'));

        $type = $this->type('rumah');
        $this->assertStringEndsWith('.png', (string) $type['template_path']);
        $this->clearUpload();
        $this->get('berkas/contoh/rumah')->assertHeader('Content-Type', 'image/png');
    }

    public function testDeletingATypeRemovesItsTemplate(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $akta  = $this->withTemplate('akta');
        $file  = WRITEPATH . 'uploads/berkas-contoh/' . $akta['template_path'];

        $this->actingAs($admin)->post("admin/pengaturan/berkas/{$akta['id']}/hapus")->assertSessionHas('message');
        $this->assertFileDoesNotExist($file);
    }

    // ---------- Links ----------

    public function testLinksShowOnlyWhenTheTemplateIsOn(): void
    {
        $this->loadRoutes();
        $link      = site_url('berkas/contoh/sk-aktif');
        $applicant = $this->makeApplicant();
        $session   = $this->grantSession((int) $applicant['id']);

        $this->assertStringNotContainsString($link, (string) $this->get('persyaratan')->response()->getBody());
        $this->assertStringNotContainsString($link, (string) $this->withSession($session)->get('cek-status/data')->response()->getBody());

        $this->withTemplate('sk-aktif', enabled: false);
        $this->assertStringNotContainsString($link, (string) $this->get('persyaratan')->response()->getBody());

        model(DocumentTypeModel::class)->update($this->type('sk-aktif')['id'], ['template_enabled' => 1]);
        $page = $this->get('persyaratan');
        $page->assertSee(lang('Berkas.templateDefaultLabel'));
        $this->assertStringContainsString($link, (string) $page->response()->getBody());
        $this->assertStringContainsString($link, (string) $this->withSession($session)->get('cek-status/data')->response()->getBody());

        // Only that type gets a link.
        $this->assertSame(1, substr_count((string) $page->response()->getBody(), 'berkas/contoh/'));
    }

    public function testClientPackShipsTheSkAktifExample(): void
    {
        $file = ROOTPATH . 'clients/smp-nu-al-maruf-kudus/berkas-contoh/sk-aktif.doc';

        $this->assertFileExists($file);
        $this->assertContains((string) (new finfo(FILEINFO_MIME_TYPE))->file($file), TemplateStorage::FORMATS['doc']);
    }

    // ---------- Helpers ----------

    private function migration(): AddSkAktifDocumentType
    {
        return new AddSkAktifDocumentType();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSkAktif(): array
    {
        foreach (InstallSeeder::DEFAULTS as $type) {
            if ($type['code'] === 'sk-aktif') {
                return array_map('strval', $type);
            }
        }

        return [];
    }

    /**
     * Stores a fixture as the type's example file.
     *
     * @return array<string, mixed>
     */
    private function withTemplate(string $code, bool $enabled = true, string $fixture = 'sample.pdf'): array
    {
        $type   = $this->type($code);
        $stored = (new TemplateStorage())->store($this->fixture($fixture), 'contoh-' . $fixture);
        model(DocumentTypeModel::class)->update($type['id'], $stored + ['template_enabled' => (int) $enabled]);

        return $this->type($code);
    }

    /**
     * Type form data as the edit page would post it.
     *
     * @param array<string, mixed> $type
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function form(array $type, array $extra = []): array
    {
        $formats = array_values(array_unique(array_map(static fn (string $e): string => $e === 'jpeg' ? 'jpg' : $e, DocumentTypeModel::extensionsOf($type))));

        return $extra + [
            'name'             => $type['name'], 'code' => $type['code'], 'description' => (string) $type['description'],
            'max_size_kb'      => (string) $type['max_size_kb'], 'formats' => $formats,
            'is_required'      => (string) $type['is_required'], 'may_follow' => (string) $type['may_follow'], 'is_active' => (string) $type['is_active'],
            'template_enabled' => (string) $type['template_enabled'], 'template_label' => (string) ($type['template_label'] ?? ''),
        ];
    }

    private function madeFile(string $ext, string $content): string
    {
        $path = sys_get_temp_dir() . '/berkas-contoh-test-' . bin2hex(random_bytes(6)) . '.' . $ext;
        file_put_contents($path, $content);
        $this->madeFiles[] = $path;

        return $path;
    }

    /**
     * A minimal Word document (.docx).
     */
    private function makeDocx(): string
    {
        $path = $this->madeFile('docx', '');
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Contoh</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        return $path;
    }
}
