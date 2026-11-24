<?php

declare(strict_types=1);

use App\Libraries\ModuleRegistry;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Modules;
use Config\Services;

/**
 * A module switched off on Pengaturan → Fitur loses its pages (404), not only
 * its menu entries; switching it on brings them back (D-112).
 *
 * @internal
 */
final class ModuleRoutesTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
        $this->resetServices();
    }

    protected function tearDown(): void
    {
        // Leave no signed-in user behind for the next test class (Shield keeps it in the session).
        $_SESSION = [];
        $this->resetServices();
        parent::tearDown();
    }

    /**
     * Loads the routes as a request would while these modules are switched off.
     *
     * @param list<string> $disabled
     */
    private function switchOff(array $disabled): void
    {
        Services::injectMock('modules', new ModuleRegistry(APPPATH . 'Modules', config('Features')->core, $disabled));
        Services::resetSingle('routes');
        Services::resetSingle('router');
        service('routes')->loadRoutes();
    }

    private function superAdmin(): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'super', 'email' => 'super@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup('superadmin');

        return $user;
    }

    public function testRouteDiscoveryIsOff(): void
    {
        // Each module is its own namespace, so discovery would load the routes of switched-off modules too.
        $this->assertFalse((new Modules())->shouldDiscover('routes'));
    }

    public function testSwitchedOffModuleAnswers404OnPublicAndAdminPages(): void
    {
        $this->switchOff(['Situs', 'Tes']);
        $admin = $this->superAdmin();

        $this->get('persyaratan')->assertStatus(404);
        $this->get('tanya-jawab')->assertStatus(404);
        $this->actingAs($admin)->get('admin/tanya-jawab')->assertStatus(404);
        $this->actingAs($admin)->get('admin/tes/ruang')->assertStatus(404);
        $this->assertFalse(route_to('situs.persyaratan'));
        $this->assertFalse(route_to('tes.ruang'));
    }

    public function testSwitchingBackOnRestoresThePages(): void
    {
        $this->switchOff(['Situs', 'Tes']);
        $this->get('persyaratan')->assertStatus(404);

        $this->switchOff([]);
        $admin = $this->superAdmin();

        $this->get('persyaratan')->assertOK();
        $this->actingAs($admin)->get('admin/tanya-jawab')->assertOK();
        $this->actingAs($admin)->get('admin/tes/ruang')->assertOK();
        $this->assertNotFalse(route_to('situs.persyaratan'));
    }

    public function testModulesThatRequireASwitchedOffModuleLoseTheirPagesToo(): void
    {
        // Situs, Berkas, Kartu and Tes require Pendaftaran.
        $this->switchOff(['Pendaftaran']);

        $this->get('daftar')->assertStatus(404);
        $this->get('persyaratan')->assertStatus(404);
        $this->actingAs($this->superAdmin())->get('admin/tes/ruang')->assertStatus(404);
    }

    public function testCoreModulesCannotLoseTheirPages(): void
    {
        $this->switchOff(['Pengaturan', 'Pengguna', 'Profil', 'Panduan', 'Riwayat']);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get('admin/pengaturan/fitur')->assertOK();
        $this->actingAs($admin)->get('admin/pengguna')->assertOK();
        $this->actingAs($admin)->get('admin/riwayat')->assertOK();
    }

    public function testPagesThatLinkToASwitchedOffModuleStillRender(): void
    {
        $this->switchOff(['Situs', 'Tes', 'Kartu', 'Berkas']);

        $home = $this->get('/');
        $home->assertOK();
        $this->assertStringNotContainsString(site_url('persyaratan'), $home->getBody());

        $this->get('daftar')->assertOK();
        $this->get('cek-status')->assertOK();

        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('admin')->assertOK();
        $this->actingAs($admin)->get('admin/pendaftar')->assertOK();
        $this->actingAs($admin)->get('admin/pengaturan/fitur')->assertOK();
    }

    public function testEveryModuleSwitchedOffAloneStillRendersTheMainPages(): void
    {
        $admin = $this->superAdmin();

        foreach (service('modules')->all() as $module) {
            if (service('modules')->isCore($module)) {
                continue;
            }

            $this->switchOff([$module]);

            $this->get('/')->assertOK();
            $this->actingAs($admin)->get('admin')->assertOK();
            $this->actingAs($admin)->get('admin/pengaturan/fitur')->assertOK();
        }
    }
}
