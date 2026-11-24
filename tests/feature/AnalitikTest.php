<?php

declare(strict_types=1);

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Analitik\Libraries\VisitStats;

/**
 * Analitik: counting through the global filter, the Pengunjung page and its
 * settings, the dashboard card and the public footer line.
 *
 * @internal
 */
final class AnalitikTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $this->resetServices();
        cache()->delete(VisitStats::FOOTER_CACHE_KEY);
    }

    private function makeUser(string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'u' . $group, 'email' => $group . '@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => 'Petugas ' . $group]));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }

    private function visit(string $path, string $agent = self::BROWSER): void
    {
        $this->withHeaders(['User-Agent' => $agent])->get($path)->assertOK();
    }

    /**
     * @return array{page_views: int, visitors: int}
     */
    private function today(): array
    {
        $row = db_connect()->table('analytics_daily')->where('date', date('Y-m-d'))->get()->getRowArray();

        return ['page_views' => (int) ($row['page_views'] ?? 0), 'visitors' => (int) ($row['visitors'] ?? 0)];
    }

    public function testPublicPagesAreCounted(): void
    {
        $this->visit('/');
        $this->visit('panduan/calon-murid');
        $this->visit('/', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');

        $this->assertSame(['page_views' => 3, 'visitors' => 2], $this->today(), 'the same browser counts once per day');
        $this->seeInDatabase('analytics_daily_pages', ['date' => date('Y-m-d'), 'path' => '', 'views' => 2]);
        $this->seeInDatabase('analytics_daily_pages', ['date' => date('Y-m-d'), 'path' => 'panduan/calon-murid', 'views' => 1]);
    }

    public function testBotsSignInAndStaffAreNotCounted(): void
    {
        $this->visit('/', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)');
        $this->visit('/', '');
        $this->visit('masuk');
        $this->assertSame(['page_views' => 0, 'visitors' => 0], $this->today(), 'bots and sign-in');

        $admin = $this->makeUser('admin');
        // A signed-in browser always carries its session; withSession() stands in for the cookie.
        $this->actingAs($admin)->withSession()->withHeaders(['User-Agent' => self::BROWSER])->get('/')->assertOK();
        $this->withHeaders(['User-Agent' => self::BROWSER])->get('admin/pengunjung')->assertOK();

        $this->assertSame(['page_views' => 0, 'visitors' => 0], $this->today());
        $this->seeNumRecords(0, 'analytics_visitors', []);
    }

    public function testNothingIsCountedWhileTheModuleIsOff(): void
    {
        setting('Features.disabled', ['Analitik']);
        $this->resetServices();

        $this->visit('/');

        $this->assertSame(['page_views' => 0, 'visitors' => 0], $this->today());
    }

    public function testPageAccessByPermission(): void
    {
        $this->get('admin/pengunjung')->assertRedirectTo(site_url('masuk'));

        $panitia = $this->makeUser('panitia');
        $this->actingAs($panitia)->get('admin/pengunjung')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($panitia)->post('admin/pengunjung/pengaturan', ['showInFooter' => '0', 'retentionDays' => '90'])->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->assertTrue(setting('Analitik.showInFooter'));

        db_connect()->table('analytics_daily')->insert(['date' => date('Y-m-d'), 'page_views' => 7, 'visitors' => 3]);
        db_connect()->table('analytics_daily_pages')->insert(['date' => date('Y-m-d'), 'path' => 'tanya-jawab', 'views' => 7]);

        $page = $this->actingAs($this->makeUser('admin'))->get('admin/pengunjung?rentang=7');
        $page->assertOK();
        $page->assertSee('Pengunjung website');
        $page->assertSee('Halaman paling sering dibuka');
        $page->assertSee('Tanya jawab');
        $page->assertSee('tanpa menyimpan data pribadi');
        $page->assertSee('Pengaturan statistik');
        $this->assertStringContainsString('aria-current="page"', (string) $page->getBody());
        $this->assertStringContainsString('data-chart=', (string) $page->getBody());
    }

    public function testEmptyPage(): void
    {
        $page = $this->actingAs($this->makeUser('admin'))->get('admin/pengunjung');

        $page->assertOK();
        $page->assertSee('Belum ada pengunjung');
        $this->assertStringNotContainsString('data-chart=', (string) $page->getBody());
    }

    public function testSettingsAreSavedAndAudited(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post('admin/pengunjung/pengaturan', ['showInFooter' => '1', 'retentionDays' => '5'])
            ->assertRedirect();
        $this->assertSame(400, (int) setting('Analitik.retentionDays'), 'below the minimum');

        $this->actingAs($admin)->post('admin/pengunjung/pengaturan', ['showInFooter' => '0', 'retentionDays' => '120'])
            ->assertRedirectTo(site_url('admin/pengunjung'));

        $this->assertFalse(setting('Analitik.showInFooter'));
        $this->assertSame(120, setting('Analitik.retentionDays'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_type' => 'settings', 'subject_id' => 'Analitik']);
    }

    public function testDashboardCardFollowsPermission(): void
    {
        $this->actingAs($this->makeUser('admin'))->get('admin')->assertSee('Lihat statistik');

        $_SESSION = [];
        $this->resetServices();
        $this->actingAs($this->makeUser('panitia'))->get('admin')->assertDontSee('Lihat statistik');
    }

    public function testFooterLineFollowsTheSetting(): void
    {
        db_connect()->table('analytics_daily')->insert(['date' => date('Y-m-d'), 'page_views' => 2500, 'visitors' => 1234]);

        $html = view('Modules\Analitik\Views\panels\public_footer', [], ['saveData' => false]);
        $this->assertStringContainsString('Pengunjung hari ini', $html);
        $this->assertStringContainsString('1.234', $html);

        setting('Analitik.showInFooter', false);
        $this->assertStringNotContainsString('Pengunjung hari ini', view('Modules\Analitik\Views\panels\public_footer', [], ['saveData' => false]));
        $this->assertStringNotContainsString('Pengunjung hari ini', module_panels('public_footer'));
    }
}
