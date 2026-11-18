<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Referensi\ReferensiFixtures;

/**
 * School import page (/admin/referensi/sekolah/impor).
 *
 * @internal
 */
final class ReferensiImporTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use ReferensiFixtures;

    protected $namespace;
    protected $refresh      = true;
    private ?string $upload = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetSignedInUser();
    }

    protected function tearDown(): void
    {
        service('superglobals')->setFilesArray([]);

        if ($this->upload !== null && is_file($this->upload)) {
            unlink($this->upload);
        }

        parent::tearDown();
    }

    private function attach(string $source, string $clientName): void
    {
        $this->upload = tempnam(sys_get_temp_dir(), 'imp');
        copy($source, $this->upload);
        service('superglobals')->setFilesArray(['berkas' => ['name' => $clientName, 'type' => 'text/csv', 'tmp_name' => $this->upload, 'error' => UPLOAD_ERR_OK, 'size' => filesize($this->upload)]]);
    }

    public function testPageAndTemplate(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');

        $page = $this->actingAs($admin)->get('admin/referensi/sekolah/impor');
        $page->assertOK();
        $page->assertSee(lang('Referensi.importSubmit'));

        $csv = $this->actingAs($admin)->get('admin/referensi/sekolah/impor/contoh');
        $csv->assertOK();
        $this->assertStringContainsString('text/csv', $csv->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('NPSN,"Nama Sekolah",Jenjang', $csv->getBody());
    }

    public function testUploadImportsAndShowsReport(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');
        $this->attach(self::fixture('sekolah.csv'), 'sekolah.csv');

        $result = $this->actingAs($admin)->post('admin/referensi/sekolah/impor');

        $result->assertRedirectTo(site_url('admin/referensi/sekolah/impor'));
        $result->assertSessionHas('warning', lang('Referensi.importHasErrors', [3]));
        $report = session('import_report');
        $this->assertSame([2, 1, 3], [$report['created'], $report['updated'], $report['failed']]);
        $this->assertSame('sekolah.csv', $report['file']);
        $this->seeInDatabase('ref_sekolah', ['npsn' => '20319101', 'source' => 'import']);
        $this->seeInDatabase('audit_logs', ['event' => 'schools_imported', 'subject_type' => 'ref_sekolah']);

        $page = $this->actingAs($admin)->withSession(['import_report' => $report])->get('admin/referensi/sekolah/impor');
        $page->assertSee(lang('Referensi.importErrorsTitle'));
        $page->assertSee(lang('Referensi.rowBadNpsn'));
    }

    public function testWrongFilesAreRefusedKindly(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');

        $this->actingAs($admin)->post('admin/referensi/sekolah/impor')->assertRedirect();
        $this->assertSame(lang('Referensi.importNoFile'), session('errors')['berkas']);

        $this->attach(self::fixture('sekolah.csv'), 'sekolah.pdf');
        $this->actingAs($admin)->post('admin/referensi/sekolah/impor');
        $this->assertSame(lang('Referensi.importBadType'), session('errors')['berkas']);

        $this->attach(self::fixture('kolom-salah.csv'), 'salah.csv');
        $this->actingAs($admin)->post('admin/referensi/sekolah/impor');
        $this->assertSame(lang('Referensi.importBadHeader'), session('errors')['berkas']);

        $this->dontSeeInDatabase('audit_logs', ['event' => 'schools_imported']);
    }
}
