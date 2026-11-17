<?php

declare(strict_types=1);

use App\Controllers\Errors;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Friendly Indonesian 404 and 500 pages.
 *
 * @internal
 */
final class ErrorPagesTest extends CIUnitTestCase
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
        setting()->forget('School.whatsapp');
        parent::tearDown();
    }

    private function makeStaff(): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'panitia1', 'email' => 'panitia1@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup('panitia');

        return $user;
    }

    public function testVisitorSeesFriendlyNotFoundPageInPublicLayout(): void
    {
        setting('School.whatsapp', '0812 3456 7890');

        $result = $this->get('halaman-yang-tidak-ada');

        $result->assertStatus(404);
        $result->assertSee(lang('App.notFoundTitle'));
        $result->assertSee(lang('App.backHome'));
        $result->assertSee(lang('App.staffLogin')); // public footer
        $result->assertSee('https://wa.me/6281234567890');
        $result->assertDontSee('PageNotFoundException');
    }

    public function testSignedInStaffSeesNotFoundPageInAdminLayout(): void
    {
        $result = $this->actingAs($this->makeStaff())->get('admin/halaman-yang-tidak-ada');

        $result->assertStatus(404);
        $result->assertSee(lang('App.notFoundTitle'));
        $result->assertSee(lang('App.backDashboard'));
        $result->assertSee(lang('App.mainMenu')); // admin sidebar
        $result->assertDontSee('wa.me');
    }

    public function testNotFoundThrownByAControllerUsesTheSamePage(): void
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'admin1', 'email' => 'admin1@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $admin = $users->findById($users->getInsertID());
        $admin->addGroup('admin');

        $result = $this->actingAs($admin)->get('admin/pengguna/999999/ubah');

        $result->assertStatus(404);
        $result->assertSee(lang('App.notFoundTitle'));
    }

    public function testServerErrorPageIsFriendlyAndHidesDetails(): void
    {
        service('routes')->loadRoutes(); // a real request has its routes loaded

        ob_start();
        include APPPATH . 'Views/errors/html/production.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString(lang('App.serverErrorTitle'), $html);
        $this->assertStringContainsString(lang('App.reload'), $html);
        $this->assertStringContainsString(lang('App.staffLogin'), $html); // public layout, not the fallback
        $this->assertStringNotContainsString('Exception', $html);
    }

    public function testFallbackPageNeedsNothingButPhp(): void
    {
        $html = Errors::fallback(500);

        $this->assertStringContainsString('Maaf, ada gangguan di sistem', $html);
        $this->assertStringContainsString('lang="id"', $html);
        $this->assertStringContainsString('Halaman tidak ditemukan', Errors::fallback(404));
    }
}
