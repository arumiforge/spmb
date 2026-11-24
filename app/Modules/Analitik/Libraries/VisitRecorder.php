<?php

declare(strict_types=1);

namespace Modules\Analitik\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Throwable;

/**
 * Counts public page views and daily unique visitors without storing anything
 * personal (D-009, D-053).
 *
 * A visitor is recognised for one day only: HMAC-SHA256 of IP address + user agent
 * with a key derived from the app encryption key and the date. The key changes
 * every day and is never stored, so hashes cannot be linked across days, and the
 * hash rows themselves are deleted after a day. Only daily totals and per-path
 * totals are kept.
 *
 * Not counted: anything but a plain GET page load answered with 200 text/html
 * (redirects, errors, downloads, JSON, AJAX, prefetch), bots and crawlers, the
 * staff area and sign-in pages, asset routes, and signed-in staff.
 */
class VisitRecorder
{
    /**
     * Paths (without leading slash) that are never counted; `*` matches the rest.
     */
    public const EXCLUDED_PATHS = [
        'admin', 'admin/*',
        'masuk', 'masuk/*', 'keluar', 'keluar/*', 'verifikasi', 'verifikasi/*',
        'login', 'login/*', 'logout', 'register', 'register/*',
        'aset/*', 'assets/*', 'favicon.ico', 'robots.txt', 'sitemap.xml',
    ];

    /**
     * User agents containing one of these (case-insensitive) are bots, crawlers,
     * link previews, monitors or scripts.
     */
    public const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'scrap', 'fetch', 'preview', 'facebookexternalhit',
        'whatsapp', 'telegram', 'embedly', 'headless', 'lighthouse', 'pagespeed', 'gtmetrix',
        'pingdom', 'uptime', 'monitor', 'statuscake', 'curl', 'wget', 'python', 'java/',
        'go-http', 'okhttp', 'axios', 'node-fetch', 'guzzle', 'httpclient', 'libwww', 'php/',
        'postman', 'insomnia', 'phantomjs', 'selenium', 'puppeteer', 'playwright', 'httrack',
    ];

    public function __construct(private ?BaseConnection $db = null)
    {
    }

    /**
     * Records the visit when it counts. Never throws: a failure is logged and the page is served as usual.
     */
    public function handle(RequestInterface $request, ResponseInterface $response): void
    {
        try {
            if (! $this->shouldCount($request, $response) || $this->isSignedIn($request)) {
                return;
            }

            /** @var IncomingRequest $request */
            $this->record(
                self::normalisePath($request->getPath()),
                $this->visitorHash($request->getIPAddress(), $request->getHeaderLine('User-Agent'), Time::today()->toDateString()),
            );
        } catch (Throwable $e) {
            log_message('error', 'Analitik: visit not recorded: {message}', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Whether this request/response pair is a page view by a person (signed-in staff are checked separately).
     */
    public function shouldCount(RequestInterface $request, ResponseInterface $response): bool
    {
        if (! $request instanceof IncomingRequest || strtoupper($request->getMethod()) !== 'GET') {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $type = strtolower($response->getHeaderLine('Content-Type'));

        if ($type !== '' && ! str_contains($type, 'text/html')) {
            return false;
        }

        if ($request->isAJAX() || $request->hasHeader('HX-Request')) {
            return false;
        }

        // Browsers say what the request is for: only full page loads count, not fetches, frames or prefetches.
        $dest = strtolower($request->getHeaderLine('Sec-Fetch-Dest'));

        if ($dest !== '' && $dest !== 'document') {
            return false;
        }

        $purpose = strtolower($request->getHeaderLine('Sec-Purpose') . ' ' . $request->getHeaderLine('Purpose') . ' ' . $request->getHeaderLine('X-Moz'));

        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        if (self::isExcludedPath($request->getPath())) {
            return false;
        }

        return ! self::isBot($request->getHeaderLine('User-Agent'));
    }

    public static function isBot(string $agent): bool
    {
        $agent = strtolower(trim($agent));

        // Every real browser sends "Mozilla/5.0 ..." (old Opera: "Opera/...").
        if ($agent === '' || (! str_contains($agent, 'mozilla') && ! str_contains($agent, 'opera'))) {
            return true;
        }

        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($agent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function isExcludedPath(string $path): bool
    {
        $path = strtolower(trim($path, '/'));

        foreach (self::EXCLUDED_PATHS as $pattern) {
            if ($pattern === $path || (str_ends_with($pattern, '/*') && str_starts_with($path . '/', substr($pattern, 0, -1)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Path as stored: lower case, no slashes at the ends, no query string, at most
     * four segments and 191 characters. Numbers and long random segments (card and
     * link tokens) become `*`, so no token or id is ever stored.
     */
    public static function normalisePath(string $path): string
    {
        $path     = strtolower(trim((string) parse_url('/' . ltrim($path, '/'), PHP_URL_PATH), '/'));
        $segments = [];

        foreach (array_slice(explode('/', $path), 0, 4) as $segment) {
            if ($segment === '') {
                continue;
            }

            $segments[] = preg_match('/\A\d+\z/', $segment) === 1 || strlen($segment) >= 20 ? '*' : $segment;
        }

        return mb_substr(implode('/', $segments), 0, 191);
    }

    /**
     * One-way daily visitor key: the same person gets the same hash all day and a
     * different one tomorrow. 128 bits of HMAC-SHA256 as hex.
     */
    public function visitorHash(string $ip, string $agent, string $date): string
    {
        $dayKey = hash_hmac('sha256', 'analitik-visitor|' . $date, self::secret(), true);

        return substr(hash_hmac('sha256', $ip . "\n" . $agent, $dayKey), 0, 32);
    }

    /**
     * Adds one page view (and one visitor when the hash is new today). Three small
     * queries; the first view of a day also deletes yesterday's leftovers.
     */
    public function record(string $path, string $hash, ?string $date = null): void
    {
        $db = $this->db ??= db_connect();
        $date ??= Time::today()->toDateString();

        $db->query('INSERT IGNORE INTO ' . $db->prefixTable('analytics_visitors') . ' (`date`, `hash`) VALUES (?, ?)', [$date, $hash]);
        $newVisitor = $db->affectedRows() > 0 ? 1 : 0;

        $db->query(
            'INSERT INTO ' . $db->prefixTable('analytics_daily') . ' (`date`, `page_views`, `visitors`) VALUES (?, 1, ?)'
            . ' ON DUPLICATE KEY UPDATE `page_views` = `page_views` + 1, `visitors` = `visitors` + ?',
            [$date, $newVisitor, $newVisitor],
        );
        $firstOfDay = $db->affectedRows() === 1;

        $db->query(
            'INSERT INTO ' . $db->prefixTable('analytics_daily_pages') . ' (`date`, `path`, `views`) VALUES (?, ?, 1)'
            . ' ON DUPLICATE KEY UPDATE `views` = `views` + 1',
            [$date, $path],
        );

        if ($firstOfDay) {
            $this->prune((int) setting('Analitik.retentionDays'), $date);
        }
    }

    /**
     * Deletes visitor hashes older than yesterday and per-page rows older than
     * $retentionDays (minimum 1). Daily totals are kept.
     *
     * @return array{visitors: int, pages: int} rows deleted
     */
    public function prune(int $retentionDays, ?string $today = null): array
    {
        $db    = $this->db ??= db_connect();
        $today = Time::parse($today ?? Time::today()->toDateString());

        $db->table('analytics_visitors')->where('date <', $today->subDays(1)->toDateString())->delete();
        $visitors = $db->affectedRows();

        $db->table('analytics_daily_pages')->where('date <', $today->subDays(max(1, $retentionDays))->toDateString())->delete();

        return ['visitors' => $visitors, 'pages' => $db->affectedRows()];
    }

    /**
     * Signed-in staff are not counted. Only looks at the session when the browser
     * already has one, so anonymous visitors never get a session from this.
     */
    private function isSignedIn(RequestInterface $request): bool
    {
        /** @var IncomingRequest $request */
        $hasSession = (isset($_SESSION) && $_SESSION !== [])
            || $request->getCookie(config('Session')->cookieName) !== null
            || $request->getCookie((string) (config('Auth')->sessionConfig['rememberCookieName'] ?? 'remember')) !== null;

        return $hasSession && auth('session')->loggedIn();
    }

    private static function secret(): string
    {
        $key = (string) config('Encryption')->key;

        // Without an encryption key (fresh development install) fall back to a per-install value.
        return $key !== '' ? $key : hash('sha256', ROOTPATH . php_uname('n'), true);
    }
}
