<?php

declare(strict_types=1);

use App\Libraries\ModuleRegistry;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Switching feature modules on and off (Pengaturan → Fitur).
 *
 * Uses a fixture module folder (tests/_support/Modules) with one optional
 * module, Contoh, next to the core Pengaturan module.
 *
 * @internal
 */
final class FiturTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Start every test signed out (Shield keeps the signed-in user in the session).
        $_SESSION = [];
        $this->resetServices();

        // Load the real routes first, then swap the registry the controller sees.
        service('routes')->loadRoutes();
        setting()->forget('Features.disabled');
        $this->useFixtureModules([]);
    }

    protected function tearDown(): void
    {
        setting()->forget('Features.disabled');
        Services::resetSingle('modules');
        parent::tearDown();
    }

    /**
     * @param list<string> $disabled
     */
    private function useFixtureModules(array $disabled): void
    {
        Services::injectMock('modules', new ModuleRegistry(SUPPORTPATH . 'Modules', config('Features')->core, $disabled));
    }

    private function makeUser(string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'u' . $group, 'email' => $group . '@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }

    public function testSuperAdminSeesOptionalAndCoreModules(): void
    {
        $result = $this->actingAs($this->makeUser('superadmin'))->get('admin/pengaturan/fitur');

        $result->assertOK();
        $result->assertSee('Contoh Fitur');
        $result->assertSee('Fitur contoh untuk pengujian.');
        $result->assertSee('Selalu menyala');
        $this->assertMatchesRegularExpression('/name="aktif\[\]" value="Contoh"[^>]*checked/', $result->getBody());
        $this->assertStringNotContainsString('value="Pengaturan"', $result->getBody());
    }

    public function testAdminSekolahIsTurnedAway(): void
    {
        $this->actingAs($this->makeUser('admin'))->get('admin/pengaturan/fitur')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($this->makeUser('panitia'))->post('admin/pengaturan/fitur', [])->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->assertSame([], setting('Features.disabled'));
    }

    public function testGuestIsSentToSignIn(): void
    {
        $this->get('admin/pengaturan/fitur')->assertRedirectTo(site_url('masuk'));
    }

    public function testSwitchingOffAModuleIsSavedAndAudited(): void
    {
        $result = $this->actingAs($this->makeUser('superadmin'))->post('admin/pengaturan/fitur', []);

        $result->assertRedirectTo(site_url('admin/pengaturan/fitur'));
        $result->assertSessionHas('message', lang('Pengaturan.featuresSaved'));
        $this->assertSame(['Contoh'], setting('Features.disabled'));
        $this->seeInDatabase('audit_logs', ['event' => 'features_changed', 'subject_type' => 'settings', 'subject_id' => 'Features']);

        $log = db_connect()->table('audit_logs')->where('event', 'features_changed')->get()->getRowArray();
        $this->assertSame(['disabled' => ['Contoh Fitur']], json_decode($log['new_values'], true));
    }

    public function testSwitchingOnAgainClearsTheList(): void
    {
        setting('Features.disabled', ['Contoh']);
        $this->useFixtureModules(['Contoh']);

        $this->actingAs($this->makeUser('superadmin'))->post('admin/pengaturan/fitur', ['aktif' => ['Contoh']]);

        $this->assertSame([], setting('Features.disabled'));
    }

    public function testCoreModulesCannotBeSwitchedOff(): void
    {
        setting('Features.disabled', ['Contoh']);
        $this->useFixtureModules(['Contoh']);

        // Pengaturan is missing from the post, and an unknown name is sent: neither is stored.
        $this->actingAs($this->makeUser('superadmin'))->post('admin/pengaturan/fitur', ['aktif' => ['TidakAda']]);

        $this->assertSame(['Contoh'], setting('Features.disabled'));
        $this->dontSeeInDatabase('audit_logs', ['event' => 'features_changed']);
    }

    public function testRegistryHidesFilesOfDisabledModules(): void
    {
        $registry = new ModuleRegistry(SUPPORTPATH . 'Modules', ['Pengaturan'], ['Contoh', 'Pengaturan']);

        $this->assertSame(['Contoh', 'Pengaturan'], $registry->all());
        $this->assertSame(['Pengaturan'], $registry->enabled());
        $this->assertSame(['Contoh'], $registry->disabled());
        $this->assertNull($registry->file('Contoh', 'Config/Module.php'));
        $this->assertNotNull($registry->file('Pengaturan', 'Config/Module.php'));
        $this->assertSame('Contoh Fitur', $registry->info('Contoh')['label']);
        $this->assertFalse($registry->info('Contoh')['enabled']);
    }

    public function testEveryRealModuleDescribesItself(): void
    {
        $registry = new ModuleRegistry(APPPATH . 'Modules', config('Features')->core, []);

        foreach ($registry->all() as $name) {
            $this->assertFileExists(APPPATH . 'Modules/' . $name . '/Config/Module.php', $name . ' needs Config/Module.php for the Fitur page');
        }
    }
}
