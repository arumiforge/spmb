<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Services;

use CodeIgniter\Database\BaseConnection;
use Throwable;

/**
 * Registration numbers (FR-REG-03, D-031): one shared sequence for online and
 * offline registrations, unique, immutable and never reused.
 *
 * next() locks the counter row of the scope (SELECT … FOR UPDATE) inside a
 * transaction. Call it inside the transaction that inserts the applicant so
 * the number and the row are committed together.
 */
class RegistrationNumber
{
    /**
     * Allowed format: letters, digits, - / . _ and the tokens {YEAR}, {YY}, {0…0}.
     */
    private const FORMAT_PATTERN = '#\A(?:[A-Za-z0-9./_-]|\{YEAR\}|\{YY\}|\{0+\})+\z#';

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Next number for the academic year, e.g. "PMB-2027-0001".
     */
    public function next(string $academicYear): string
    {
        $format = (string) setting('Pendaftaran.numberFormat');

        if (! self::isValidFormat($format)) {
            $format = 'PMB-{YEAR}-{0000}';
        }

        $scope = self::scope($academicYear);
        $table = $this->db->prefixTable('registration_counters');

        // Nested inside the caller's transaction, only the outermost begin/commit/rollback is real.
        $this->db->transBegin();

        try {
            $row = $this->db->query("SELECT last_number FROM {$table} WHERE scope = ? FOR UPDATE", [$scope])->getRowArray();

            if ($row === null) {
                // Two first registrations at once: INSERT IGNORE lets one win, then both lock the row.
                $this->db->query("INSERT IGNORE INTO {$table} (scope, last_number, updated_at) VALUES (?, 0, ?)", [$scope, date('Y-m-d H:i:s')]);
                $row = $this->db->query("SELECT last_number FROM {$table} WHERE scope = ? FOR UPDATE", [$scope])->getRowArray();
            }

            $sequence = (int) ($row['last_number'] ?? 0);

            // Skip numbers that already exist (e.g. the counter was reset by hand): never reuse one.
            do {
                $sequence++;
                $number = self::format($format, $academicYear, $sequence);
            } while ($this->exists($number));

            $this->db->query("UPDATE {$table} SET last_number = ?, updated_at = ? WHERE scope = ?", [$sequence, date('Y-m-d H:i:s'), $scope]);
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        $this->db->transCommit();

        return $number;
    }

    /**
     * Counter scope: the academic year, or "all" when numbers never restart.
     */
    public static function scope(string $academicYear): string
    {
        return setting('Pendaftaran.numberScope') === 'never' ? 'all' : $academicYear;
    }

    /**
     * Applies the format: {YEAR} → 2027, {YY} → 27, {0000} → 0012.
     */
    public static function format(string $format, string $academicYear, int $sequence): string
    {
        $year = (string) (int) substr($academicYear, 0, 4);

        return (string) preg_replace_callback(
            '/\{(YEAR|YY|0+)\}/',
            static fn (array $m): string => match (true) {
                $m[1] === 'YEAR' => $year,
                $m[1] === 'YY'   => substr($year, -2),
                default          => str_pad((string) $sequence, strlen($m[1]), '0', STR_PAD_LEFT),
            },
            $format,
        );
    }

    /**
     * A format is valid when it uses only allowed characters, has exactly one
     * sequence token and its numbers fit the column (30 characters).
     */
    public static function isValidFormat(string $format): bool
    {
        if (preg_match(self::FORMAT_PATTERN, $format) !== 1 || preg_match_all('/\{0+\}/', $format) !== 1) {
            return false;
        }

        return strlen(self::format($format, '2027/2028', 99999)) <= 30;
    }

    private function exists(string $number): bool
    {
        return $this->db->table('applicants')->where('registration_number', $number)->countAllResults() > 0;
    }
}
