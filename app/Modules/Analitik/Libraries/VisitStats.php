<?php

declare(strict_types=1);

namespace Modules\Analitik\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

/**
 * Read side of the visitor statistics: totals, the daily series, the most
 * opened pages and the cached footer numbers.
 *
 * "Pengunjung" over several days is the sum of the daily unique visitors: a
 * person who comes back on another day is counted again (nothing links days).
 */
class VisitStats
{
    public const FOOTER_CACHE_KEY = 'analitik_footer';
    public const FOOTER_CACHE_TTL = 300;

    private readonly string $today;

    public function __construct(private ?BaseConnection $db = null, ?string $today = null)
    {
        $this->today = $today ?? Time::today()->toDateString();
    }

    /**
     * @return array<string, array{visitors: int, views: int}> keys today, yesterday, week, month, total
     */
    public function totals(): array
    {
        $today = Time::parse($this->today);
        $sums  = [
            'today'     => [$this->today, $this->today],
            'yesterday' => [$today->subDays(1)->toDateString(), $today->subDays(1)->toDateString()],
            'week'      => [$today->subDays(6)->toDateString(), $this->today],
            'month'     => [$today->subDays(29)->toDateString(), $this->today],
        ];
        $select = [];
        $binds  = [];

        foreach ($sums as $key => [$from, $to]) {
            $select[] = "COALESCE(SUM(CASE WHEN `date` BETWEEN ? AND ? THEN `visitors` END), 0) AS {$key}_visitors";
            $select[] = "COALESCE(SUM(CASE WHEN `date` BETWEEN ? AND ? THEN `page_views` END), 0) AS {$key}_views";
            array_push($binds, $from, $to, $from, $to);
        }

        $select[] = 'COALESCE(SUM(`visitors`), 0) AS total_visitors';
        $select[] = 'COALESCE(SUM(`page_views`), 0) AS total_views';

        $db  = $this->db();
        $row = $db->query('SELECT ' . implode(', ', $select) . ' FROM ' . $db->prefixTable('analytics_daily'), $binds)->getRowArray() ?? [];

        $out = [];

        foreach ([...array_keys($sums), 'total'] as $key) {
            $out[$key] = ['visitors' => (int) ($row[$key . '_visitors'] ?? 0), 'views' => (int) ($row[$key . '_views'] ?? 0)];
        }

        return $out;
    }

    /**
     * One row per day for the last $days days up to today (missing days are 0), oldest first.
     *
     * @return list<array{date: string, visitors: int, views: int}>
     */
    public function daily(int $days): array
    {
        $days  = max(1, $days);
        $today = Time::parse($this->today);
        $from  = $today->subDays($days - 1)->toDateString();
        $rows  = $this->db()->table('analytics_daily')
            ->where('date >=', $from)->where('date <=', $this->today)
            ->get()->getResultArray();
        $byDate = array_column($rows, null, 'date');
        $out    = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date  = $today->subDays($i)->toDateString();
            $out[] = [
                'date'     => $date,
                'visitors' => (int) ($byDate[$date]['visitors'] ?? 0),
                'views'    => (int) ($byDate[$date]['page_views'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Most opened pages in the last $days days.
     *
     * @return list<array{path: string, views: int, name: string|null}>
     */
    public function topPages(int $days = 30, int $limit = 10): array
    {
        $from = Time::parse($this->today)->subDays(max(1, $days) - 1)->toDateString();
        $rows = $this->db()->table('analytics_daily_pages')
            ->select('path, SUM(views) AS views')
            ->where('date >=', $from)->where('date <=', $this->today)
            ->groupBy('path')
            ->orderBy('views', 'DESC')->orderBy('path', 'ASC')
            ->limit($limit)
            ->get()->getResultArray();

        return array_map(static fn (array $row): array => [
            'path'  => (string) $row['path'],
            'views' => (int) $row['views'],
            'name'  => self::pageName((string) $row['path']),
        ], $rows);
    }

    /**
     * Friendly name of a stored path (Language Analitik.pages), or null when unknown.
     */
    public static function pageName(string $path): ?string
    {
        $names = (array) lang('Analitik.pages');

        if (isset($names[$path])) {
            return (string) $names[$path];
        }

        // A known parent with a hidden token or id, e.g. "kartu/*".
        $parent = preg_replace('#/[^/]+\z#', '/*', $path);

        return $parent !== $path && isset($names[$parent]) ? (string) $names[$parent] : null;
    }

    /**
     * Visitors today, this calendar month and in total, cached for five minutes
     * so the public footer adds no query to most page loads.
     *
     * @return array{today: int, month: int, total: int}
     */
    public function footer(): array
    {
        $cached = cache(self::FOOTER_CACHE_KEY);

        if (is_array($cached) && isset($cached['today'], $cached['month'], $cached['total'])) {
            return $cached;
        }

        $db  = $this->db();
        $row = $db->query(
            'SELECT COALESCE(SUM(CASE WHEN `date` = ? THEN `visitors` END), 0) AS today,'
            . ' COALESCE(SUM(CASE WHEN `date` >= ? THEN `visitors` END), 0) AS month,'
            . ' COALESCE(SUM(`visitors`), 0) AS total FROM ' . $db->prefixTable('analytics_daily'),
            [$this->today, substr($this->today, 0, 8) . '01'],
        )->getRowArray() ?? [];

        $numbers = ['today' => (int) ($row['today'] ?? 0), 'month' => (int) ($row['month'] ?? 0), 'total' => (int) ($row['total'] ?? 0)];
        cache()->save(self::FOOTER_CACHE_KEY, $numbers, self::FOOTER_CACHE_TTL);

        return $numbers;
    }

    private function db(): BaseConnection
    {
        return $this->db ??= db_connect();
    }
}
