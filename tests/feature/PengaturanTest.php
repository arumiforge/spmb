<?php

declare(strict_types=1);

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * School identity and security settings.
 *
 * @internal
 */
final class PengaturanTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    protected function tearDown(): void
    {
        foreach (['name', 'shortName', 'primaryColor', 'timezoneLabel', 'email'] as $key) {
            setting()->forget('School.' . $key);
        }

        setting()->forget('TwoFactor.requiredRoles');
        parent::tearDown();
    }

    private function makeUser(string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'u' . $group, 'email' => $group . '@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }

    private function validPost(array $overrides = []): array
    {
        return $overrides + [
            'name'          => 'SMP Contoh Nusantara',
            'shortName'     => 'SMP Contoh',
            'tagline'       => 'Penerimaan Murid Baru 2027/2028',
            'primaryColor'  => '#0E7490',
            'timezoneLabel' => 'WIB',
            'email'         => 'info@contoh.sch.id',
        ];
    }

    public function testAdminUpdatesSchoolIdentity(): void
    {
        $result = $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/sekolah', $this->validPost());

        $result->assertRedirectTo(site_url('admin/pengaturan/sekolah'));
        $this->assertSame('SMP Contoh Nusantara', setting('School.name'));
        $this->assertSame('#0e7490', setting('School.primaryColor'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_id' => 'School']);
    }

    public function testBrandColourAppearsInLayout(): void
    {
        setting('School.primaryColor', '#0e7490');

        $body = $this->get('/')->getBody();
        $this->assertStringContainsString('--brand:#0e7490', $body);
    }

    public function testInvalidColourIsRejected(): void
    {
        $result = $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/sekolah', $this->validPost(['primaryColor' => 'red;}body{']));

        $result->assertSessionHas('errors');
        $this->assertNotSame('red;}body{', setting('School.primaryColor'));
    }

    public function testStaffWithoutPermissionIsTurnedAway(): void
    {
        $this->actingAs($this->makeUser('panitia'))->get('admin/pengaturan/sekolah')->assertRedirectTo(site_url('admin/akses-ditolak'));
    }

    public function testOnlySuperAdminSetsTwoFactorPolicy(): void
    {
        $this->actingAs($this->makeUser('admin'))->get('admin/pengaturan/keamanan')->assertRedirectTo(site_url('admin/akses-ditolak'));
    }

    public function testSuperAdminRequiresTwoFactorForRoles(): void
    {
        $super = $this->makeUser('superadmin');
        $this->actingAs($super)->get('admin/pengaturan/keamanan')->assertOK();

        $this->actingAs($super)->post('admin/pengaturan/keamanan', ['roles' => ['admin', 'panitia', 'tidak-ada']])->assertRedirect();

        $this->assertSame(['admin', 'panitia'], setting('TwoFactor.requiredRoles'));
    }
}
