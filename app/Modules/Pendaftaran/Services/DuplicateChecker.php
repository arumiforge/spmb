<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Services;

use Transliterator;

/**
 * Finds applicants that may be the same child (FR-REG-05, D-032): same NIK,
 * same NISN, or the same normalised name and date of birth, within one
 * academic year. Deleted applicants are ignored.
 *
 * Results are for staff only: public pages never show them.
 */
class DuplicateChecker
{
    /**
     * Normalised name for comparisons: lower case, no accents, letters and
     * single spaces only ("  Siti  'Aisyah " → "siti aisyah").
     */
    public static function nameKey(string $name): string
    {
        static $transliterator = null;
        $transliterator ??= Transliterator::create('Any-Latin; Latin-ASCII; Lower()');

        $name = $transliterator instanceof Transliterator ? (string) $transliterator->transliterate($name) : mb_strtolower($name);
        $name = preg_replace('/[^a-z ]+/', '', $name) ?? '';

        return mb_substr(trim(preg_replace('/\s+/', ' ', $name) ?? ''), 0, 100);
    }

    /**
     * Possible duplicates of $data (keys nik, nisn, full_name or name_key,
     * birth_date, academic_year), each row with 'reasons' => list of nik|nisn|name.
     *
     * @param array<string, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    public function find(array $data, ?int $exceptId = null): array
    {
        $nik     = trim((string) ($data['nik'] ?? ''));
        $nisn    = trim((string) ($data['nisn'] ?? ''));
        $nameKey = (string) ($data['name_key'] ?? self::nameKey((string) ($data['full_name'] ?? '')));
        $birth   = trim((string) ($data['birth_date'] ?? ''));

        if ($nik === '' && $nisn === '' && ($nameKey === '' || $birth === '')) {
            return [];
        }

        $builder = db_connect()->table('applicants')
            ->select('id, registration_number, full_name, birth_date, nik, nisn, name_key, status, method, registered_at')
            ->where('deleted_at', null)
            ->where('academic_year', (string) ($data['academic_year'] ?? setting('Pendaftaran.academicYear')))
            ->groupStart();

        $any = false;

        if ($nik !== '') {
            $builder->where('nik', $nik);
            $any = true;
        }

        if ($nisn !== '') {
            $any ? $builder->orWhere('nisn', $nisn) : $builder->where('nisn', $nisn);
            $any = true;
        }

        if ($nameKey !== '' && $birth !== '') {
            $any ? $builder->orGroupStart() : $builder->groupStart();
            $builder->where('name_key', $nameKey)->where('birth_date', $birth)->groupEnd();
        }

        $builder->groupEnd();

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        $rows = $builder->orderBy('registered_at', 'ASC')->limit(20)->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['reasons'] = array_values(array_filter([
                $nik !== '' && $row['nik'] === $nik ? 'nik' : null,
                $nisn !== '' && $row['nisn'] === $nisn ? 'nisn' : null,
                $row['name_key'] === $nameKey && $row['birth_date'] === $birth ? 'name' : null,
            ]));
        }

        return $rows;
    }
}
