<?php

declare(strict_types=1);

namespace Modules\Referensi\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Previous schools (table ref_sekolah). Owner: work package A (Referensi).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 *
 * Stored conventions: `province` without the "Prov." prefix ("Jawa Tengah"),
 * `regency` as "Kab. Kudus" / "Kota Semarang", `district` without "Kec.".
 */
class SchoolModel extends Model
{
    use Auditable;

    /**
     * School levels the list accepts.
     *
     * @var list<string>
     */
    public const LEVELS = ['SD', 'MI', 'SDLB', 'SLB'];

    /**
     * @var list<string>
     */
    public const SOURCES = ['dapodik', 'emis', 'import', 'manual'];

    public const SEARCH_LIMIT = 20;

    protected $table          = 'ref_sekolah';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'npsn', 'name', 'level', 'ownership', 'province', 'regency', 'district', 'village', 'address', 'source', 'is_active',
    ];
    protected $beforeInsert = ['normaliseData'];
    protected $beforeUpdate = ['normaliseData'];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();

        $rules = [
            'id'        => 'permit_empty|is_natural_no_zero',
            'npsn'      => 'permit_empty|exact_length[8]|numeric|is_unique[ref_sekolah.npsn,id,{id}]',
            'name'      => 'required|max_length[150]',
            'level'     => 'required|in_list[' . implode(',', self::LEVELS) . ']',
            'ownership' => 'permit_empty|in_list[negeri,swasta]',
            'province'  => 'permit_empty|max_length[60]',
            'regency'   => 'permit_empty|max_length[60]',
            'district'  => 'permit_empty|max_length[60]',
            'village'   => 'permit_empty|max_length[60]',
            'address'   => 'permit_empty|max_length[255]',
            'source'    => 'permit_empty|in_list[' . implode(',', self::SOURCES) . ']',
            'is_active' => 'permit_empty|in_list[0,1]',
        ];

        foreach ($rules as $field => $rule) {
            $label                         = lang('Referensi.field.' . $field);
            $this->validationRules[$field] = ['label' => str_starts_with($label, 'Referensi.') ? $field : $label, 'rules' => $rule];
        }

        $this->validationMessages = [
            'npsn'  => ['exact_length' => lang('Referensi.npsnFormat'), 'numeric' => lang('Referensi.npsnFormat'), 'is_unique' => lang('Referensi.npsnTaken')],
            'level' => ['in_list' => lang('Referensi.rowBadLevel', [implode(', ', self::LEVELS)])],
        ];
    }

    /**
     * Friendly one-line label, e.g. "SD NEGERI 1 KUDUS — Kec. Kota Kudus, Kab. Kudus".
     * Null when the school does not exist.
     */
    public function label(int $id): ?string
    {
        $row = $this->find($id);

        return $row === null ? null : self::formatLabel($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function formatLabel(array $row): string
    {
        $place = array_filter([
            ($row['district'] ?? '') !== '' && $row['district'] !== null ? 'Kec. ' . $row['district'] : '',
            (string) ($row['regency'] ?? ''),
        ], static fn (string $part): bool => $part !== '');

        return (string) $row['name'] . ($place === [] ? '' : ' — ' . implode(', ', $place));
    }

    /**
     * Active schools matching a typed text, schools of the configured provinces
     * first. Every word must start a word of the name, district or regency, or
     * the start of the NPSN. Fast on a small server: indexed prefix match first,
     * at most SEARCH_LIMIT rows.
     *
     * @return list<array{id: int, npsn: string|null, name: string, level: string, district: string|null, regency: string|null, label: string}>
     */
    public function search(string $text, int $limit = self::SEARCH_LIMIT): array
    {
        $words = self::searchWords($text);

        if ($words === []) {
            return [];
        }

        $builder = $this->db->table($this->table)
            ->select('id, npsn, name, level, district, regency, province')
            ->where('is_active', 1);

        foreach ($words as $word) {
            $builder->groupStart()
                ->like('name', $word, 'after')
                ->orLike('name', ' ' . $word, 'both')
                ->orLike('district', $word, 'after')
                ->orLike('district', ' ' . $word, 'both')
                ->orLike('regency', ' ' . $word, 'both');

            if (ctype_digit($word)) {
                $builder->orLike('npsn', $word, 'after');
            }

            $builder->groupEnd();
        }

        $provinces = array_values(array_filter(array_map('strval', (array) setting('Referensi.provinces'))));

        if ($provinces !== []) {
            $list = implode(',', array_map(fn (string $p): string => (string) $this->db->escape($p), $provinces));
            $builder->orderBy('CASE WHEN province IN (' . $list . ') THEN 0 ELSE 1 END', '', false);
        }

        $phrase = $this->db->escapeLikeString(implode(' ', $words));
        // Words hold only letters and digits (searchWords), so they are safe inside these LIKE strings.
        $inName = array_map(function (string $word): string {
            $like = $this->db->escapeLikeString($word);

            return "(name LIKE '" . $like . "%' ESCAPE '!' OR name LIKE '% " . $like . "%' ESCAPE '!')";
        }, $words);

        $builder->orderBy("CASE WHEN name LIKE '" . $phrase . "%' ESCAPE '!' THEN 0 ELSE 1 END", '', false)
            ->orderBy('(' . implode(' + ', $inName) . ')', 'DESC', false)
            ->orderBy('name', 'ASC')
            ->limit(max(1, min($limit, self::SEARCH_LIMIT)));

        $rows = [];

        foreach ($builder->get()->getResultArray() as $row) {
            $rows[] = [
                'id'       => (int) $row['id'],
                'npsn'     => $row['npsn'],
                'name'     => $row['name'],
                'level'    => $row['level'],
                'district' => $row['district'],
                'regency'  => $row['regency'],
                'label'    => self::formatLabel($row),
            ];
        }

        return $rows;
    }

    /**
     * Lower-case search words with common abbreviations expanded
     * ("sdn 2 jati" → sd, negeri, 2, jati). Empty when the text is too short.
     *
     * @return list<string>
     */
    public static function searchWords(string $text): array
    {
        $text = mb_strtolower(trim($text));

        if (mb_strlen($text) < 2) {
            return [];
        }

        $expand = ['sdn' => ['sd', 'negeri'], 'min' => ['mi', 'negeri'], 'mis' => ['mi'], 'kab' => [], 'kec' => [], 'kota' => ['kota']];
        $words  = [];

        foreach (preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            foreach ($expand[$word] ?? [$word] as $part) {
                if (! in_array($part, $words, true)) {
                    $words[] = $part;
                }
            }
        }

        return array_slice($words, 0, 6);
    }

    /**
     * Turns the two fields posted by the school picker into what an applicant
     * row stores: a list entry, or the free text when the school is not in the
     * list (admins map it later on /admin/referensi/sekolah-lain).
     *
     * @return array{previous_school_id: int|null, previous_school_name: string|null}
     */
    public function fromPicker(mixed $id, mixed $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $name) ?? '');
        $id   = is_numeric($id) ? (int) $id : 0;

        if ($id > 0) {
            $school = $this->where('is_active', 1)->find($id);

            if ($school !== null && ($name === '' || in_array(mb_strtolower($name), [mb_strtolower($school['name']), mb_strtolower(self::formatLabel($school))], true))) {
                return ['previous_school_id' => (int) $school['id'], 'previous_school_name' => null];
            }
        }

        if ($name === '') {
            return ['previous_school_id' => null, 'previous_school_name' => null];
        }

        // The exact name of exactly one active school counts as choosing it.
        $matches = $this->where('is_active', 1)->where('name', self::normaliseName($name))->findAll(2);

        if (count($matches) === 1) {
            return ['previous_school_id' => (int) $matches[0]['id'], 'previous_school_name' => null];
        }

        return ['previous_school_id' => null, 'previous_school_name' => mb_substr($name, 0, 150)];
    }

    /**
     * Regencies present in the list, for the filter.
     *
     * @return list<string>
     */
    public function regencies(): array
    {
        $rows = $this->db->table($this->table)->distinct()->select('regency')
            ->where('regency IS NOT NULL')->where('regency !=', '')
            ->orderBy('regency', 'ASC')->get()->getResultArray();

        return array_column($rows, 'regency');
    }

    /**
     * Cleans one row the way it is stored (see the class comment).
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    public static function normalise(array $row): array
    {
        foreach (['npsn', 'name', 'level', 'ownership', 'province', 'regency', 'district', 'village', 'address'] as $field) {
            if (! array_key_exists($field, $row)) {
                continue;
            }

            $value       = $row[$field] === null ? '' : trim(preg_replace('/\s+/u', ' ', (string) $row[$field]) ?? '');
            $row[$field] = $value === '' && $field !== 'name' && $field !== 'level' ? null : $value;
        }

        if (isset($row['name'])) {
            $row['name'] = self::normaliseName($row['name']);
        }

        if (isset($row['level'])) {
            $row['level'] = strtoupper($row['level']);
        }

        if (isset($row['ownership'])) {
            $row['ownership'] = mb_strtolower($row['ownership']);
        }

        if (isset($row['province'])) {
            $row['province'] = preg_replace('/^(prov\.?|provinsi)\s+/iu', '', $row['province']);
        }

        if (isset($row['regency'])) {
            $row['regency'] = preg_replace('/^(kabupaten|kab\.?)\s+/iu', 'Kab. ', $row['regency']);
        }

        if (isset($row['district'])) {
            $row['district'] = preg_replace('/^(kecamatan|kec\.?)\s+/iu', '', $row['district']);
        }

        return $row;
    }

    /**
     * "SDN 1 Jati" → "SD Negeri 1 Jati", "MIS NU ..." → "MI NU ..."; keeps the case style.
     */
    public static function normaliseName(string $name): string
    {
        $name  = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $upper = $name === mb_strtoupper($name);
        $map   = [
            '/^(SDN|SD\s*N)\.?(?=\s|\d|$)\s*/iu' => $upper ? 'SD NEGERI ' : 'SD Negeri ',
            '/^MIN\.?(?=\s|\d|$)\s*/iu'          => $upper ? 'MI NEGERI ' : 'MI Negeri ',
            '/^MIS\.?(?=\s|$)\s*/iu'             => 'MI ',
        ];

        foreach ($map as $pattern => $replacement) {
            $name = preg_replace($pattern, $replacement, $name) ?? $name;
        }

        return trim($name);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function normaliseData(array $data): array
    {
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = self::normalise($data['data']);
        }

        return $data;
    }
}
