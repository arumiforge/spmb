<?php

declare(strict_types=1);

namespace Modules\Referensi\Libraries;

use CodeIgniter\Database\BaseConnection;
use Modules\Referensi\Models\SchoolModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use RuntimeException;
use Throwable;

/**
 * Reads school rows from a CSV/XLSX file or from the Dapodik importer and
 * upserts them into ref_sekolah: by NPSN when there is one, otherwise by
 * name + district + regency. Never deletes and never changes is_active
 * (deactivating is a manual decision). Rows are written in bulk without a
 * per-row audit entry; the caller logs one `schools_imported` event.
 */
class SchoolImporter
{
    public const MAX_ROWS = 25000;

    /**
     * Header aliases (lower case, single spaces) → column.
     *
     * @var array<string, string>
     */
    public const HEADERS = [
        'npsn'              => 'npsn',
        'nama'              => 'name',
        'nama sekolah'      => 'name',
        'sekolah'           => 'name',
        'name'              => 'name',
        'jenjang'           => 'level',
        'bentuk pendidikan' => 'level',
        'level'             => 'level',
        'status'            => 'ownership',
        'status sekolah'    => 'ownership',
        'provinsi'          => 'province',
        'kabupaten'         => 'regency',
        'kabupaten/kota'    => 'regency',
        'kab/kota'          => 'regency',
        'kota/kabupaten'    => 'regency',
        'kecamatan'         => 'district',
        'desa'              => 'village',
        'kelurahan'         => 'village',
        'desa/kelurahan'    => 'village',
        'desa kelurahan'    => 'village',
        'kabupaten kota'    => 'regency',
        'alamat'            => 'address',
        'alamat jalan'      => 'address',
        'kota'              => 'regency',
        'kab'               => 'regency',
        'kabupaten / kota'  => 'regency',
        'kelurahan/desa'    => 'village',
        'kelurahan desa'    => 'village',
        'kec'               => 'district',
        'prov'              => 'province',
        'village'           => 'village',
        'district'          => 'district',
        'regency'           => 'regency',
        'province'          => 'province',
        'address'           => 'address',
        'ownership'         => 'ownership',
    ];

    /**
     * Errors kept in a report (the count of failed rows is always complete).
     */
    public const MAX_ERRORS = 1000;

    /**
     * Columns compared and written.
     *
     * @var list<string>
     */
    private const FIELDS = ['npsn', 'name', 'level', 'ownership', 'province', 'regency', 'district', 'village', 'address'];

    private const MAX_LENGTH = ['name' => 150, 'province' => 60, 'regency' => 60, 'district' => 60, 'village' => 60, 'address' => 255];

    private BaseConnection $db;

    /**
     * Existing rows by NPSN and by name key, filled on first use.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $byNpsn = null;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $byKey = [];

    private int $fakeId = 0;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Reads the first sheet of a CSV or XLSX file into rows keyed by column
     * (npsn, name, level, ...). Keys are the spreadsheet row numbers.
     *
     * @return array<int, array<string, string>>
     *
     * @throws RuntimeException with a friendly Indonesian message
     */
    public function readFile(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        if (! in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
            throw new RuntimeException(lang('Referensi.importBadType'));
        }

        try {
            if ($extension === 'xlsx') {
                $reader = IOFactory::createReader('Xlsx');
            } else {
                $reader = new Csv();
                $reader->setInputEncoding(Csv::GUESS_ENCODING);
                $reader->setDelimiter(null);
            }

            $reader->setReadDataOnly(true);
            $data = $reader->load($path)->getActiveSheet()->toArray(null, false, false, false);
        } catch (Throwable $e) {
            log_message('warning', 'School import could not read the file: ' . $e->getMessage());

            throw new RuntimeException(lang('Referensi.importUnreadable'), 0, $e);
        }

        return $this->mapRows($data);
    }

    /**
     * Reads a JSON school list for the command line (no row limit): an array of
     * objects, {"data": [...]}, or one object per line (NDJSON); .gz files are
     * unpacked. Keys use the same aliases as spreadsheet headers (npsn,
     * nama_sekolah, desa, kecamatan, kabupaten_kota, provinsi, jenjang, status, ...).
     * When no level is given it is taken from the name (SD…, MI…, D-057).
     *
     * @return iterable<int, array<string, string>> Row number (1-based) => columns
     *
     * @throws RuntimeException
     */
    public function readJson(string $path): iterable
    {
        $raw = (string) file_get_contents($path);

        if (str_ends_with(strtolower($path), '.gz')) {
            $raw = (string) gzdecode($raw);
        }

        $raw  = ltrim($raw, "\xEF\xBB\xBF \t\r\n");
        $data = json_decode($raw, true);

        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        if (! is_array($data) || ! array_is_list($data)) {
            // One JSON object per line.
            $data = [];

            foreach (preg_split('/\R/', $raw) ?: [] as $line) {
                $line = trim($line);

                if ($line !== '') {
                    $data[] = json_decode($line, true);
                }
            }
        }

        $raw = '';

        if ($data === [] || ! is_array($data[0] ?? null)) {
            throw new RuntimeException(lang('Referensi.importUnreadable'));
        }

        foreach ($data as $i => $object) {
            if (! is_array($object)) {
                continue;
            }

            $row = [];

            foreach ($object as $key => $value) {
                $column = self::HEADERS[self::headerKey((string) $key)] ?? null;

                if ($column !== null && ! isset($row[$column]) && (is_scalar($value) || $value === null)) {
                    $row[$column] = self::cell($value);
                }
            }

            yield $i + 1 => $row;
        }
    }

    /**
     * "Nama_Sekolah" / "Kab. Kota" / "nama sekolah" → "nama sekolah".
     */
    private static function headerKey(string $title): string
    {
        $key = trim(preg_replace('/[\s_]+/u', ' ', mb_strtolower(str_replace('.', '', $title))) ?? '');

        return preg_replace('/^\x{FEFF}/u', '', $key) ?? $key;
    }

    /**
     * Level from the school name when the file has none: SD…, MI…, SDLB…, SLB….
     */
    public static function levelFromName(string $name): ?string
    {
        $name = strtoupper(trim($name));

        return match (true) {
            preg_match('/\A(SDLB|SD LB|SD-LB)\b/', $name) === 1                        => 'SDLB',
            preg_match('/\A(SLB)\b/', $name) === 1                                     => 'SLB',
            preg_match('/\A(SD|SDN|SDS|SDIT|SD IT|SDI|SEKOLAH DASAR)\b/', $name) === 1 => 'SD',
            preg_match('/\A(MI|MIN|MIS|MIT|MI\.|MADRASAH IBTIDAIYAH)\b/', $name) === 1 => 'MI',
            default                                                                    => null,
        };
    }

    /**
     * @param list<list<mixed>> $data First row is the header
     *
     * @return array<int, array<string, string>>
     */
    public function mapRows(array $data): array
    {
        $header  = array_shift($data) ?? [];
        $columns = [];

        foreach ($header as $index => $title) {
            $key = self::headerKey((string) $title);

            if (isset(self::HEADERS[$key]) && ! in_array(self::HEADERS[$key], $columns, true)) {
                $columns[$index] = self::HEADERS[$key];
            }
        }

        if (! in_array('name', $columns, true) || ! in_array('level', $columns, true)) {
            throw new RuntimeException(lang('Referensi.importBadHeader'));
        }

        if (count($data) > self::MAX_ROWS) {
            throw new RuntimeException(lang('Referensi.importTooMany', [number_format(self::MAX_ROWS, 0, ',', '.')]));
        }

        $rows = [];

        foreach ($data as $i => $cells) {
            $row = [];

            foreach ($columns as $index => $column) {
                $row[$column] = self::cell($cells[$index] ?? null);
            }

            if (implode('', $row) === '') {
                continue; // empty line
            }

            $rows[$i + 2] = $row;
        }

        return $rows;
    }

    /**
     * Validates and upserts rows.
     *
     * @param iterable<int, array<string, mixed>> $rows Row number => columns
     *
     * @return array{created: int, updated: int, unchanged: int, failed: int, errors: list<array{row: int, name: string, message: string}>}
     */
    public function import(iterable $rows, string $source, bool $dryRun = false): array
    {
        $this->loadExisting();

        $report = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0, 'errors' => []];
        $now    = date('Y-m-d H:i:s');
        $count  = 0;

        if (! $dryRun) {
            $this->db->transStart();
        }

        foreach ($rows as $number => $raw) {
            $row   = $this->clean($raw);
            $error = $this->validate($row);

            if ($error !== null) {
                $report['failed']++;

                if (count($report['errors']) < self::MAX_ERRORS) {
                    $report['errors'][] = ['row' => (int) $number, 'name' => (string) ($raw['name'] ?? ''), 'message' => $error];
                }

                continue;
            }

            $existing = $this->find($row);

            if ($existing === null) {
                $row['source']     = $source;
                $row['is_active']  = 1;
                $row['created_at'] = $now;
                $row['updated_at'] = $now;

                if ($dryRun) {
                    $row['id'] = --$this->fakeId;
                } else {
                    $this->db->table('ref_sekolah')->insert($row);
                    $row['id'] = (int) $this->db->insertID();
                }

                $this->remember($row);
                $report['created']++;
            } else {
                $changes = [];

                foreach (self::FIELDS as $field) {
                    // Empty cells never wipe data that is already there.
                    if (($row[$field] ?? null) !== null && (string) $row[$field] !== (string) ($existing[$field] ?? '')) {
                        $changes[$field] = $row[$field];
                    }
                }

                if ($changes === []) {
                    $report['unchanged']++;

                    continue;
                }

                $changes['source']     = $source;
                $changes['updated_at'] = $now;

                if (! $dryRun) {
                    $this->db->table('ref_sekolah')->where('id', $existing['id'])->update($changes);
                }

                $this->forget($existing);
                $this->remember(array_merge($existing, $changes));
                $report['updated']++;
            }

            if (! $dryRun && ++$count % 500 === 0) {
                $this->db->transComplete();
                $this->db->transStart();
            }
        }

        if (! $dryRun) {
            $this->db->transComplete();
        }

        return $report;
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, string|null>
     */
    private function clean(array $raw): array
    {
        $row = [];

        foreach (self::FIELDS as $field) {
            $value       = array_key_exists($field, $raw) ? self::cell($raw[$field]) : '';
            $row[$field] = $value === '' ? null : $value;
        }

        if ($row['npsn'] !== null) {
            $row['npsn'] = preg_replace('/\D/', '', $row['npsn']);
        }

        if ($row['level'] === null && $row['name'] !== null) {
            $row['level'] = self::levelFromName($row['name']);
        }

        if ($row['level'] !== null) {
            $level = strtoupper(str_replace(['.', ' '], '', $row['level']));
            $level = in_array($level, ['MIS', 'MIN'], true) ? 'MI' : $level;
            // "Sekolah Dasar", "Madrasah Ibtidaiyah", ...
            $row['level'] = in_array($level, SchoolModel::LEVELS, true) ? $level : (self::levelFromName($row['level']) ?? $level);
        }

        if ($row['ownership'] !== null) {
            $own              = mb_strtolower($row['ownership']);
            $row['ownership'] = match (true) {
                $own === 'n' || str_starts_with($own, 'negeri') => 'negeri',
                $own === 's' || str_starts_with($own, 'swasta') => 'swasta',
                default                                         => $own,
            };
        }

        /** @var array<string, string|null> */
        return SchoolModel::normalise($row);
    }

    /**
     * @param array<string, string|null> $row
     */
    private function validate(array $row): ?string
    {
        if ($row['name'] === null || $row['name'] === '') {
            return lang('Referensi.rowNoName');
        }

        if ($row['level'] === null || ! in_array($row['level'], SchoolModel::LEVELS, true)) {
            return lang('Referensi.rowBadLevel', [implode(', ', SchoolModel::LEVELS)]);
        }

        if ($row['npsn'] !== null && strlen($row['npsn']) !== 8) {
            return lang('Referensi.rowBadNpsn');
        }

        if ($row['ownership'] !== null && ! in_array($row['ownership'], ['negeri', 'swasta'], true)) {
            return lang('Referensi.rowBadOwnership');
        }

        foreach (self::MAX_LENGTH as $field => $max) {
            if ($row[$field] !== null && mb_strlen($row[$field]) > $max) {
                return lang('Referensi.rowTooLong', [lang('Referensi.field.' . $field), $max]);
            }
        }

        return null;
    }

    private function loadExisting(): void
    {
        if ($this->byNpsn !== null) {
            return;
        }

        $this->byNpsn = [];
        $this->byKey  = [];

        $rows = $this->db->table('ref_sekolah')->select('id, ' . implode(', ', self::FIELDS))->get()->getResultArray();

        foreach ($rows as $row) {
            $this->remember($row);
        }
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>|null
     */
    private function find(array $row): ?array
    {
        if ($row['npsn'] !== null && isset($this->byNpsn[$row['npsn']])) {
            return $this->byNpsn[$row['npsn']];
        }

        $existing = $this->byKey[self::key($row)] ?? null;

        // With an NPSN, a name match only counts for a school entered without one
        // (e.g. typed by hand before the import); never for another NPSN.
        if ($existing !== null && $row['npsn'] !== null && ($existing['npsn'] ?? null) !== null) {
            return null;
        }

        return $existing;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function remember(array $row): void
    {
        if (($row['npsn'] ?? null) !== null && $row['npsn'] !== '') {
            $this->byNpsn[(string) $row['npsn']] = $row;
        }

        $this->byKey[self::key($row)] = $row;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function forget(array $row): void
    {
        unset($this->byKey[self::key($row)]);

        if (($row['npsn'] ?? null) !== null) {
            unset($this->byNpsn[(string) $row['npsn']]);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function key(array $row): string
    {
        return mb_strtolower(($row['name'] ?? '') . '|' . ($row['district'] ?? '') . '|' . ($row['regency'] ?? ''));
    }

    private static function cell(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value) {
            $value = (string) (int) $value;
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }
}
