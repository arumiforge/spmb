<?php

declare(strict_types=1);

use App\Commands\AnalitikPrune;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\Commands;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\TestLogger;
use Config\Logger as LoggerConfig;
use Config\Services;
use Modules\Analitik\Libraries\VisitRecorder;
use Modules\Analitik\Libraries\VisitStats;

/**
 * Analitik: recording rules, the daily visitor hash, pruning and the read side.
 *
 * @internal
 */
final class AnalitikRecorderTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    private const BROWSER = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->delete(VisitStats::FOOTER_CACHE_KEY);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        service('superglobals')->unsetServer('HTTP_USER_AGENT');
        parent::tearDown();
    }

    /**
     * @param array<string, string> $headers
     */
    private function request(string $path, string $method = 'GET', array $headers = []): IncomingRequest
    {
        $headers += ['User-Agent' => self::BROWSER];
        // UserAgent reads the server variable when it is created.
        service('superglobals')->setServer('HTTP_USER_AGENT', $headers['User-Agent']);

        $request = (new IncomingRequest(config('App'), new SiteURI(config('App'), $path), null, new UserAgent()))->withMethod($method);

        foreach ($headers as $name => $value) {
            $request->setHeader($name, $value);
        }

        return $request;
    }

    private function response(int $status = 200, string $type = 'text/html; charset=UTF-8'): Response
    {
        $response = new Response(config('App'));
        $response->setStatusCode($status)->setHeader('Content-Type', $type);

        return $response;
    }

    public function testOnlyPlainHtmlPageLoadsCount(): void
    {
        $recorder = new VisitRecorder();

        $this->assertTrue($recorder->shouldCount($this->request('persyaratan'), $this->response()));
        $this->assertTrue($recorder->shouldCount($this->request(''), $this->response()));
        $this->assertTrue($recorder->shouldCount($this->request('daftar', 'GET', ['Sec-Fetch-Dest' => 'document']), $this->response()));

        $this->assertFalse($recorder->shouldCount($this->request('daftar', 'POST'), $this->response()), 'form posts');
        $this->assertFalse($recorder->shouldCount($this->request('daftar', 'HEAD'), $this->response()), 'HEAD');
        $this->assertFalse($recorder->shouldCount($this->request('daftar'), $this->response(302)), 'redirects');
        $this->assertFalse($recorder->shouldCount($this->request('daftar'), $this->response(404)), 'errors');
        $this->assertFalse($recorder->shouldCount($this->request('cek-status/kartu'), $this->response(200, 'application/pdf')), 'downloads');
        $this->assertFalse($recorder->shouldCount($this->request('cek-status'), $this->response(200, 'application/json')), 'JSON');
        $this->assertFalse($recorder->shouldCount($this->request('daftar', 'GET', ['X-Requested-With' => 'XMLHttpRequest']), $this->response()), 'AJAX');
        $this->assertFalse($recorder->shouldCount($this->request('daftar', 'GET', ['Sec-Fetch-Dest' => 'empty']), $this->response()), 'fetch()');
        $this->assertFalse($recorder->shouldCount($this->request('daftar', 'GET', ['Sec-Purpose' => 'prefetch']), $this->response()), 'prefetch');
        $this->assertFalse($recorder->shouldCount($this->request('admin/pendaftar'), $this->response()), 'staff area');
        $this->assertFalse($recorder->shouldCount($this->request('masuk'), $this->response()), 'sign-in');
        $this->assertFalse($recorder->shouldCount($this->request('aset/logo'), $this->response()), 'assets');
        $this->assertFalse($recorder->shouldCount($this->request('daftar', 'GET', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']), $this->response()), 'bots');
    }

    public function testBotsAndPaths(): void
    {
        foreach (['', 'curl/8.5.0', 'python-requests/2.31', 'facebookexternalhit/1.1', 'Mozilla/5.0 (X11; Linux x86_64) HeadlessChrome/120.0', 'WhatsApp/2.23.20.0'] as $agent) {
            $this->assertTrue(VisitRecorder::isBot($agent), $agent);
        }

        $this->assertFalse(VisitRecorder::isBot(self::BROWSER));
        $this->assertFalse(VisitRecorder::isBot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'));

        $this->assertTrue(VisitRecorder::isExcludedPath('admin'));
        $this->assertTrue(VisitRecorder::isExcludedPath('/admin/pengunjung/'));
        $this->assertTrue(VisitRecorder::isExcludedPath('verifikasi/kirim'));
        $this->assertFalse(VisitRecorder::isExcludedPath('administrasi'));
        $this->assertFalse(VisitRecorder::isExcludedPath('panduan'));

        $this->assertSame('', VisitRecorder::normalisePath('/'));
        $this->assertSame('cek-status', VisitRecorder::normalisePath('/Cek-Status/?nomor=123'));
        $this->assertSame('kartu/*', VisitRecorder::normalisePath('kartu/' . str_repeat('aB3_-', 9)));
        $this->assertSame('cek-status/berkas/*', VisitRecorder::normalisePath('cek-status/berkas/42'));
        $this->assertSame('a/b/c/d', VisitRecorder::normalisePath('a/b/c/d/e/f'));
        $this->assertLessThanOrEqual(191, strlen(VisitRecorder::normalisePath(str_repeat('abcdefghijklmnopqrs/', 30))));
    }

    public function testVisitorHashChangesEveryDayAndHidesTheInput(): void
    {
        $recorder = new VisitRecorder();
        $a        = $recorder->visitorHash('203.0.113.7', self::BROWSER, '2027-01-10');

        $this->assertSame(32, strlen($a));
        $this->assertSame($a, $recorder->visitorHash('203.0.113.7', self::BROWSER, '2027-01-10'));
        $this->assertNotSame($a, $recorder->visitorHash('203.0.113.7', self::BROWSER, '2027-01-11'));
        $this->assertNotSame($a, $recorder->visitorHash('203.0.113.8', self::BROWSER, '2027-01-10'));
        $this->assertStringNotContainsString('203', $a);
    }

    public function testRecordCountsViewsAndEachVisitorOncePerDay(): void
    {
        $recorder = new VisitRecorder();
        $recorder->record('', 'aaa', '2027-01-10');
        $recorder->record('daftar', 'aaa', '2027-01-10');
        $recorder->record('daftar', 'bbb', '2027-01-10');
        $recorder->record('', 'aaa', '2027-01-11');

        $this->seeInDatabase('analytics_daily', ['date' => '2027-01-10', 'page_views' => 3, 'visitors' => 2]);
        $this->seeInDatabase('analytics_daily', ['date' => '2027-01-11', 'page_views' => 1, 'visitors' => 1]);
        $this->seeInDatabase('analytics_daily_pages', ['date' => '2027-01-10', 'path' => 'daftar', 'views' => 2]);
        $this->seeInDatabase('analytics_daily_pages', ['date' => '2027-01-10', 'path' => '', 'views' => 1]);
    }

    public function testHandleStoresNothingPersonal(): void
    {
        Time::setTestNow('2027-01-10 09:00:00');
        $request = $this->request('persyaratan');

        (new VisitRecorder())->handle($request, $this->response());

        $this->seeInDatabase('analytics_daily', ['date' => '2027-01-10', 'page_views' => 1, 'visitors' => 1]);
        $this->seeInDatabase('analytics_daily_pages', ['date' => '2027-01-10', 'path' => 'persyaratan', 'views' => 1]);

        $rows = db_connect()->table('analytics_visitors')->get()->getResultArray();
        $this->assertCount(1, $rows);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $rows[0]['hash']);
        $this->assertStringNotContainsString($request->getIPAddress(), json_encode($rows, JSON_THROW_ON_ERROR));
    }

    public function testHandleNeverBreaksThePage(): void
    {
        Services::injectMock('logger', new TestLogger(new LoggerConfig()));
        db_connect()->query('DROP TABLE ' . db_connect()->prefixTable('analytics_visitors'));

        (new VisitRecorder())->handle($this->request('persyaratan'), $this->response());

        $this->assertLogContains('error', 'Analitik: visit not recorded');
    }

    public function testPruneKeepsTotalsAndRecentRows(): void
    {
        $db = db_connect();
        $db->table('analytics_daily')->insertBatch([
            ['date' => '2026-01-01', 'page_views' => 5, 'visitors' => 2],
            ['date' => '2027-01-10', 'page_views' => 5, 'visitors' => 2],
        ]);
        $db->table('analytics_daily_pages')->insertBatch([
            ['date' => '2026-01-01', 'path' => '', 'views' => 5],
            ['date' => '2027-01-01', 'path' => '', 'views' => 5],
            ['date' => '2027-01-10', 'path' => '', 'views' => 5],
        ]);
        $db->table('analytics_visitors')->insertBatch([
            ['date' => '2027-01-08', 'hash' => str_repeat('a', 32)],
            ['date' => '2027-01-09', 'hash' => str_repeat('b', 32)],
            ['date' => '2027-01-10', 'hash' => str_repeat('c', 32)],
        ]);

        $deleted = (new VisitRecorder())->prune(30, '2027-01-10');

        $this->assertSame(['visitors' => 1, 'pages' => 1], $deleted);
        $this->seeNumRecords(2, 'analytics_daily', []);
        $this->dontSeeInDatabase('analytics_daily_pages', ['date' => '2026-01-01']);
        $this->seeInDatabase('analytics_daily_pages', ['date' => '2027-01-01']);
        $this->dontSeeInDatabase('analytics_visitors', ['date' => '2027-01-08']);
        $this->seeInDatabase('analytics_visitors', ['date' => '2027-01-09']);
    }

    public function testFirstViewOfTheDayPrunes(): void
    {
        db_connect()->table('analytics_visitors')->insert(['date' => '2027-01-01', 'hash' => str_repeat('a', 32)]);

        (new VisitRecorder())->record('', 'bbb', '2027-01-10');

        $this->dontSeeInDatabase('analytics_visitors', ['date' => '2027-01-01']);
    }

    public function testPruneCommand(): void
    {
        $db = db_connect();
        $db->table('analytics_daily_pages')->insertBatch([
            ['date' => Time::today()->subDays(100)->toDateString(), 'path' => '', 'views' => 5],
            ['date' => Time::today()->subDays(10)->toDateString(), 'path' => '', 'views' => 5],
        ]);

        // Run the command directly: command() would start a second command discovery in the
        // suite, which includes module commands twice (see App\Commands\AnalitikPrune).
        $commands = (new ReflectionClass(Commands::class))->newInstanceWithoutConstructor();
        CLI::init();
        ob_start();
        $exit = (new AnalitikPrune(service('logger'), $commands))->run(['hari' => '30']);
        ob_end_clean();

        $this->assertSame(EXIT_SUCCESS, $exit);

        $this->seeNumRecords(1, 'analytics_daily_pages', []);
        $this->dontSeeInDatabase('analytics_daily_pages', ['date' => Time::today()->subDays(100)->toDateString()]);
    }

    public function testStats(): void
    {
        $recorder = new VisitRecorder();

        foreach ([['2027-01-10', 'a'], ['2027-01-10', 'b'], ['2027-01-09', 'a'], ['2027-01-04', 'a'], ['2026-12-01', 'a']] as [$date, $hash]) {
            $recorder->record($date === '2027-01-10' && $hash === 'b' ? 'kartu/*' : 'daftar', $hash, $date);
        }

        $stats  = new VisitStats(null, '2027-01-10');
        $totals = $stats->totals();

        $this->assertSame(['visitors' => 2, 'views' => 2], $totals['today']);
        $this->assertSame(['visitors' => 1, 'views' => 1], $totals['yesterday']);
        $this->assertSame(['visitors' => 4, 'views' => 4], $totals['week']);
        $this->assertSame(['visitors' => 4, 'views' => 4], $totals['month']);
        $this->assertSame(['visitors' => 5, 'views' => 5], $totals['total']);

        $daily = $stats->daily(7);
        $this->assertCount(7, $daily);
        $this->assertSame(['date' => '2027-01-04', 'visitors' => 1, 'views' => 1], $daily[0]);
        $this->assertSame(['date' => '2027-01-05', 'visitors' => 0, 'views' => 0], $daily[1]);
        $this->assertSame('2027-01-10', $daily[6]['date']);

        $pages = $stats->topPages(30);
        $this->assertSame(['path' => 'daftar', 'views' => 3, 'name' => 'Daftar'], $pages[0]);
        $this->assertSame('Periksa kartu pendaftaran', $pages[1]['name']);
        $this->assertNull(VisitStats::pageName('halaman-baru'));

        $this->assertSame(['today' => 2, 'month' => 4, 'total' => 5], $stats->footer());
        $recorder->record('daftar', 'c', '2027-01-10');
        $this->assertSame(['today' => 2, 'month' => 4, 'total' => 5], $stats->footer(), 'cached for five minutes');
    }
}
