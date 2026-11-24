<?php

declare(strict_types=1);

namespace Modules\Referensi\Models;

use CodeIgniter\Model;

/**
 * National list of administrative regions (table ref_wilayah, D-111).
 *
 * Levels: 1 provinsi, 2 kabupaten/kota, 3 kecamatan, 4 desa/kelurahan. Codes
 * follow Kemendagri ("33", "33.19", "33.19.03", "33.19.03.2006"); the parent
 * code is the code without its last part. Names keep the official spelling;
 * displayName() gives the short form used on screens and stored on applicants
 * ("Kabupaten Kudus" → "Kab. Kudus"; everything else unchanged).
 *
 * Reference data only: filled by BundledRegions, never edited by hand.
 */
class RegionModel extends Model
{
    public const LEVELS       = [1 => 'province', 2 => 'regency', 3 => 'district', 4 => 'village'];
    public const SEARCH_LIMIT = 20;

    /**
     * Code shape per level.
     */
    private const CODE_PATTERNS = [
        1 => '/\A\d{2}\z/',
        2 => '/\A\d{2}\.\d{2}\z/',
        3 => '/\A\d{2}\.\d{2}\.\d{2}\z/',
        4 => '/\A\d{2}\.\d{2}\.\d{2}\.\d{4}\z/',
    ];

    protected $table            = 'ref_wilayah';
    protected $primaryKey       = 'code';
    protected $returnType       = 'array';
    protected $useAutoIncrement = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = ['code', 'parent_code', 'level', 'name'];

    /**
     * @var array<string, array<string, mixed>|null> code => row (per request)
     */
    private array $rowCache = [];

    /**
     * The level of a well-formed code (1–4), or null.
     */
    public static function levelOf(string $code): ?int
    {
        foreach (self::CODE_PATTERNS as $level => $pattern) {
            if (preg_match($pattern, $code) === 1) {
                return $level;
            }
        }

        return null;
    }

    public static function parentOf(string $code): ?string
    {
        $cut = strrpos($code, '.');

        return $cut === false ? null : substr($code, 0, $cut);
    }

    /**
     * Short display name: "Kabupaten Kudus" → "Kab. Kudus". Cities ("Kota Semarang"),
     * provinces, districts and villages keep their official name.
     */
    public static function displayName(string $name, int $level): string
    {
        return $level === 2 ? (preg_replace('/\AKabupaten\s+/u', 'Kab. ', $name) ?? $name) : $name;
    }

    /**
     * Village kind from the code: kelurahan (1xxx) or desa (2xxx); null for other levels.
     */
    public static function villageKind(string $code): ?string
    {
        if (self::levelOf($code) !== 4) {
            return null;
        }

        return substr($code, -4, 1) === '1' ? 'kelurahan' : 'desa';
    }

    /**
     * Whether the list has been loaded (any province present).
     */
    public function hasData(): bool
    {
        return $this->db->tableExists($this->table)
            && $this->db->table($this->table)->where('level', 1)->limit(1)->countAllResults() > 0;
    }

    /**
     * One region by code, or null (cached per request).
     *
     * @return array{code: string, parent_code: string|null, level: int, name: string}|null
     */
    public function region(string $code): ?array
    {
        if (! array_key_exists($code, $this->rowCache)) {
            $row = self::levelOf($code) === null ? null : $this->db->table($this->table)->where('code', $code)->get()->getRowArray();

            $this->rowCache[$code] = $row === null ? null : self::typed($row);
        }

        return $this->rowCache[$code];
    }

    /**
     * The region and its ancestors keyed by level (1 = province ... up to the region's level),
     * or null when the code is unknown or a parent is missing.
     *
     * @return array<int, array{code: string, parent_code: string|null, level: int, name: string}>|null
     */
    public function chain(string $code): ?array
    {
        $row = $this->region($code);

        if ($row === null) {
            return null;
        }

        $codes = [];

        for ($c = $code; $c !== null; $c = self::parentOf($c)) {
            $codes[] = $c;
        }

        $missing = array_values(array_filter($codes, fn (string $c): bool => ! array_key_exists($c, $this->rowCache)));

        if ($missing !== []) {
            foreach ($missing as $c) {
                $this->rowCache[$c] = null;
            }

            foreach ($this->db->table($this->table)->whereIn('code', $missing)->get()->getResultArray() as $found) {
                $this->rowCache[$found['code']] = self::typed($found);
            }
        }

        $chain = [];

        foreach ($codes as $c) {
            $found = $this->rowCache[$c] ?? null;

            if ($found === null) {
                return null;
            }

            $chain[$found['level']] = $found;
        }

        ksort($chain);

        return $chain;
    }

    /**
     * Children of a region (provinces when $parent is null or ''), sorted by name
     * without the "Kabupaten"/"Kota" word, for the address selects.
     *
     * @return list<array{code: string, name: string, label: string}>
     */
    public function children(?string $parent): array
    {
        $builder = $this->db->table($this->table)->select('code, level, name');

        if ($parent === null || $parent === '') {
            $builder->where('level', 1);
        } else {
            if (self::levelOf($parent) === null || self::levelOf($parent) === 4) {
                return [];
            }

            $builder->where('parent_code', $parent);
        }

        $rows = array_map(static fn (array $row): array => [
            'code'  => (string) $row['code'],
            'name'  => (string) $row['name'],
            'label' => self::displayName((string) $row['name'], (int) $row['level']),
            'sort'  => self::sortKey((string) $row['name']),
        ], $builder->get()->getResultArray());

        usort($rows, static fn (array $a, array $b): int => [$a['sort'], $a['name']] <=> [$b['sort'], $b['name']]);

        return array_map(static fn (array $row): array => ['code' => $row['code'], 'name' => $row['name'], 'label' => $row['label']], $rows);
    }

    /**
     * Regencies and cities of every province for a single select, grouped by province name.
     *
     * @return array<string, array<string, string>> province => [code => label]
     */
    public function regencyOptions(): array
    {
        $byProvince = [];

        foreach ($this->db->table($this->table)->select('code, parent_code, name')->where('level', 2)->get()->getResultArray() as $row) {
            $byProvince[(string) $row['parent_code']][] = ['code' => (string) $row['code'], 'name' => (string) $row['name'], 'sort' => self::sortKey((string) $row['name'])];
        }

        $out = [];

        foreach ($this->children(null) as $province) {
            $rows = $byProvince[$province['code']] ?? [];
            usort($rows, static fn (array $a, array $b): int => [$a['sort'], $a['name']] <=> [$b['sort'], $b['name']]);

            foreach ($rows as $row) {
                $out[$province['name']][$row['code']] = self::displayName($row['name'], 2);
            }
        }

        return $out;
    }

    /**
     * Searches districts and villages by name ("ploso", "ploso jati", "jati kulon kudus").
     * Every word must appear at the start of a word in the region's name or in its parents'
     * names, and at least one in the region's own name. Regions in the school's own
     * regency/province (School.regionCode) come first.
     *
     * @return list<array{code: string, level: int, name: string, path: string, kind: string|null, codes: array<string, string|null>, names: array<string, string|null>}>
     */
    public function search(string $text, int $limit = self::SEARCH_LIMIT): array
    {
        $words = self::searchWords($text);

        if ($words === []) {
            return [];
        }

        $limit  = max(1, min($limit, self::SEARCH_LIMIT));
        $home   = (string) setting('School.regionCode');
        $home   = self::levelOf($home) !== null ? $home : '';
        $phrase = implode(' ', $words);
        $found  = [];

        foreach ([3, 4] as $level) {
            foreach ($this->searchLevel($level, $words, $home, $limit * 2) as $row) {
                $found[] = $row;
            }
        }

        // "getas pejaten" for "Getaspejaten": try the words written together.
        if ($found === [] && count($words) > 1) {
            return $this->search(implode('', $words), $limit);
        }

        // Runs of words ("ploso", "jati", "ploso jati") to recognise a name typed in full.
        $runs = [];

        for ($i = 0, $n = count($words); $i < $n; $i++) {
            for ($j = $i; $j < $n; $j++) {
                $runs[] = implode(' ', array_slice($words, $i, $j - $i + 1));
            }
        }

        $rank = static function (array $row) use ($words, $phrase, $home, $runs): array {
            $name    = mb_strtolower($row['name']);
            $tokens  = preg_split('/[^\p{L}\p{N}\'’]+/u', mb_strtolower($row['path']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $whole   = count(array_intersect($words, $tokens));
            $inName  = count(array_filter($words, static fn (string $w): bool => self::hasWordStart($name, $w)));
            $homeHit = $home === '' ? 1 : (str_starts_with($row['code'] . '.', $home . '.') ? 0 : (str_starts_with($row['code'], substr($home, 0, 2) . '.') ? 1 : 2));

            return [
                $name === $phrase ? 0 : (in_array($name, $runs, true) ? 1 : 2),
                -$whole,
                -$inName,
                str_starts_with($name, $phrase) ? 0 : 1,
                $homeHit,
                $row['level'],
                $name,
                $row['path'],
            ];
        };

        usort($found, static fn (array $a, array $b): int => $rank($a) <=> $rank($b));

        return array_slice($found, 0, $limit);
    }

    /**
     * Lowercase words of at least two letters/digits, without "desa", "kel." and similar words.
     *
     * @return list<string>
     */
    public static function searchWords(string $text): array
    {
        $text  = mb_strtolower(trim(mb_substr($text, 0, 100)));
        $skip  = ['desa', 'ds', 'kel', 'kelurahan', 'kec', 'kecamatan', 'kab', 'kabupaten', 'prov', 'provinsi'];
        $words = [];

        foreach (preg_split("/[^\\p{L}\\p{N}'’]+/u", $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $word = trim($word, "'’");

            if (mb_strlen($word) < 2 || in_array($word, $skip, true) || in_array($word, $words, true)) {
                continue;
            }

            $words[] = $word;

            if (count($words) === 5) {
                break;
            }
        }

        // One word must be long enough to be worth a search.
        return array_filter($words, static fn (string $w): bool => mb_strlen($w) >= 3) === [] ? [] : $words;
    }

    /**
     * Full display path from a chain: "Ploso, Jati, Kab. Kudus, Jawa Tengah".
     *
     * @param array<int, array{name: string, level: int}> $chain
     */
    public static function pathOf(array $chain): string
    {
        $parts = [];

        foreach (array_reverse($chain, true) as $level => $row) {
            $parts[] = self::displayName($row['name'], (int) $level);
        }

        return implode(', ', $parts);
    }

    /**
     * @param list<string> $words
     * @param string       $home  School.regionCode ('' = none): its regions are fetched first
     *
     * @return list<array{code: string, level: int, name: string, path: string, kind: string|null, codes: array<string, string|null>, names: array<string, string|null>}>
     */
    private function searchLevel(int $level, array $words, string $home, int $limit): array
    {
        $t       = $this->db->prefixTable($this->table);
        $joins   = [];
        $aliases = ['r0'];

        // r0 = the region itself, r1 = its parent, ... up to the province.
        for ($i = 1; $i < $level; $i++) {
            $joins[]   = "JOIN {$t} r{$i} ON r{$i}.code = r" . ($i - 1) . '.parent_code';
            $aliases[] = 'r' . $i;
        }

        $wordAt = function (string $alias, string $word): string {
            // Words hold only letters, digits and apostrophes (searchWords); the apostrophe is doubled for SQL.
            $like = str_replace("'", "''", $this->db->escapeLikeString($word));

            return "({$alias}.name LIKE '{$like}%' ESCAPE '!' OR {$alias}.name LIKE '% {$like}%' ESCAPE '!' OR {$alias}.name LIKE '%-{$like}%' ESCAPE '!')";
        };

        $where = ['r0.level = ' . $level];
        $own   = [];

        foreach ($words as $word) {
            $own[]   = $wordAt('r0', $word);
            $where[] = '(' . implode(' OR ', array_map(static fn (string $a): string => $wordAt($a, $word), $aliases)) . ')';
        }

        $where[] = '(' . implode(' OR ', $own) . ')';
        $select  = implode(', ', array_map(static fn (string $a): string => "{$a}.code AS {$a}_code, {$a}.name AS {$a}_name", $aliases));
        $phrase  = str_replace("'", "''", $this->db->escapeLikeString(implode(' ', $words)));
        $order   = ["CASE WHEN r0.name LIKE '{$phrase}' ESCAPE '!' THEN 0 ELSE 1 END"];

        if ($home !== '') {
            // $home passed levelOf(): digits and dots only.
            $order[] = "CASE WHEN r0.code LIKE '{$home}.%' THEN 0 WHEN r0.code LIKE '" . substr($home, 0, 2) . ".%' THEN 1 ELSE 2 END";
        }

        $order[] = "CASE WHEN r0.name LIKE '{$phrase}%' ESCAPE '!' THEN 0 ELSE 1 END";
        $order[] = 'r0.name';
        $sql     = "SELECT {$select} FROM {$t} r0 " . implode(' ', $joins) . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY ' . implode(', ', $order) . ' LIMIT ' . (int) $limit;
        $out = [];

        foreach ($this->db->query($sql)->getResultArray() as $row) {
            $chain = [];

            foreach ($aliases as $i => $alias) {
                $chain[$level - $i] = ['code' => (string) $row[$alias . '_code'], 'name' => (string) $row[$alias . '_name'], 'level' => $level - $i];
            }

            ksort($chain);
            $out[] = self::describe($chain);
        }

        return $out;
    }

    /**
     * A search result / lookup answer for the deepest region of a chain.
     *
     * @param array<int, array{code: string, name: string, level: int}> $chain
     *
     * @return array{code: string, level: int, name: string, path: string, kind: string|null, codes: array<string, string|null>, names: array<string, string|null>}
     */
    public static function describe(array $chain): array
    {
        $level = (int) max(array_keys($chain));
        $codes = [];
        $names = [];

        foreach (self::LEVELS as $l => $key) {
            $codes[$key] = isset($chain[$l]) ? $chain[$l]['code'] : null;
            $names[$key] = isset($chain[$l]) ? self::displayName($chain[$l]['name'], $l) : null;
        }

        return [
            'code'  => $chain[$level]['code'],
            'level' => $level,
            'name'  => $chain[$level]['name'],
            'path'  => self::pathOf($chain),
            'kind'  => self::villageKind($chain[$level]['code']),
            'codes' => $codes,
            'names' => $names,
        ];
    }

    private static function hasWordStart(string $name, string $word): bool
    {
        return str_starts_with($name, $word) || str_contains($name, ' ' . $word) || str_contains($name, '-' . $word);
    }

    /**
     * "Kabupaten Kudus" → "kudus 1", "Kota Semarang" → "semarang 2": cities after the regency of the same name.
     */
    private static function sortKey(string $name): string
    {
        if (preg_match('/\A(Kabupaten|Kota)\s+(.+)\z/u', $name, $m) === 1) {
            return mb_strtolower($m[2]) . ($m[1] === 'Kabupaten' ? ' 1' : ' 2');
        }

        return mb_strtolower($name);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{code: string, parent_code: string|null, level: int, name: string}
     */
    private static function typed(array $row): array
    {
        return [
            'code'        => (string) $row['code'],
            'parent_code' => $row['parent_code'] === null ? null : (string) $row['parent_code'],
            'level'       => (int) $row['level'],
            'name'        => (string) $row['name'],
        ];
    }
}
