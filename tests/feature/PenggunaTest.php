<?php

declare(strict_types=1);

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Pengguna\Controllers\Pengguna;

/**
 * Staff account management (/admin/pengguna).
 *
 * @internal
 */
final class PenggunaTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    private function makeUser(string $username, ?string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => $username, 'email' => $username . '@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => ucfirst($username) . ' Test']));
        $user = $users->findById($users->getInsertID());

        if ($group !== null) {
            $user->addGroup($group);
        }

        $user->activate();

        return $user;
    }

    public function testListNeedsPermission(): void
    {
        $this->actingAs($this->makeUser('panitia1', 'panitia'))->get('admin/pengguna')->assertRedirectTo(site_url('admin/akses-ditolak'));
    }

    public function testAdminSeesListAndCanSearch(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $this->makeUser('siti', 'panitia');
        $this->makeUser('joko', 'petugas-tes');

        $result = $this->actingAs($admin)->get('admin/pengguna?q=siti');

        $result->assertOK();
        $result->assertSee('Siti Test');
        $result->assertDontSee('Joko Test');

        $byRole = $this->actingAs($admin)->get('admin/pengguna?peran=petugas-tes');
        $byRole->assertSee('Joko Test');
        $byRole->assertDontSee('Siti Test');
    }

    public function testAdminCreatesUserWithTemporaryPassword(): void
    {
        $admin  = $this->makeUser('admin1', 'admin');
        $result = $this->actingAs($admin)->post('admin/pengguna/tambah', [
            'full_name' => 'Rina Kartika',
            'username'  => 'rina',
            'email'     => 'rina@example.test',
            'roles'     => ['panitia', 'petugas-tes'],
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('temp_password');

        $user = model(UserModel::class, false)->findByCredentials(['email' => 'rina@example.test']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEqualsCanonicalizing(['panitia', 'petugas-tes'], $user->getGroups());
        $this->assertTrue($user->requiresPasswordReset());
        $this->assertTrue(service('passwords')->verify($_SESSION['temp_password'], $user->password_hash));
    }

    public function testAdminCannotAssignProtectedRoles(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $this->actingAs($admin)->post('admin/pengguna/tambah', [
            'full_name' => 'Coba Super',
            'username'  => 'cobasuper',
            'email'     => 'cobasuper@example.test',
            'roles'     => ['superadmin'],
        ])->assertSessionHas('errors');

        $this->assertNull(model(UserModel::class, false)->findByCredentials(['email' => 'cobasuper@example.test']));
    }

    public function testAdminCannotEditSuperAdmin(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $super = $this->makeUser('super1', 'superadmin');

        $this->actingAs($admin)->get("admin/pengguna/{$super->id}/ubah")->assertRedirectTo(site_url('admin/pengguna'));
        $this->actingAs($admin)->post("admin/pengguna/{$super->id}/nonaktifkan")->assertRedirectTo(site_url('admin/pengguna'));

        $this->assertFalse(model(UserModel::class, false)->findById($super->id)->isBanned());
    }

    public function testSuperAdminCanManageAdmins(): void
    {
        $super = $this->makeUser('super1', 'superadmin');
        $admin = $this->makeUser('admin1', 'admin');

        $result = $this->actingAs($super)->post("admin/pengguna/{$admin->id}/ubah", [
            'full_name' => 'Admin Baru',
            'username'  => 'admin1',
            'email'     => 'admin1@example.test',
            'roles'     => ['admin', 'kepsek'],
        ]);

        $result->assertRedirectTo(site_url("admin/pengguna/{$admin->id}/ubah"));
        $user = model(UserModel::class, false)->findById($admin->id);
        $this->assertSame('Admin Baru', $user->full_name);
        $this->assertEqualsCanonicalizing(['admin', 'kepsek'], $user->getGroups());
    }

    public function testEditKeepsLockedRolesAndChangesEmail(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $staff = $this->makeUser('budi', 'panitia');

        $this->actingAs($admin)->post("admin/pengguna/{$staff->id}/ubah", [
            'full_name' => 'Budi Baru',
            'username'  => 'budi',
            'email'     => 'budi.baru@example.test',
            'roles'     => ['petugas-seragam'],
        ])->assertRedirect();

        $user = model(UserModel::class, false)->findById($staff->id);
        $this->assertSame(['petugas-seragam'], $user->getGroups());
        $this->assertSame('budi.baru@example.test', $user->email);
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $this->makeUser('siti', 'panitia');

        $result = $this->actingAs($admin)->post('admin/pengguna/tambah', [
            'full_name' => 'Siti Lain',
            'username'  => 'sitilain',
            'email'     => 'siti@example.test',
            'roles'     => ['panitia'],
        ]);

        $result->assertSessionHas('errors');
        $this->assertArrayHasKey('email', $_SESSION['errors']);
    }

    public function testDeactivateAndActivate(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $staff = $this->makeUser('budi', 'panitia');

        $this->actingAs($admin)->post("admin/pengguna/{$staff->id}/nonaktifkan")->assertRedirect();
        $this->assertTrue(model(UserModel::class, false)->findById($staff->id)->isBanned());

        $this->post("admin/pengguna/{$staff->id}/aktifkan")->assertRedirect();
        $this->assertFalse(model(UserModel::class, false)->findById($staff->id)->isBanned());
    }

    public function testCannotDeactivateSelf(): void
    {
        $admin = $this->makeUser('admin1', 'admin');

        $this->actingAs($admin)->post("admin/pengguna/{$admin->id}/nonaktifkan")->assertSessionHas('error');
        $this->assertFalse(model(UserModel::class, false)->findById($admin->id)->isBanned());
    }

    public function testResetPasswordForcesChange(): void
    {
        $admin = $this->makeUser('admin1', 'admin');
        $staff = $this->makeUser('budi', 'panitia');

        $this->actingAs($admin)->post("admin/pengguna/{$staff->id}/atur-ulang-kata-sandi")->assertSessionHas('temp_password');

        $user = model(UserModel::class, false)->findById($staff->id);
        $this->assertTrue($user->requiresPasswordReset());
        $this->assertFalse(service('passwords')->verify('Rahasia-Kuat-2027', $user->password_hash));
    }

    public function testTemporaryPasswordFormat(): void
    {
        $this->assertMatchesRegularExpression('/\A[a-zA-Z2-9]{4}-[a-zA-Z2-9]{4}-[a-zA-Z2-9]{4}\z/', Pengguna::temporaryPassword());
    }
}
