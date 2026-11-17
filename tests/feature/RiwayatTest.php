<?php

declare(strict_types=1);

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Modules\Riwayat\Libraries\AuditPresenter;

/**
 * Audit log viewer (Riwayat Perubahan): permissions, filters, readable values.
 *
 * @internal
 */
final class RiwayatTest extends CIUnitTestCase
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

    private function makeUser(string $group, string $name): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'u' . $group, 'email' => $group . '@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => $name]));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function log(array $row): int
    {
        db_connect()->table('audit_logs')->insert($row + ['created_at' => '2027-01-10 09:30:00']);

        return (int) db_connect()->insertID();
    }

    public function testAdminSekolahSeesTheLog(): void
    {
        $admin = $this->makeUser('admin', 'Siti Aminah');
        $this->log(['user_id' => $admin->id, 'event' => 'settings_updated', 'subject_type' => 'settings', 'subject_id' => 'School', 'description' => 'Identitas sekolah diubah']);

        $result = $this->actingAs($admin)->get('admin/riwayat');

        $result->assertOK();
        $result->assertSee('Riwayat Perubahan');
        $result->assertSee('Identitas sekolah diubah');
        $result->assertSee('Pengaturan · Identitas Sekolah');
        $result->assertSee('Pengaturan diubah');
        $result->assertSee('10 Januari 2027, 09.30');
    }

    public function testRolesWithoutPermissionAreTurnedAway(): void
    {
        foreach (['kepsek', 'panitia', 'petugas_tes'] as $group) {
            if (! isset(config('AuthGroups')->groups[$group])) {
                continue;
            }

            $user = $this->makeUser($group, 'Petugas ' . $group);
            $this->actingAs($user)->get('admin/riwayat')->assertRedirectTo(site_url('admin/akses-ditolak'));
            $this->actingAs($user)->get('admin/riwayat/1')->assertRedirectTo(site_url('admin/akses-ditolak'));
        }
    }

    public function testGuestIsSentToSignIn(): void
    {
        $this->get('admin/riwayat')->assertRedirectTo(site_url('masuk'));
    }

    public function testFiltersNarrowTheList(): void
    {
        $admin = $this->makeUser('admin', 'Siti Aminah');
        $super = $this->makeUser('superadmin', 'Budi Santoso');
        $this->log(['user_id' => $admin->id, 'event' => 'password_reset', 'subject_type' => 'users', 'subject_id' => (string) $super->id, 'description' => 'Catatan-A', 'created_at' => '2027-01-05 08:00:00']);
        $this->log(['user_id' => $super->id, 'event' => 'settings_updated', 'subject_type' => 'settings', 'subject_id' => 'TwoFactor', 'description' => 'Catatan-B', 'created_at' => '2027-01-20 08:00:00']);
        $this->log(['user_id' => null, 'event' => 'updated', 'subject_type' => 'users', 'subject_id' => (string) $admin->id, 'description' => 'Catatan-C', 'created_at' => '2027-02-01 08:00:00']);

        $this->actingAs($admin);
        $see = function (string $query, array $visible): void {
            $body = $this->get('admin/riwayat?' . $query)->getBody();

            foreach (['Catatan-A', 'Catatan-B', 'Catatan-C'] as $text) {
                in_array($text, $visible, true)
                    ? $this->assertStringContainsString($text, $body, $query)
                    : $this->assertStringNotContainsString($text, $body, $query);
            }
        };

        $see('pengguna=' . $admin->id, ['Catatan-A']);
        $see('pengguna=sistem', ['Catatan-C']);
        $see('kegiatan=settings_updated', ['Catatan-B']);
        $see('data=users', ['Catatan-A', 'Catatan-C']);
        $see('data=users&id=' . $admin->id, ['Catatan-C']);
        $see('dari=2027-01-10&sampai=2027-01-31', ['Catatan-B']);
        $see('dari=2027-01-31&sampai=2027-01-10', ['Catatan-B']); // reversed range is swapped
        $see('sampai=2027-01-05', ['Catatan-A']);                  // end date includes the whole day
        $see('dari=31-01-2027', ['Catatan-A', 'Catatan-B', 'Catatan-C']); // bad date is ignored
    }

    public function testInvalidDateShowsAHint(): void
    {
        $this->actingAs($this->makeUser('admin', 'Siti Aminah'))
            ->get('admin/riwayat?dari=2027-02-30')
            ->assertSee(lang('Riwayat.dateInvalid'));
    }

    public function testEmptyStatesDifferWithAndWithoutFilters(): void
    {
        $admin = $this->makeUser('admin', 'Siti Aminah');
        db_connect()->table('audit_logs')->truncate();

        $empty = $this->actingAs($admin)->get('admin/riwayat');
        $empty->assertSee(lang('Riwayat.emptyTitle'));
        $empty->assertSee('state/kosong-riwayat.webp');

        $filtered = $this->actingAs($admin)->get('admin/riwayat?kegiatan=deleted');
        $filtered->assertSee(lang('Riwayat.emptyFilteredTitle'));
        $filtered->assertSee('state/kosong-pencarian.webp');
    }

    public function testListIsPaginated(): void
    {
        $admin = $this->makeUser('admin', 'Siti Aminah');
        db_connect()->table('audit_logs')->truncate();

        for ($i = 1; $i <= 30; $i++) {
            $this->log(['user_id' => $admin->id, 'event' => 'updated', 'description' => sprintf('Baris-%02d', $i)]);
        }

        $first = $this->actingAs($admin)->get('admin/riwayat')->getBody();
        $this->assertStringContainsString('Baris-30', $first);
        $this->assertStringNotContainsString('Baris-05', $first);
        $this->assertStringContainsString('30 catatan', $first);

        // The shared pager remembers the page of the first request; real requests start fresh.
        Services::resetSingle('pager');
        $second = $this->actingAs($admin)->get('admin/riwayat?page=2')->getBody();
        $this->assertStringContainsString('Baris-05', $second);
        $this->assertStringNotContainsString('Baris-30', $second);
    }

    public function testDetailShowsReadableOldAndNewValues(): void
    {
        $admin = $this->makeUser('admin', 'Siti Aminah');
        $id    = $this->log([
            'user_id'      => $admin->id,
            'event'        => 'user_roles_changed',
            'subject_type' => 'users',
            'subject_id'   => (string) $admin->id,
            'old_values'   => json_encode(['roles' => ['panitia'], 'password' => '[disembunyikan]']),
            'new_values'   => json_encode(['roles' => ['admin', 'panitia'], 'password' => '[disembunyikan]']),
            'ip_address'   => '10.0.0.7',
        ]);

        $result = $this->actingAs($admin)->get('admin/riwayat/' . $id);

        $result->assertOK();
        $result->assertSee('Detail Perubahan');
        $result->assertSee('Peran');
        $result->assertSee('Panitia Pendaftaran');
        $result->assertSee('Admin Sekolah, Panitia Pendaftaran');
        $result->assertSee('(disembunyikan)');
        $result->assertSee('10.0.0.7');
        $result->assertSee('Pengguna · Siti Aminah');
        $result->assertDontSee('[disembunyikan]');
    }

    public function testMissingEntryIsNotFound(): void
    {
        $result = $this->actingAs($this->makeUser('admin', 'Siti Aminah'))->get('admin/riwayat/999999');

        $result->assertStatus(404);
        $result->assertSee(lang('App.notFoundTitle'));
    }

    public function testPresenterFallsBackForUnknownKeys(): void
    {
        $presenter = new AuditPresenter(['admin' => 'Admin Sekolah']);

        $this->assertSame('Something new', $presenter->eventLabel('something_new'));
        $this->assertSame('Akun dinonaktifkan', $presenter->eventLabel('user_deactivated'));
        $this->assertSame('Nilai baru', $presenter->fieldLabel('nilai_baru'));
        $this->assertSame('(kosong)', $presenter->format('name', ''));
        $this->assertSame('Ya', $presenter->format('active', true));
        $this->assertSame('Tidak', $presenter->format('active', '0'));
        $this->assertSame('Data baru berisi Email', $presenter->summary(['event' => 'created', 'new_values' => json_encode(['email' => 'a@b.c'])]));
        $this->assertSame('Admin Sekolah', $presenter->format('roles', ['admin']));
        $this->assertSame('{"a":1}', $presenter->format('extra', ['a' => 1]));
        $this->assertSame('Mengubah Nama sekolah, Email, Website dan 1 lainnya', $presenter->summary([
            'event'      => 'updated',
            'new_values' => json_encode(['name' => 'x', 'email' => 'y', 'website' => 'z', 'phone' => 'w']),
        ]));
    }
}
