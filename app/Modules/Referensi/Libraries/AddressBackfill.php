<?php

declare(strict_types=1);

namespace Modules\Referensi\Libraries;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use Modules\Pendaftaran\Models\ApplicantModel;

/**
 * Matches the typed addresses of earlier applicants to the national region
 * list (D-111), once after the list arrives: `php spark referensi:cocokkan-alamat`
 * or Sekolah Asal → Alamat pendaftar.
 *
 * Applicants without a village code but with some address text are matched
 * with RegionMatcher (same name, then without spaces, then one or two letters
 * off inside the known district/regency). Matched levels get their code and
 * official display name; levels that could not be matched keep the text as
 * typed and are reported. Writes go through ApplicantModel, so every change is
 * audited. Safe to run again: rows already complete are skipped.
 */
class AddressBackfill
{
    /**
     * Result rows kept for the report (counts cover every applicant).
     */
    public const REPORT_ROWS = 300;

    private const FIELDS = ['province', 'regency', 'district', 'village'];

    private readonly BaseConnection $db;

    public function __construct(?BaseConnection $db = null, private readonly ?RegionMatcher $matcher = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Applicants whose address still has no village code but some text to match.
     */
    public function pendingCount(): int
    {
        if (! $this->db->tableExists('applicants') || ! $this->db->fieldExists('village_code', 'applicants')) {
            return 0;
        }

        return $this->pending()->countAllResults();
    }

    /**
     * @return array{checked: int, full: int, partial: int, none: int, changed: int, rows: list<array{id: int, number: string, name: string, typed: string, result: string, status: string, unmatched: list<string>}>}
     *                                                                                                                                                                                                                 status: full (every typed level matched), partial, none
     */
    public function run(bool $dryRun = true, bool $similar = true): array
    {
        $report = ['checked' => 0, 'full' => 0, 'partial' => 0, 'none' => 0, 'changed' => 0, 'rows' => []];

        if ($this->pendingCount() === 0) {
            return $report;
        }

        $matcher    = $this->matcher ?? new RegionMatcher($this->db);
        $applicants = model(ApplicantModel::class);
        $lastId     = 0;

        do {
            $batch = $this->pending()
                ->select('id, registration_number, full_name, province, regency, district, village, province_code, regency_code, district_code, village_code')
                ->where('id >', $lastId)->orderBy('id', 'ASC')->limit(200)->get()->getResultArray();

            foreach ($batch as $row) {
                $lastId = (int) $row['id'];
                $report['checked']++;

                $match  = $matcher->match(array_intersect_key($row, array_flip(self::FIELDS)), $similar);
                $status = $match['level'] === 0 ? 'none' : ($match['unmatched'] === [] ? 'full' : 'partial');
                $report[$status]++;

                $changes = [];

                if ($match['level'] > 0) {
                    foreach (RegionInput::CODE_FIELDS as $text => $codeField) {
                        if ($match['codes'][$text] === null) {
                            continue;
                        }

                        $changes[$codeField] = $match['codes'][$text];
                        $changes[$text]      = $match['names'][$text];
                    }

                    $changes = array_filter($changes, static fn ($value, string $key): bool => (string) ($row[$key] ?? '') !== (string) $value, ARRAY_FILTER_USE_BOTH);
                }

                if ($changes !== []) {
                    $report['changed']++;

                    if (! $dryRun) {
                        $applicants->update((int) $row['id'], $changes);
                    }
                }

                if (count($report['rows']) < self::REPORT_ROWS && ($changes !== [] || $status !== 'full')) {
                    $report['rows'][] = [
                        'id'        => (int) $row['id'],
                        'number'    => (string) $row['registration_number'],
                        'name'      => (string) $row['full_name'],
                        'typed'     => self::typed($row),
                        'result'    => $match['level'] === 0 ? '' : self::path($match['names']),
                        'status'    => $status,
                        'unmatched' => $match['unmatched'],
                    ];
                }
            }
        } while (count($batch) === 200);

        if (! $dryRun && $report['changed'] > 0) {
            service('audit')->log('addresses_matched', 'applicants', null, null, [
                'checked' => $report['checked'],
                'changed' => $report['changed'],
                'full'    => $report['full'],
                'partial' => $report['partial'],
                'none'    => $report['none'],
            ], 'Alamat pendaftar dicocokkan dengan daftar wilayah');
        }

        return $report;
    }

    /**
     * "Ds. Ploso, Jati, Kudus" — the address as typed.
     *
     * @param array<string, mixed> $row
     */
    public static function typed(array $row): string
    {
        $parts = [];

        foreach (array_reverse(self::FIELDS) as $field) {
            $value = trim((string) ($row[$field] ?? ''));

            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * @param array<string, string|null> $names
     */
    private static function path(array $names): string
    {
        return implode(', ', array_filter([$names['village'] ?? null, $names['district'] ?? null, $names['regency'] ?? null, $names['province'] ?? null]));
    }

    private function pending(): BaseBuilder
    {
        $builder = $this->db->table('applicants')->where('deleted_at', null)->where('village_code', null)->groupStart();

        foreach (self::FIELDS as $field) {
            $builder->orGroupStart()->where($field . ' IS NOT NULL', null, false)->where('TRIM(' . $field . ") <> ''", null, false)->groupEnd();
        }

        return $builder->groupEnd();
    }
}
