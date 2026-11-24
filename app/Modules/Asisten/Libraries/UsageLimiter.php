<?php

declare(strict_types=1);

namespace Modules\Asisten\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\I18n\Time;

/**
 * Limits of the assistant (D-110). Only AI answers count towards the daily limits; quick choices and
 * Tanya Jawab answers are free.
 *
 * - Per visitor per day (setting Asisten.dailyVisitorLimit, default 10): counted under an anonymous
 *   daily key (HMAC-SHA256 of IP address + user agent with a key derived from the app encryption key
 *   and the date, as Analitik's VisitRecorder) and in the visitor's session; the higher count applies,
 *   so neither clearing cookies nor a changing mobile IP address resets it.
 * - Whole website per day (Asisten.dailySiteLimit, default 300): row "_site" in asisten_usage.
 * - Per minute (Asisten.perMinuteLimit, every question, against abuse): CodeIgniter's Throttler
 *   (cache) under the daily key.
 *
 * Usage rows older than yesterday are deleted on the first AI answer of a day (and by asisten:prune).
 */
final class UsageLimiter
{
    public const SITE        = '_site';
    public const SESSION_KEY = 'asisten_usage';

    private string $visitor;

    public function __construct(private readonly IncomingRequest $request, private ?BaseConnection $db = null)
    {
        $this->visitor = self::visitorKey($request->getIPAddress(), $request->getHeaderLine('User-Agent'), Time::today()->toDateString());
    }

    /**
     * One-way key for today: the same browser gets the same key all day and another one tomorrow.
     */
    public static function visitorKey(string $ip, string $agent, string $date): string
    {
        $key    = (string) config('Encryption')->key;
        $secret = $key !== '' ? $key : hash('sha256', ROOTPATH . php_uname('n'), true);
        $dayKey = hash_hmac('sha256', 'asisten-visitor|' . $date, $secret, true);

        return substr(hash_hmac('sha256', $ip . "\n" . $agent, $dayKey), 0, 32);
    }

    /**
     * False when the visitor asked more than the per-minute limit within the last minute.
     */
    public function allowMinute(): bool
    {
        $limit = max(1, (int) setting('Asisten.perMinuteLimit'));

        return service('throttler')->check('asisten-' . $this->visitor, $limit, MINUTE) !== false;
    }

    /**
     * Which daily limit is reached: 'visitor', 'site' or null.
     */
    public function reached(): ?string
    {
        $today = Time::today()->toDateString();

        if ($this->visitorCount($today) >= max(0, (int) setting('Asisten.dailyVisitorLimit'))) {
            return 'visitor';
        }

        if ($this->count(self::SITE, $today) >= max(0, (int) setting('Asisten.dailySiteLimit'))) {
            return 'site';
        }

        return null;
    }

    /**
     * Counts one AI answer for this visitor and for the website.
     */
    public function record(): void
    {
        $db    = $this->db ??= db_connect();
        $today = Time::today()->toDateString();
        $table = $db->prefixTable('asisten_usage');

        foreach ([$this->visitor, self::SITE] as $visitor) {
            $db->query('INSERT INTO ' . $table . ' (`date`, `visitor`, `ai_count`) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE `ai_count` = `ai_count` + 1', [$today, $visitor]);

            if ($visitor === self::SITE && $db->affectedRows() === 1) {
                // First AI answer of the day: yesterday's visitor keys are no longer needed.
                self::prune($today, $db);
            }
        }

        $session = session();
        $usage   = $session->get(self::SESSION_KEY);
        $count   = is_array($usage) && ($usage['date'] ?? '') === $today ? (int) $usage['count'] : 0;
        $session->set(self::SESSION_KEY, ['date' => $today, 'count' => $count + 1]);
    }

    /**
     * AI answers given today on the whole website.
     */
    public function siteCountToday(): int
    {
        return $this->count(self::SITE, Time::today()->toDateString());
    }

    /**
     * Deletes visitor rows older than yesterday and site rows older than 400 days.
     */
    public static function prune(?string $today = null, ?BaseConnection $db = null): int
    {
        $db ??= db_connect();
        $today = Time::parse($today ?? Time::today()->toDateString());

        $db->table('asisten_usage')->where('visitor !=', self::SITE)->where('date <', $today->subDays(1)->toDateString())->delete();
        $deleted = $db->affectedRows();
        $db->table('asisten_usage')->where('visitor', self::SITE)->where('date <', $today->subDays(400)->toDateString())->delete();

        return $deleted + $db->affectedRows();
    }

    private function visitorCount(string $today): int
    {
        $usage   = session(self::SESSION_KEY);
        $session = is_array($usage) && ($usage['date'] ?? '') === $today ? (int) $usage['count'] : 0;

        return max($session, $this->count($this->visitor, $today));
    }

    private function count(string $visitor, string $date): int
    {
        $db  = $this->db ??= db_connect();
        $row = $db->table('asisten_usage')->select('ai_count')->where('date', $date)->where('visitor', $visitor)->get()->getRowArray();

        return (int) ($row['ai_count'] ?? 0);
    }
}
