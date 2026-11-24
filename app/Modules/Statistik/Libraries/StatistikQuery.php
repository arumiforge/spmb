<?php

declare(strict_types=1);

namespace Modules\Statistik\Libraries;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

/**
 * Aggregates for the Statistik page (D-111). Every number comes from one
 * GROUP BY query over `applicants` (never a loop over applicants in PHP),
 * limited by the filters: academic year, wave and status; soft-deleted
 * applicants never count.
 *
 * Places are grouped by the text columns, trimmed and case-insensitive. When
 * the address has region codes (`regency_code`, `district_code`,
 * `village_code` from the national region list) a row with a code is grouped
 * by its code instead; rows without one keep the text grouping. Group keys are
 * opaque strings: a code ("33.19") or "t:" + the normalised names, so a key
 * also works as a drill-down value in the address.
 *
 * Rows are arrays {key: string, label: string, count: int, note?: string|null};
 * an empty label means "not filled in".
 */
final class StatistikQuery
{
    /**
     * @var array<string, true>|null
     */
    private ?array $fields = null;

    public function __construct(private readonly StatistikFilter $filter, private ?BaseConnection $db = null)
    {
    }

    // ------------------------------------------------------------------
    // Summary
    // ------------------------------------------------------------------

    /**
     * @return array{total: int, online: int, offline: int, complete: int}
     */
    public function totals(): array
    {
        $row = $this->base()->select(
            "COUNT(*) AS total, SUM(a.method = 'online') AS online, SUM(a.method = 'offline') AS offline, SUM(a.document_status = 'complete') AS complete",
            false,
        )->get()->getRowArray() ?? [];

        return [
            'total'    => (int) ($row['total'] ?? 0),
            'online'   => (int) ($row['online'] ?? 0),
            'offline'  => (int) ($row['offline'] ?? 0),
            'complete' => (int) ($row['complete'] ?? 0),
        ];
    }

    // ------------------------------------------------------------------
    // Location: regency → district → village
    // ------------------------------------------------------------------

    /**
     * Applicants per home regency, or per district of one regency, or per
     * village of one district (following the kab/kec filter). A drill-down
     * value that no longer matches any applicant is ignored.
     *
     * @return array{level: string, regency: array{key: string, label: string}|null, district: array{key: string, label: string}|null, rows: list<array<string, mixed>>}
     */
    public function location(): array
    {
        $regency  = $this->filter->regency === null ? null : $this->place('regency', $this->filter->regency);
        $district = $regency === null || $this->filter->district === null ? null : $this->place('district', $this->filter->district, $regency['key']);

        if ($district !== null) {
            $rows = $this->grouped($this->placeKey('village'), 'TRIM(a.village)', function (BaseBuilder $b) use ($district): void {
                $this->whereKey($b, $this->placeKey('district'), $district['key']);
            });

            return ['level' => 'village', 'regency' => $regency, 'district' => $district, 'rows' => self::byCount(self::blankKeys($rows))];
        }

        if ($regency !== null) {
            $rows = $this->grouped($this->placeKey('district'), 'TRIM(a.district)', function (BaseBuilder $b) use ($regency): void {
                $this->whereKey($b, $this->placeKey('regency'), $regency['key']);
            });

            return ['level' => 'district', 'regency' => $regency, 'district' => null, 'rows' => self::byCount(self::blankKeys($rows))];
        }

        return ['level' => 'regency', 'regency' => null, 'district' => null, 'rows' => self::byCount(self::blankKeys($this->grouped($this->placeKey('regency'), 'TRIM(a.regency)')))];
    }

    /**
     * Rows whose place name is empty cannot be opened further: their key becomes ''.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private static function blankKeys(array $rows): array
    {
        return array_map(static fn (array $r): array => $r['label'] === '' ? ['key' => ''] + $r : $r, $rows);
    }

    /**
     * Group key of a place level: its code when the column exists and is
     * filled, otherwise "t:" + the normalised names down to that level (so a
     * district key also tells the regency apart).
     */
    public function placeKey(string $level): string
    {
        $levels = ['regency', 'district', 'village'];
        $names  = [];

        foreach (array_slice($levels, 0, array_search($level, $levels, true) + 1) as $part) {
            $names[] = self::norm('a.' . $part);
        }

        $text = "CONCAT('t:', " . implode(", '|', ", $names) . ')';

        return $this->hasField($level . '_code') ? "COALESCE(NULLIF(a.{$level}_code, ''), {$text})" : $text;
    }

    /**
     * Name of one place by its key (within a regency for a district), or null
     * when no applicant in the current filter matches.
     *
     * @return array{key: string, label: string}|null
     */
    private function place(string $level, string $key, ?string $regencyKey = null): ?array
    {
        $builder = $this->base()->select("COUNT(*) AS n, MIN(TRIM(a.{$level})) AS label", false);
        $this->whereKey($builder, $this->placeKey($level), $key);

        if ($regencyKey !== null) {
            $this->whereKey($builder, $this->placeKey('regency'), $regencyKey);
        }

        $row = $builder->get()->getRowArray();

        return $row === null || (int) $row['n'] === 0 || trim((string) $row['label']) === '' ? null : ['key' => $key, 'label' => (string) $row['label']];
    }

    // ------------------------------------------------------------------
    // Origin schools (ref_sekolah)
    // ------------------------------------------------------------------

    /**
     * Applicants per origin school district ("kecamatan"), regency
     * ("kabupaten") or school ("nama"). Schools typed in by hand (not in the
     * list) form one group "lain" in the place modes and keep their typed
     * name in the school mode (key "n:…", note "lain").
     *
     * @return list<array<string, mixed>>
     */
    public function schools(string $mode): array
    {
        $typed = "TRIM(COALESCE(a.previous_school_name, '')) <> ''";

        [$key, $label, $note] = match ($mode) {
            'kabupaten' => [
                "CASE WHEN s.id IS NOT NULL THEN CONCAT('s:', " . self::norm('s.regency') . ") WHEN {$typed} THEN 'lain' ELSE '' END",
                "CASE WHEN s.id IS NOT NULL THEN TRIM(COALESCE(s.regency, '')) ELSE '' END",
                null,
            ],
            'nama' => [
                "CASE WHEN s.id IS NOT NULL THEN CONCAT('s:', s.id) WHEN {$typed} THEN CONCAT('n:', " . self::norm('a.previous_school_name') . ") ELSE '' END",
                "CASE WHEN s.id IS NOT NULL THEN TRIM(s.name) ELSE TRIM(COALESCE(a.previous_school_name, '')) END",
                "CASE WHEN s.id IS NOT NULL THEN CONCAT_WS(', ', NULLIF(TRIM(s.district), ''), NULLIF(TRIM(s.regency), '')) ELSE 'lain' END",
            ],
            default => [
                "CASE WHEN s.id IS NOT NULL THEN CONCAT('s:', " . self::norm('s.regency') . ", '|', " . self::norm('s.district') . ") WHEN {$typed} THEN 'lain' ELSE '' END",
                "CASE WHEN s.id IS NOT NULL THEN CONCAT_WS(', ', NULLIF(TRIM(s.district), ''), NULLIF(TRIM(s.regency), '')) ELSE '' END",
                null,
            ],
        };

        $rows = $this->grouped($key, $label, $this->joinSchools(...), $note === null ? [] : ['note' => "MIN({$note})"]);

        // "lain" has no label of its own; the view names it.
        return self::byCount($rows, ['lain']);
    }

    /**
     * Origin school level (SD, MI, …) from the list; "lain" = typed in by hand.
     *
     * @return list<array<string, mixed>>
     */
    public function schoolLevels(): array
    {
        $key = "CASE WHEN s.id IS NOT NULL THEN COALESCE(NULLIF(UPPER(TRIM(s.level)), ''), '?') WHEN TRIM(COALESCE(a.previous_school_name, '')) <> '' THEN 'lain' ELSE '' END";

        return self::byCount($this->grouped($key, $key, $this->joinSchools(...)), ['?', 'lain']);
    }

    /**
     * Origin school ownership: negeri, swasta, "?" (not recorded in the list), "lain" (typed in by hand).
     *
     * @return list<array<string, mixed>>
     */
    public function schoolOwnership(): array
    {
        $key = "CASE WHEN s.id IS NOT NULL THEN COALESCE(NULLIF(LOWER(TRIM(s.ownership)), ''), '?') WHEN TRIM(COALESCE(a.previous_school_name, '')) <> '' THEN 'lain' ELSE '' END";

        return self::byCount($this->grouped($key, $key, $this->joinSchools(...)), ['?', 'lain']);
    }

    private function joinSchools(BaseBuilder $builder): void
    {
        $builder->join('ref_sekolah s', 's.id = a.previous_school_id', 'left');
    }

    // ------------------------------------------------------------------
    // Family and student profile
    // ------------------------------------------------------------------

    /**
     * Counts per value of a text column (trimmed, case-insensitive), most first.
     *
     * @return list<array<string, mixed>>
     */
    public function textCounts(string $column): array
    {
        return self::byCount($this->grouped(self::norm('a.' . $column), 'TRIM(a.' . $column . ')'));
    }

    /**
     * Counts per income range in the order of the school's list
     * (Pendaftaran.incomeOptions); values not in the list follow, then "not filled in".
     *
     * @return list<array<string, mixed>>
     */
    public function incomeCounts(string $column): array
    {
        return self::inListOrder($this->grouped(self::norm('a.' . $column), 'TRIM(a.' . $column . ')'), self::incomeOptions());
    }

    /**
     * Counts per code of a short code column (gender, method, document status, has_guardian).
     *
     * @return list<array<string, mixed>>
     */
    public function codeCounts(string $column): array
    {
        if (! $this->hasField($column)) {
            return [];
        }

        return $this->grouped("COALESCE(TRIM(CAST(a.{$column} AS CHAR)), '')", "COALESCE(TRIM(CAST(a.{$column} AS CHAR)), '')");
    }

    /**
     * Applicants per age in whole years on the day they registered (from birth_date).
     *
     * @return array<int, int> age => count, youngest first
     */
    public function ages(): array
    {
        $rows = $this->base()
            ->select('TIMESTAMPDIFF(YEAR, a.birth_date, DATE(a.registered_at)) AS age, COUNT(*) AS n', false)
            ->where('a.birth_date IS NOT NULL', null, false)
            ->groupBy('age', false)
            ->get()->getResultArray();

        $out = [];

        foreach ($rows as $row) {
            $out[(int) $row['age']] = (int) $row['n'];
        }

        ksort($out);

        return $out;
    }

    // ------------------------------------------------------------------
    // Registration
    // ------------------------------------------------------------------

    /**
     * Applicants per wave, oldest wave first; key "0" = no wave.
     *
     * @return list<array<string, mixed>>
     */
    public function waves(): array
    {
        $rows = $this->base()
            ->select('COALESCE(a.wave_id, 0) AS k, MIN(w.name) AS label, MIN(w.starts_on) AS starts_on, COUNT(*) AS n', false)
            ->join('waves w', 'w.id = a.wave_id', 'left')
            ->groupBy('k', false)
            ->get()->getResultArray();

        usort($rows, static fn (array $x, array $y): int => [(int) $x['k'] === 0, (string) $x['starts_on'], (int) $x['k']] <=> [(int) $y['k'] === 0, (string) $y['starts_on'], (int) $y['k']]);

        return array_map(static fn (array $r): array => ['key' => (string) $r['k'], 'label' => (string) $r['label'], 'count' => (int) $r['n']], $rows);
    }

    /**
     * Registrations per day or per week (weeks start on Monday), with the empty
     * days or weeks between the first and the last filled in as 0.
     *
     * @return list<array{date: string, count: int}> date = the day, or the Monday of the week
     */
    public function timeline(string $mode): array
    {
        $day  = 'DATE(a.registered_at)';
        $expr = $mode === 'minggu' ? "DATE_SUB({$day}, INTERVAL WEEKDAY(a.registered_at) DAY)" : $day;
        $rows = $this->base()->select("{$expr} AS d, COUNT(*) AS n", false)->groupBy('d', false)->orderBy('d', 'ASC', false)->get()->getResultArray();

        if ($rows === []) {
            return [];
        }

        $counts = array_column($rows, 'n', 'd');
        $step   = $mode === 'minggu' ? 7 : 1;
        $cursor = Time::parse((string) array_key_first($counts));
        $last   = (string) array_key_last($counts);
        $out    = [];

        // Bounded by the dates, not by the number of applicants.
        while (($date = $cursor->toDateString()) <= $last) {
            $out[]  = ['date' => $date, 'count' => (int) ($counts[$date] ?? 0)];
            $cursor = $cursor->addDays($step);
        }

        return $out;
    }

    /**
     * First and last registration date in the filter (for choosing days or weeks).
     *
     * @return array{0: string, 1: string}|null
     */
    public function dateSpan(): ?array
    {
        $row = $this->base()->select('MIN(DATE(a.registered_at)) AS first_day, MAX(DATE(a.registered_at)) AS last_day', false)->get()->getRowArray();

        return $row === null || $row['first_day'] === null ? null : [(string) $row['first_day'], (string) $row['last_day']];
    }

    // ------------------------------------------------------------------
    // Cross-tabs
    // ------------------------------------------------------------------

    /**
     * Income range (of the father or the mother) per wave.
     *
     * @return array{groups: list<array{key: string, label: string, count: int}>, income: list<array{key: string, label: string}>, cells: array<string, array<string, int>>}
     */
    public function incomeByWave(string $column): array
    {
        $rows = $this->base()
            ->select('COALESCE(a.wave_id, 0) AS g, MIN(w.name) AS glabel, MIN(w.starts_on) AS gsort, ' . self::norm('a.' . $column) . ' AS i, MIN(TRIM(a.' . $column . ')) AS ilabel, COUNT(*) AS n', false)
            ->join('waves w', 'w.id = a.wave_id', 'left')
            ->groupBy('g, i', false)
            ->get()->getResultArray();

        usort($rows, static fn (array $x, array $y): int => [(int) $x['g'] === 0, (string) $x['gsort'], (int) $x['g']] <=> [(int) $y['g'] === 0, (string) $y['gsort'], (int) $y['g']]);

        return self::matrix($rows, null);
    }

    /**
     * Income range (of the father or the mother) per home district: the
     * $limit districts with the most applicants, the rest in one group "lain".
     *
     * @return array{groups: list<array{key: string, label: string, count: int, note?: string}>, income: list<array{key: string, label: string}>, cells: array<string, array<string, int>>}
     */
    public function incomeByDistrict(string $column, int $limit = 8): array
    {
        $rows = $this->base()
            ->select($this->placeKey('district') . ' AS g, MIN(TRIM(a.district)) AS glabel, MIN(TRIM(a.regency)) AS gnote, ' . self::norm('a.' . $column) . ' AS i, MIN(TRIM(a.' . $column . ')) AS ilabel, COUNT(*) AS n', false)
            ->groupBy('g, i', false)
            ->get()->getResultArray();

        return self::matrix($rows, $limit);
    }

    /**
     * Builds the cross-tab from grouped rows (g, glabel, [gnote], i, ilabel, n).
     *
     * @param list<array<string, mixed>> $rows  in group order (when $limit is null)
     * @param int|null                   $limit Keep the biggest groups, fold the rest into "lain"
     *
     * @return array{groups: list<array<string, mixed>>, income: list<array{key: string, label: string}>, cells: array<string, array<string, int>>}
     */
    private static function matrix(array $rows, ?int $limit): array
    {
        $groups = [];
        $income = [];
        $cells  = [];

        foreach ($rows as $row) {
            $g = (string) $row['g'];
            $i = (string) $row['i'];
            $groups[$g] ??= ['key' => $g, 'label' => trim((string) $row['glabel']), 'count' => 0] + (isset($row['gnote']) ? ['note' => (string) $row['gnote']] : []);
            $groups[$g]['count'] += (int) $row['n'];
            $income[$i] ??= ['key' => $i, 'label' => trim((string) $row['ilabel']), 'count' => 0];
            $income[$i]['count'] += (int) $row['n'];
            $cells[$g][$i] = ($cells[$g][$i] ?? 0) + (int) $row['n'];
        }

        if ($limit !== null) {
            $sorted = self::byCount(array_values($groups));
            $keep   = array_slice($sorted, 0, $limit);
            $rest   = array_slice($sorted, $limit);

            if ($rest !== []) {
                $other = ['key' => 'lain', 'label' => '', 'count' => 0];

                foreach ($rest as $group) {
                    $other['count'] += $group['count'];

                    foreach ($cells[$group['key']] ?? [] as $i => $n) {
                        $cells['lain'][$i] = ($cells['lain'][$i] ?? 0) + $n;
                    }

                    unset($cells[$group['key']]);
                }

                $keep[] = $other;
            }

            $groups = $keep;
        } else {
            $groups = array_values($groups);
        }

        $income = array_map(
            static fn (array $r): array => ['key' => $r['key'], 'label' => $r['label']],
            self::inListOrder(array_values($income), self::incomeOptions()),
        );

        return ['groups' => $groups, 'income' => $income, 'cells' => $cells];
    }

    // ------------------------------------------------------------------
    // Building blocks
    // ------------------------------------------------------------------

    /**
     * Applicants in the filter: academic year, wave, status; never soft-deleted.
     */
    private function base(): BaseBuilder
    {
        $builder = $this->db()->table('applicants a')
            ->where('a.deleted_at', null)
            ->where('a.academic_year', $this->filter->year);

        match ($this->filter->status) {
            'aktif' => $builder->where('a.status !=', 'cancelled'),
            'semua' => null,
            default => $builder->where('a.status', $this->filter->status),
        };

        if ($this->filter->wave === 'tanpa') {
            $builder->where('a.wave_id', null);
        } elseif (is_int($this->filter->wave)) {
            $builder->where('a.wave_id', $this->filter->wave);
        }

        return $builder;
    }

    /**
     * COUNT(*) per key expression, with the smallest label spelling of each group.
     *
     * @param callable(BaseBuilder): void|null $scope Extra joins or conditions
     * @param array<string, string>            $extra Extra aggregate columns, alias => SQL
     *
     * @return list<array<string, mixed>>
     */
    private function grouped(string $key, string $label, ?callable $scope = null, array $extra = []): array
    {
        $builder = $this->base();

        if ($scope !== null) {
            $scope($builder);
        }

        $select = "{$key} AS k, MIN({$label}) AS label, COUNT(*) AS n";

        foreach ($extra as $alias => $sql) {
            $select .= ", {$sql} AS {$alias}";
        }

        $rows = $builder->select($select, false)->groupBy('k', false)->get()->getResultArray();

        return array_map(static function (array $r) use ($extra): array {
            $row = ['key' => (string) $r['k'], 'label' => trim((string) $r['label']), 'count' => (int) $r['n']];

            foreach (array_keys($extra) as $alias) {
                $row[$alias] = $r[$alias] === null ? null : (string) $r[$alias];
            }

            return $row;
        }, $rows);
    }

    private function whereKey(BaseBuilder $builder, string $expression, string $value): void
    {
        $builder->where($expression . ' = ' . $this->db()->escape($value), null, false);
    }

    /**
     * Trimmed, lower-case text of a column ('' when empty), for grouping.
     */
    private static function norm(string $column): string
    {
        return "LOWER(TRIM(COALESCE({$column}, '')))";
    }

    /**
     * Most first, then by label; "lain" and the keys in $last next, groups
     * that are not filled in (empty key or label) at the very end.
     *
     * @param list<array<string, mixed>> $rows
     * @param list<string>               $last
     *
     * @return list<array<string, mixed>>
     */
    public static function byCount(array $rows, array $last = []): array
    {
        $rank = static fn (array $r): int => match (true) {
            $r['key'] === 'lain' || in_array($r['key'], $last, true) => 1,
            $r['key'] === ''     || $r['label'] === ''               => 2,
            default                                                  => 0,
        };

        usort($rows, static fn (array $x, array $y): int => [$rank($x), -$x['count'], mb_strtolower($x['label'])] <=> [$rank($y), -$y['count'], mb_strtolower($y['label'])]);

        return $rows;
    }

    /**
     * Rows in the order of $options (matched case-insensitively), then the
     * values not in the list (most first), then "not filled in".
     *
     * @param list<array<string, mixed>> $rows
     * @param list<string>               $options
     *
     * @return list<array<string, mixed>>
     */
    public static function inListOrder(array $rows, array $options): array
    {
        $position = [];

        foreach (array_values($options) as $i => $option) {
            $position[mb_strtolower(trim($option))] ??= $i;
        }

        $rank = static fn (array $r): array => $r['key'] === ''
            ? [2, 0, 0]
            : (isset($position[$r['key']]) ? [0, $position[$r['key']], 0] : [1, 0, -$r['count']]);

        usort($rows, static fn (array $x, array $y): int => $rank($x) <=> $rank($y));

        return $rows;
    }

    /**
     * @return list<string>
     */
    private static function incomeOptions(): array
    {
        $options = setting('Pendaftaran.incomeOptions');

        return is_array($options) ? array_values(array_map('strval', $options)) : [];
    }

    private function hasField(string $field): bool
    {
        if ($this->fields === null) {
            $this->fields = array_fill_keys($this->db()->getFieldNames('applicants'), true);
        }

        return isset($this->fields[$field]);
    }

    private function db(): BaseConnection
    {
        return $this->db ??= db_connect();
    }
}
