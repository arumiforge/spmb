<?php

declare(strict_types=1);

namespace Modules\Referensi\Libraries;

use CodeIgniter\Database\BaseConnection;
use Modules\Referensi\Models\RegionModel;

/**
 * Matches a typed address (province, regency/city, district, village as free
 * text) to the national region list (D-111).
 *
 * Each level is cleaned first: case, extra spaces and the usual words in front
 * ("Prov.", "Kab.", "Kabupaten", "Kota", "Kec.", "Ds.", "Desa", "Kel." ...).
 * Matching runs from the province down, each level only inside the regions
 * still possible above it. Per level: the same name, then the same letters
 * without spaces and marks ("Getas Pejaten" = "Getaspejaten"), then, when
 * allowed and only inside a known parent, a name one or two letters off
 * ("Plosso" → "Ploso"). A level counts when exactly one region is left; the
 * result is the deepest level that counts, with all its parents. A level whose
 * text matches nothing stops the search there (the levels below are not guessed).
 */
class RegionMatcher
{
    /**
     * Common short names of provinces.
     */
    private const PROVINCE_ALIASES = [
        'jateng'          => 'jawa tengah', 'jatim' => 'jawa timur', 'jabar' => 'jawa barat', 'diy' => 'daerah istimewa yogyakarta',
        'yogyakarta'      => 'daerah istimewa yogyakarta', 'jogja' => 'daerah istimewa yogyakarta', 'jogjakarta' => 'daerah istimewa yogyakarta',
        'di yogyakarta'   => 'daerah istimewa yogyakarta', 'dki' => 'daerah khusus ibukota jakarta', 'dki jakarta' => 'daerah khusus ibukota jakarta',
        'jakarta'         => 'daerah khusus ibukota jakarta', 'dk jakarta' => 'daerah khusus ibukota jakarta', 'daerah khusus jakarta' => 'daerah khusus ibukota jakarta',
        'ntb'             => 'nusa tenggara barat', 'ntt' => 'nusa tenggara timur', 'sumut' => 'sumatera utara', 'sumbar' => 'sumatera barat',
        'sumsel'          => 'sumatera selatan', 'kalbar' => 'kalimantan barat', 'kalteng' => 'kalimantan tengah', 'kalsel' => 'kalimantan selatan',
        'kaltim'          => 'kalimantan timur', 'kaltara' => 'kalimantan utara', 'sulut' => 'sulawesi utara', 'sulteng' => 'sulawesi tengah',
        'sulsel'          => 'sulawesi selatan', 'sultra' => 'sulawesi tenggara', 'sulbar' => 'sulawesi barat', 'babel' => 'kepulauan bangka belitung',
        'bangka belitung' => 'kepulauan bangka belitung', 'kepri' => 'kepulauan riau', 'nad' => 'aceh',
    ];

    /**
     * Similar names are only tried when the parent set is this small.
     */
    private const MAX_PARENTS_FOR_SIMILAR = 3;

    private readonly BaseConnection $db;

    /**
     * @var array<string, list<array{code: string, name: string, level: int}>> children by "level|parent" (per instance)
     */
    private array $childCache = [];

    public function __construct(?BaseConnection $db = null, private readonly ?RegionModel $regions = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @param array{province?: string|null, regency?: string|null, district?: string|null, village?: string|null} $address
     * @param bool                                                                                                $similar also accept names one or two letters off
     *
     * @return array{level: int, codes: array<string, string|null>, names: array<string, string|null>, unmatched: list<string>}
     *                                                                                                                          level: deepest matched level (0 = nothing);
     *                                                                                                                          names: display names of the matched levels;
     *                                                                                                                          unmatched: levels with text that could not be matched
     */
    public function match(array $address, bool $similar = true): array
    {
        $texts = [];

        foreach (RegionModel::LEVELS as $level => $key) {
            $texts[$level] = trim((string) ($address[$key] ?? ''));
        }

        // Candidates per level: null = not narrowed (no text), list = the regions still possible.
        $parents  = null;
        $unique   = [];
        $stopped  = false;
        $notFound = [];

        foreach (RegionModel::LEVELS as $level => $key) {
            if ($stopped) {
                if ($texts[$level] !== '') {
                    $notFound[] = $key;
                }

                continue;
            }

            if ($texts[$level] === '') {
                // Nothing typed: the level stays open, its children are searched under all parents.
                $parents = $parents === null ? null : $this->childrenOf($parents, $level);

                if ($parents !== null && count($parents) > 2000) {
                    $parents = null;
                }

                continue;
            }

            $found = $this->find($level, $texts[$level], $parents, $similar);

            if ($found === []) {
                $notFound[] = $key;
                $stopped    = true;

                continue;
            }

            $parents = $found;

            if (count($found) === 1) {
                $unique[$level] = $found[0]['code'];
            }
        }

        // The deepest level that ended with exactly one region (its parents follow from the code).
        $deepest = $unique === [] ? null : $unique[max(array_keys($unique))];
        $result  = ['level' => 0, 'codes' => [], 'names' => [], 'unmatched' => $notFound];

        foreach (RegionModel::LEVELS as $key) {
            $result['codes'][$key] = null;
            $result['names'][$key] = null;
        }

        if ($deepest === null) {
            $result['unmatched'] = array_values(array_unique([...$notFound, ...array_values(array_filter(RegionModel::LEVELS, static fn (string $k, int $l): bool => $texts[$l] !== '', ARRAY_FILTER_USE_BOTH))]));

            return $result;
        }

        $chain = ($this->regions ?? model(RegionModel::class))->chain($deepest);

        if ($chain === null) {
            return $result;
        }

        foreach ($chain as $level => $row) {
            $result['codes'][RegionModel::LEVELS[$level]] = $row['code'];
            $result['names'][RegionModel::LEVELS[$level]] = RegionModel::displayName($row['name'], $level);
        }

        $result['level'] = (int) max(array_keys($chain));

        // Levels with text below the matched one are reported as not matched.
        foreach (RegionModel::LEVELS as $level => $key) {
            if ($level > $result['level'] && $texts[$level] !== '' && ! in_array($key, $result['unmatched'], true)) {
                $result['unmatched'][] = $key;
            }
        }

        return $result;
    }

    /**
     * Cleaned text of one level: lowercase, single spaces, no leading "Kab."/"Desa" words.
     * For regencies, the kind ("kabupaten"/"kota") comes back separately. The third value is
     * the cleaned text before the leading word was removed (some official names start with
     * such a word, e.g. the district "Kota Kudus").
     *
     * @return array{0: string, 1: string|null, 2: string} [name, kind, name with the leading word]
     */
    public static function normalise(int $level, string $text): array
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['’', '`', '‘'], "'", $text);
        $text = (string) preg_replace('/[\s_]+/u', ' ', $text);
        $text = trim((string) preg_replace('/\s*[,(].*\z/u', '', $text));
        $raw  = $text;
        $kind = null;

        $prefixes = match ($level) {
            1       => '(?:provinsi|propinsi|prov\.?|prop\.?)',
            2       => '(?:kabupaten|kab\.?|kotamadya|kodya|kota|kot\.?)',
            3       => '(?:kecamatan|kec\.?|kec)',
            default => '(?:desa|ds\.?|kelurahan|kel\.?)',
        };

        if (preg_match('/\A' . $prefixes . '(?:\s+|(?<=\.))(.+)\z/u', $text, $m) === 1) {
            if ($level === 2) {
                $word = rtrim(strtok($text, ' .') ?: '', '.');
                $kind = in_array($word, ['kota', 'kotamadya', 'kodya', 'kot'], true) ? 'kota' : 'kabupaten';
            }

            $text = trim($m[1]);
        }

        if ($level === 1) {
            $text = self::PROVINCE_ALIASES[$text] ?? self::PROVINCE_ALIASES[str_replace('.', '', $text)] ?? $text;
        }

        return [$text, $kind, $raw];
    }

    /**
     * Letters and digits only: "getas pejaten" and "getaspejaten" compare equal.
     */
    public static function compact(string $text): string
    {
        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($text));
    }

    /**
     * @param list<array{code: string, name: string, level: int}>|null $parents
     *
     * @return list<array{code: string, name: string, level: int}>
     */
    private function find(int $level, string $text, ?array $parents, bool $similar): array
    {
        [$name, $kind, $raw] = self::normalise($level, $text);

        if ($name === '') {
            return [];
        }

        $found = $this->findAs($level, $name, $kind, $raw, $parents, $similar);

        // "Kota Kudus" typed as the regency of Kab. Kudus: try again without the kind.
        return $found === [] && $kind !== null ? $this->findAs($level, $name, null, $raw, $parents, $similar) : $found;
    }

    /**
     * @param list<array{code: string, name: string, level: int}>|null $parents
     *
     * @return list<array{code: string, name: string, level: int}>
     */
    private function findAs(int $level, string $name, ?string $kind, string $raw, ?array $parents, bool $similar): array
    {
        $pool = $parents === null ? $this->byName($level, $name, $kind, $raw) : $this->childrenOf($parents, $level);

        // Regency names: "Kabupaten X" / "Kota X"; without a kind both count.
        $bare = static function (array $row) use ($level): array {
            $n = mb_strtolower($row['name']);
            $k = null;

            if ($level === 2 && preg_match('/\A(kabupaten|kota)\s+(.+)\z/u', $n, $m) === 1) {
                [$k, $n] = [$m[1], $m[2]];
            }

            return [$n, $k];
        };

        $exact   = [];
        $compact = [];
        $target  = self::compact($name);

        foreach ($pool as $row) {
            [$n, $k] = $bare($row);

            if ($kind !== null && $k !== null && $k !== $kind) {
                continue;
            }

            if ($n === $name || mb_strtolower($row['name']) === $raw) {
                $exact[] = $row;
            } elseif (self::compact($n) === $target) {
                $compact[] = $row;
            }
        }

        // A bare regency name that fits both "Kabupaten X" and "Kota X": the regency is the usual meaning.
        if ($level === 2 && $kind === null && count($exact) === 2) {
            $kabupaten = array_values(array_filter($exact, static fn (array $r): bool => str_starts_with($r['name'], 'Kabupaten ')));
            $exact     = count($kabupaten) === 1 ? $kabupaten : $exact;
        }

        if ($exact !== []) {
            return $exact;
        }

        if ($compact !== []) {
            return $compact;
        }

        if (! $similar || $parents === null || count(array_unique(array_map(static fn (array $r): ?string => RegionModel::parentOf($r['code']), $pool))) > self::MAX_PARENTS_FOR_SIMILAR) {
            return [];
        }

        // One or two letters off, inside the known parent only; a tie means no answer.
        $best     = [];
        $bestCost = PHP_INT_MAX;
        $allowed  = mb_strlen($target) <= 5 ? 1 : 2;

        foreach ($pool as $row) {
            [$n, $k] = $bare($row);

            if ($kind !== null && $k !== null && $k !== $kind) {
                continue;
            }

            $candidate = self::compact($n);

            if (abs(strlen($candidate) - strlen($target)) > $allowed) {
                continue;
            }

            $cost = levenshtein($candidate, $target);

            if ($cost <= $allowed && $cost < $bestCost) {
                $best     = [$row];
                $bestCost = $cost;
            } elseif ($cost === $bestCost) {
                $best[] = $row;
            }
        }

        return count($best) === 1 ? $best : [];
    }

    /**
     * Regions of a level whose name equals $name (or "Kabupaten $name"/"Kota $name"), nationwide.
     * Also finds names that only differ in spaces through the compact form of the first word.
     *
     * @return list<array{code: string, name: string, level: int}>
     */
    private function byName(int $level, string $name, ?string $kind, string $raw): array
    {
        $names = match (true) {
            $level !== 2          => [$name, $raw],
            $kind === 'kota'      => ['kota ' . $name],
            $kind === 'kabupaten' => ['kabupaten ' . $name],
            default               => ['kabupaten ' . $name, 'kota ' . $name],
        };
        $names = array_values(array_unique($names));

        $builder = $this->db->table('ref_wilayah')->select('code, name, level')->where('level', $level)->whereIn('name', $names);
        $rows    = $builder->get()->getResultArray();

        if ($rows === [] && $level !== 1) {
            // "getas pejaten" vs "Getaspejaten": names starting with the same letters, compared compactly.
            $first = mb_substr(self::compact($name), 0, 4);

            if (mb_strlen($first) >= 3) {
                $prefix = $level === 2 ? ($kind === 'kota' ? ['kota ' . $first] : ($kind === 'kabupaten' ? ['kabupaten ' . $first] : ['kabupaten ' . $first, 'kota ' . $first])) : [$first];
                $b      = $this->db->table('ref_wilayah')->select('code, name, level')->where('level', $level)->groupStart();

                foreach ($prefix as $p) {
                    $b->orLike('name', $p, 'after');
                }

                $rows = $b->groupEnd()->limit(500)->get()->getResultArray();
            }
        }

        return array_map(static fn (array $r): array => ['code' => (string) $r['code'], 'name' => (string) $r['name'], 'level' => (int) $r['level']], $rows);
    }

    /**
     * All regions of $level under the given parents.
     *
     * @param list<array{code: string, name: string, level: int}> $parents
     *
     * @return list<array{code: string, name: string, level: int}>
     */
    private function childrenOf(array $parents, int $level): array
    {
        $codes = array_values(array_unique(array_map(static fn (array $p): string => $p['code'], $parents)));

        if ($codes === []) {
            return [];
        }

        // Parents may be more than one level up when a level in between had no text.
        $out  = [];
        $todo = [];

        foreach ($codes as $code) {
            $key = $level . '|' . $code;

            if (isset($this->childCache[$key])) {
                array_push($out, ...$this->childCache[$key]);
            } else {
                $todo[] = $code;
            }
        }

        foreach (array_chunk($todo, 500) as $chunk) {
            $builder = $this->db->table('ref_wilayah')->select('code, name, level')->where('level', $level)->groupStart();

            foreach ($chunk as $code) {
                $builder->orLike('code', $code . '.', 'after');
            }

            $found = [];

            foreach ($builder->groupEnd()->get()->getResultArray() as $r) {
                $row = ['code' => (string) $r['code'], 'name' => (string) $r['name'], 'level' => (int) $r['level']];

                foreach ($chunk as $code) {
                    if (str_starts_with($row['code'], $code . '.')) {
                        $found[$code][] = $row;

                        break;
                    }
                }
            }

            foreach ($chunk as $code) {
                $this->childCache[$level . '|' . $code] = $found[$code] ?? [];
                array_push($out, ...$this->childCache[$level . '|' . $code]);
            }
        }

        return $out;
    }
}
