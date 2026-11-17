<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Panduan\Libraries\GuideBuilder;

/**
 * Public guide pages (/panduan).
 *
 * @internal
 */
final class PanduanTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    public function testIndexListsEveryRole(): void
    {
        $result = $this->get('panduan');

        $result->assertOK();
        $result->assertSee('Calon Murid dan Orang Tua');
        $result->assertSee('Panitia Pendaftaran');
        $result->assertSee('Super Admin');
    }

    public function testStaffGuideIncludesSharedSections(): void
    {
        $result = $this->get('panduan/panitia-pendaftaran');

        $result->assertOK();
        $result->assertSee('Apa yang bisa Anda lakukan');
        $result->assertSee('Verifikasi dua langkah');
        $result->assertDontSee('Menambah pengguna');
    }

    public function testSuperAdminGuideIncludesAdminSections(): void
    {
        $result = $this->get('panduan/super-admin');

        $result->assertSee('Mewajibkan verifikasi dua langkah');
        $result->assertSee('Menambah pengguna');
    }

    public function testPublicGuideHasNoStaffSections(): void
    {
        $this->get('panduan/calon-murid')->assertDontSee('Masuk ke dasbor');
    }

    public function testGuidesShowTheirCoverIllustration(): void
    {
        $this->get('panduan')->assertSee('guide/panduan-calon-murid.webp');
        $this->get('panduan/calon-murid')->assertSee('guide/panduan-calon-murid.webp');
        $this->get('panduan/panitia-pendaftaran')->assertSee('guide/panduan-petugas.webp');
    }

    public function testUnknownRoleIsNotFound(): void
    {
        $this->get('panduan/tidak-ada')->assertStatus(404);
    }

    public function testHeadingsGetAnchors(): void
    {
        $builder = new GuideBuilder(service('modules'), require APPPATH . 'Modules/Panduan/Config/Roles.php');
        $guide   = $builder->render('admin-sekolah');

        $this->assertNotEmpty($guide['toc']);
        $this->assertStringContainsString('<h2 id="' . $guide['toc'][0]['id'] . '">', $guide['html']);
    }
}
