<?php

declare(strict_types=1);

use App\Libraries\SetupChecklist;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * "Persiapan sistem" checklist on the dashboard (D-099).
 *
 * @internal
 */
final class PersiapanSistemTest extends CIUnitTestCase
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

    protected function tearDown(): void
    {
        foreach (['name', 'address', 'phone', 'whatsapp'] as $key) {
            setting()->forget('School.' . $key);
        }

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

    private function step(array $steps, string $key): ?array
    {
        foreach ($steps as $step) {
            if ($step['key'] === $key) {
                return $step;
            }
        }

        return null;
    }

    public function testStepsFollowTheSettings(): void
    {
        setting('School.name', 'Nama Sekolah');
        $steps = (new SetupChecklist())->steps();
        $this->assertFalse($this->step($steps, 'school-name')['done']);
        $this->assertFalse($this->step($steps, 'school-address')['done']);
        $this->assertTrue($this->step($steps, 'school-name')['required']);

        setting('School.name', 'SMP Contoh Nusantara');
        setting('School.address', 'Jl. Melati 1, Kudus');
        setting('School.whatsapp', '081234567890');
        $steps = (new SetupChecklist())->steps();
        $this->assertTrue($this->step($steps, 'school-name')['done']);
        $this->assertTrue($this->step($steps, 'school-address')['done']);
        $this->assertTrue($this->step($steps, 'school-contact')['done']);
        $this->assertFalse($this->step($steps, 'reg-waves')['done']);
    }

    public function testProgressCountsRequiredStepsLeft(): void
    {
        $progress = SetupChecklist::progress([
            ['done' => true, 'required' => true],
            ['done' => false, 'required' => true],
            ['done' => false, 'required' => false],
            ['done' => true, 'required' => false],
        ]);

        $this->assertSame(['done' => 2, 'total' => 4, 'requiredLeft' => 1, 'percent' => 50], $progress);
    }

    public function testAdminSeesOnlyStepsTheyCanFix(): void
    {
        $admin = $this->makeUser('admin');
        $steps = (new SetupChecklist())->steps($admin);

        $this->assertNotNull($this->step($steps, 'school-logo'));
        $this->assertNotNull($this->step($steps, 'acc-panitia'));
        $this->assertNull($this->step($steps, 'acc-admin'));   // Super Admin creates Admin Sekolah
        $this->assertNull($this->step($steps, 'sys-upload'));  // Super Admin only

        $result = $this->actingAs($admin)->get('admin');
        $result->assertOK();
        $result->assertSee('Persiapan sistem');
        $this->assertStringContainsString('data-langkah="school-logo"', (string) $result->getBody());
        $this->assertStringContainsString('data-lipatan="persiapan"', (string) $result->getBody());
    }

    public function testPanitiaDoesNotSeeTheChecklist(): void
    {
        $result = $this->actingAs($this->makeUser('panitia'))->get('admin');
        $result->assertOK();
        $result->assertDontSee('Persiapan sistem');
    }

    public function testSuperAdminSeesServerAndAdminSteps(): void
    {
        $body = (string) $this->actingAs($this->makeUser('superadmin'))->get('admin')->getBody();

        $this->assertStringContainsString('data-langkah="sys-upload"', $body);
        $this->assertStringContainsString('data-langkah="acc-admin"', $body);
    }
}
