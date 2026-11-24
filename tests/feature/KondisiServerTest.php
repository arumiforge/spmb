<?php

declare(strict_types=1);

use App\Libraries\ServerCheck;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Server condition page (Super Admin only, D-101).
 *
 * @internal
 */
final class KondisiServerTest extends CIUnitTestCase
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
        Services::resetSingle('auth');
    }

    private function makeUser(string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'u' . $group, 'email' => $group . '@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }

    public function testOnlySuperAdminOpensThePage(): void
    {
        $admin = $this->makeUser('admin');
        $super = $this->makeUser('superadmin');
        $this->actingAs($admin)->get('admin/pengaturan/kondisi-server')->assertRedirectTo(site_url('admin/akses-ditolak'));

        $result = $this->actingAs($super)->get('admin/pengaturan/kondisi-server');
        $result->assertOK();
        $result->assertSee('Kondisi server');
        $result->assertSee('upload_max_filesize');
        $result->assertSee('post_max_size');
        $this->assertStringContainsString('data-lipatan="server-upload"', (string) $result->getBody());
        $result->assertSee(esc(ServerCheck::formatBytes(ServerCheck::maxUploadBytes())));
    }

    public function testSidebarShowsTheEntryOnlyForSuperAdmin(): void
    {
        $super = $this->makeUser('superadmin');
        $admin = $this->makeUser('admin');
        $this->assertStringContainsString('admin/pengaturan/kondisi-server', (string) $this->actingAs($super)->get('admin')->getBody());
        $this->assertStringNotContainsString('admin/pengaturan/kondisi-server', (string) $this->actingAs($admin)->get('admin')->getBody());
    }

    public function testIniValuesAreReadAsBytes(): void
    {
        $this->assertSame(12 * 1024 * 1024, ServerCheck::iniBytes('12M'));
        $this->assertSame(512 * 1024, ServerCheck::iniBytes('512k'));
        $this->assertSame(2 * 1024 ** 3, ServerCheck::iniBytes('2G'));
        $this->assertSame(0, ServerCheck::iniBytes('-1'));
        $this->assertSame('10 MB', ServerCheck::formatBytes(10 * 1024 * 1024));
        $this->assertSame('1,5 KB', ServerCheck::formatBytes(1536));
    }

    public function testOverallTakesTheWorstStatus(): void
    {
        $this->assertSame('ok', ServerCheck::overall(['a' => [['status' => 'ok'], ['status' => 'info']]]));
        $this->assertSame('warn', ServerCheck::overall(['a' => [['status' => 'ok']], 'b' => [['status' => 'warn']]]));
        $this->assertSame('bad', ServerCheck::overall(['a' => [['status' => 'warn']], 'b' => [['status' => 'bad']]]));
    }
}
