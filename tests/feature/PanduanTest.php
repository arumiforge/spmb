<?php

declare(strict_types=1);

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Panduan\Libraries\GuideBuilder;

/**
 * Guide pages: the public guide for applicants and parents (/panduan) and the staff
 * guides inside the dashboard (/admin/panduan), each role its own (D-097).
 *
 * @internal
 */
final class PanduanTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    private function makeUser(string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => $group . 'x', 'email' => $group . '@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => ucfirst($group) . ' Test']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);
        $user->activate();

        return $user;
    }

    public function testIndexOpensTheOnlyPublicGuide(): void
    {
        $this->get('panduan')->assertRedirectTo(site_url('panduan/calon-murid'));
    }

    public function testPublicGuideHasNoStaffSectionsOrLinks(): void
    {
        $result = $this->get('panduan/calon-murid');

        $result->assertOK();
        $result->assertDontSee('Masuk ke dasbor');
        // Staff guides are not offered on the public site.
        $result->assertDontSee('panduan/panitia');
        $result->assertDontSee('panduan/admin-sekolah');
        $result->assertDontSee('panduan/super-admin');
    }

    public function testStaffGuidesAreNotPublic(): void
    {
        foreach (['panitia', 'admin-sekolah', 'super-admin'] as $slug) {
            $this->get('panduan/' . $slug)->assertRedirectTo(site_url('admin/panduan/' . $slug));
            $this->get('admin/panduan/' . $slug)->assertRedirectTo(site_url('masuk'));
        }
    }

    public function testOldRoleGuidesRedirectIntoTheDashboard(): void
    {
        $this->get('panduan/panitia-pendaftaran')->assertRedirectTo(site_url('admin/panduan/panitia'));
        $this->get('panduan/petugas-tes')->assertRedirectTo(site_url('admin/panduan/panitia'));
        $this->get('panduan/kepala-sekolah')->assertRedirectTo(site_url('admin/panduan/admin-sekolah'));
    }

    public function testPanitiaReadsOnlyTheirOwnGuide(): void
    {
        $user = $this->makeUser('panitia');

        $this->actingAs($user)->get('admin/panduan')->assertRedirectTo(site_url('admin/panduan/panitia'));

        $result = $this->actingAs($user)->get('admin/panduan/panitia');
        $result->assertOK();
        $result->assertSee('Panduan Panitia');
        $result->assertSee('Apa yang bisa Anda lakukan');
        $result->assertSee('Verifikasi dua langkah');
        $result->assertDontSee('Menambah pengguna');
        $result->assertSee('guide/panduan-petugas.webp');

        $this->actingAs($user)->get('admin/panduan/admin-sekolah')->assertStatus(404);
        $this->actingAs($user)->get('admin/panduan/super-admin')->assertStatus(404);
    }

    public function testAdminSekolahReadsOnlyTheirOwnGuide(): void
    {
        $user = $this->makeUser('admin');

        $this->actingAs($user)->get('admin/panduan')->assertRedirectTo(site_url('admin/panduan/admin-sekolah'));
        $this->actingAs($user)->get('admin/panduan/admin-sekolah')->assertSee('Menambah pengguna');
        $this->actingAs($user)->get('admin/panduan/panitia')->assertStatus(404);
        $this->actingAs($user)->get('admin/panduan/super-admin')->assertStatus(404);
    }

    public function testSuperAdminReadsEveryStaffGuide(): void
    {
        $user = $this->makeUser('superadmin');

        $list = $this->actingAs($user)->get('admin/panduan');
        $list->assertOK();
        $list->assertSee('Panduan Panitia');
        $list->assertSee('Panduan Admin Sekolah');
        $list->assertSee('Panduan Super Admin');
        $list->assertSee(site_url('panduan/calon-murid'));

        $guide = $this->actingAs($user)->get('admin/panduan/super-admin');
        $guide->assertSee('Mewajibkan verifikasi dua langkah');
        $guide->assertSee('Menambah pengguna');
        $this->actingAs($user)->get('admin/panduan/panitia')->assertOK();
    }

    public function testGuidesHaveASearchBoxAndCover(): void
    {
        $result = $this->get('panduan/calon-murid');

        $result->assertSee('guide/panduan-calon-murid.webp');
        $result->assertSee('data-cari-halaman');
        $result->assertSee('data-cari-wadah="#isi-panduan"');
        $result->assertSee('id="isi-panduan"');
        $result->assertSee(lang('Panduan.searchLabel'));
    }

    public function testUnknownRoleIsNotFound(): void
    {
        $this->get('panduan/tidak-ada')->assertStatus(404);
        $this->actingAs($this->makeUser('panitia'))->get('admin/panduan/tidak-ada')->assertStatus(404);
    }

    public function testHeadingsGetAnchors(): void
    {
        $builder = new GuideBuilder(service('modules'), require APPPATH . 'Modules/Panduan/Config/Roles.php');
        $guide   = $builder->render('admin-sekolah');

        $this->assertNotEmpty($guide['toc']);
        $this->assertStringContainsString('<h2 id="' . $guide['toc'][0]['id'] . '">', $guide['html']);
    }
}
