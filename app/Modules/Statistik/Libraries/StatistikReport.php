<?php

declare(strict_types=1);

namespace Modules\Statistik\Libraries;

use CodeIgniter\I18n\Time;
use InvalidArgumentException;

/**
 * Turns the aggregates of StatistikQuery into the sections and cards the page
 * shows and the CSV files contain, so both always hold the same numbers.
 *
 * A card is an array:
 *   id, title, intro?, type (bars | meter | columns | stacked), wide (bool),
 *   labelHeader, noteHeader?, rows (list of {label, count, note?, url?, short?}),
 *   total, ordered (keep the order; no "top 15 + other" in the chart),
 *   switch? {label, options: list of {label, url, active}}, trail? (location breadcrumbs),
 *   and for stacked cards: series (labels) and groups (list of {label, cells: list<int>, count}).
 */
final class StatistikReport
{
    /**
     * Bars shown in a chart before the rest is folded into "Lainnya".
     */
    public const TOP = 15;

    public const AGE_MIN = 11;
    public const AGE_MAX = 15;

    /**
     * Section slugs (also the CSV download names), in page order.
     */
    public const SECTIONS = ['asal', 'keluarga', 'murid', 'pendaftaran', 'perbandingan'];

    private ?array $totals = null;

    public function __construct(
        private readonly StatistikFilter $filter,
        private readonly StatistikQuery $query,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * @return array{total: int, online: int, offline: int, complete: int}
     */
    public function totals(): array
    {
        return $this->totals ??= $this->query->totals();
    }

    /**
     * @return list<array{id: string, title: string, cards: list<array<string, mixed>>}>
     */
    public function sections(): array
    {
        return array_map($this->section(...), self::SECTIONS);
    }

    /**
     * @return array{id: string, title: string, cards: list<array<string, mixed>>}
     */
    public function section(string $id): array
    {
        return match ($id) {
            'asal' => ['id' => 'asal', 'title' => lang('Statistik.groupOrigin'), 'cards' => [
                $this->location(), $this->schools(), $this->schoolLevels(), $this->schoolOwnership(),
            ]],
            'keluarga' => ['id' => 'keluarga', 'title' => lang('Statistik.groupFamily'), 'cards' => [
                $this->textCard('pekerjaan-ayah', 'father_occupation', lang('Statistik.fatherJobTitle'), lang('Statistik.jobHeader')),
                $this->textCard('pekerjaan-ibu', 'mother_occupation', lang('Statistik.motherJobTitle'), lang('Statistik.jobHeader')),
                $this->incomeCard('penghasilan-ayah', 'father_income', lang('Statistik.fatherIncomeTitle')),
                $this->incomeCard('penghasilan-ibu', 'mother_income', lang('Statistik.motherIncomeTitle')),
                $this->codeCard('wali', 'has_guardian', lang('Statistik.guardianTitle'), lang('Statistik.guardianHeader'), (array) lang('Statistik.guardian'), lang('Statistik.guardianIntro')),
            ]],
            'murid' => ['id' => 'murid', 'title' => lang('Statistik.groupStudent'), 'cards' => [
                $this->codeCard('jenis-kelamin', 'gender', lang('Statistik.genderTitle'), lang('Statistik.genderHeader'), (array) lang('Status.gender')),
                $this->textCard('agama', 'religion', lang('Statistik.religionTitle'), lang('Statistik.religionHeader'), 'meter'),
                $this->ages(),
            ]],
            'pendaftaran' => ['id' => 'pendaftaran', 'title' => lang('Statistik.groupRegistration'), 'cards' => [
                $this->timeline(),
                $this->waves(),
                $this->codeCard('cara-daftar', 'method', lang('Statistik.methodTitle'), lang('Statistik.methodHeader'), (array) lang('Status.method')),
                $this->codeCard('berkas', 'document_status', lang('Statistik.documentsTitle'), lang('Statistik.documentsHeader'), (array) lang('Status.documents')),
            ]],
            'perbandingan' => ['id' => 'perbandingan', 'title' => lang('Statistik.groupCross'), 'cards' => [
                $this->crossWave(), $this->crossDistrict(),
            ]],
            default => throw new InvalidArgumentException('Unknown section ' . $id),
        };
    }

    /**
     * Link to the page with the current filters and $changes, jumping to $anchor.
     *
     * @param array<string, string|null> $changes
     */
    public function url(array $changes = [], ?string $anchor = null): string
    {
        return $this->baseUrl . $this->filter->query($changes) . ($anchor === null ? '' : '#' . $anchor);
    }

    // ------------------------------------------------------------------
    // Asal calon murid
    // ------------------------------------------------------------------

    private function location(): array
    {
        $data   = $this->query->location();
        $level  = $data['level'];
        $header = ['regency' => lang('Statistik.levelRegency'), 'district' => lang('Statistik.levelDistrict'), 'village' => lang('Statistik.levelVillage')][$level];
        $rows   = [];

        foreach ($data['rows'] as $row) {
            $url = match (true) {
                $row['key'] === ''    => null,
                $level === 'regency'  => $this->url(['kab' => $row['key'], 'kec' => null], 'tempat-tinggal'),
                $level === 'district' => $this->url(['kab' => $data['regency']['key'], 'kec' => $row['key']], 'tempat-tinggal'),
                default               => null,
            };
            $rows[] = ['label' => $row['label'] === '' ? lang('Statistik.notFilled') : $row['label'], 'count' => $row['count'], 'url' => $url];
        }

        $trail = [['label' => lang('Statistik.locationAll'), 'url' => $level === 'regency' ? null : $this->url(['kab' => null, 'kec' => null], 'tempat-tinggal')]];

        if ($data['regency'] !== null) {
            $trail[] = ['label' => $data['regency']['label'], 'url' => $level === 'village' ? $this->url(['kec' => null], 'tempat-tinggal') : null];
        }

        if ($data['district'] !== null) {
            $trail[] = ['label' => $data['district']['label'], 'url' => null];
        }

        $place = $data['district']['label'] ?? $data['regency']['label'] ?? null;

        return [
            'id'          => 'tempat-tinggal',
            'title'       => lang('Statistik.locationTitle'),
            'intro'       => lang('Statistik.locationIntro'),
            'chartTitle'  => $place === null ? lang('Statistik.locationChart', [mb_strtolower($header)]) : lang('Statistik.locationIn', [mb_strtolower($header), $place]),
            'type'        => 'bars',
            'wide'        => true,
            'labelHeader' => $header,
            'rows'        => $rows,
            'total'       => self::sum($rows),
            'ordered'     => false,
            'trail'       => $trail,
            'hint'        => $level === 'village' ? null : lang('Statistik.openHint'),
        ];
    }

    private function schools(): array
    {
        $mode = $this->filter->schoolMode;
        $rows = [];

        foreach ($this->query->schools($mode) as $row) {
            $label = match (true) {
                $row['key'] === 'lain' => lang('Statistik.schoolOther'),
                $row['key'] === ''     => lang('Statistik.notFilled'),
                $row['label'] === ''   => lang('Statistik.notFilled'),
                default                => $row['label'],
            };
            $note = null;

            if ($mode === 'nama' && $row['key'] !== '') {
                $note = ($row['note'] ?? '') === 'lain' ? lang('Statistik.schoolOtherNote') : (string) ($row['note'] ?? '');
            }

            $rows[] = ['label' => $label, 'count' => $row['count']] + ($mode === 'nama' ? ['note' => $note] : []);
        }

        return [
            'id'          => 'sekolah-asal',
            'title'       => lang('Statistik.schoolTitle'),
            'intro'       => lang('Statistik.schoolIntro'),
            'chartTitle'  => lang('Statistik.schoolChart')[$mode],
            'type'        => 'bars',
            'wide'        => true,
            'labelHeader' => lang('Statistik.schoolHeader')[$mode],
            'noteHeader'  => $mode === 'nama' ? lang('Statistik.schoolPlace') : null,
            'rows'        => $rows,
            'total'       => self::sum($rows),
            'ordered'     => false,
            'switch'      => $this->switch(lang('Statistik.schoolModeLabel'), 'sekolah', (array) lang('Statistik.schoolModes'), $mode, 'sekolah-asal'),
        ];
    }

    private function schoolLevels(): array
    {
        $rows = array_map(static fn (array $r): array => ['label' => match ($r['key']) {
            ''      => lang('Statistik.notFilled'),
            'lain'  => lang('Statistik.schoolOther'),
            '?'     => lang('Statistik.levelUnknown'),
            default => $r['key'],
        }, 'count' => $r['count']], $this->query->schoolLevels());

        return $this->meter('jenjang-sekolah', lang('Statistik.levelTitle'), lang('Statistik.levelHeader'), $rows, lang('Statistik.levelIntro'));
    }

    private function schoolOwnership(): array
    {
        $names = (array) lang('Statistik.ownership');
        $rows  = array_map(static fn (array $r): array => ['label' => match ($r['key']) {
            ''      => lang('Statistik.notFilled'),
            'lain'  => lang('Statistik.schoolOther'),
            '?'     => lang('Statistik.ownershipUnknown'),
            default => $names[$r['key']] ?? ucfirst($r['key']),
        }, 'count' => $r['count']], $this->query->schoolOwnership());

        return $this->meter('status-sekolah', lang('Statistik.ownershipTitle'), lang('Statistik.ownershipHeader'), $rows, lang('Statistik.ownershipIntro'));
    }

    // ------------------------------------------------------------------
    // Family, student
    // ------------------------------------------------------------------

    private function textCard(string $id, string $column, string $title, string $header, string $type = 'bars'): array
    {
        $rows = array_map(static fn (array $r): array => ['label' => $r['key'] === '' ? lang('Statistik.notFilled') : $r['label'], 'count' => $r['count']], $this->query->textCounts($column));

        return $type === 'meter'
            ? $this->meter($id, $title, $header, $rows)
            : ['id' => $id, 'title' => $title, 'type' => 'bars', 'wide' => false, 'labelHeader' => $header, 'rows' => $rows, 'total' => self::sum($rows), 'ordered' => false];
    }

    private function incomeCard(string $id, string $column, string $title): array
    {
        $rows = array_map(static fn (array $r): array => ['label' => $r['key'] === '' ? lang('Statistik.notFilled') : $r['label'], 'count' => $r['count']], $this->query->incomeCounts($column));

        return ['id' => $id, 'title' => $title, 'intro' => lang('Statistik.incomeIntro'), 'type' => 'bars', 'wide' => false, 'labelHeader' => lang('Statistik.incomeHeader'), 'rows' => $rows, 'total' => self::sum($rows), 'ordered' => true];
    }

    /**
     * A small split by code, with every known code listed (also at 0) in the order of $labels.
     *
     * @param array<string, string> $labels code => label
     */
    private function codeCard(string $id, string $column, string $title, string $header, array $labels, ?string $intro = null): array
    {
        $counts = [];

        foreach ($this->query->codeCounts($column) as $row) {
            $counts[$row['key']] = ($counts[$row['key']] ?? 0) + $row['count'];
        }

        $rows = [];

        foreach ($labels as $code => $label) {
            $rows[] = ['label' => $label, 'count' => $counts[(string) $code] ?? 0];
            unset($counts[(string) $code]);
        }

        foreach ($counts as $code => $count) {
            $rows[] = ['label' => $code === '' ? lang('Statistik.notFilled') : (string) $code, 'count' => $count];
        }

        return $this->meter($id, $title, $header, $rows, $intro);
    }

    private function ages(): array
    {
        $buckets = [];

        for ($age = self::AGE_MIN - 1; $age <= self::AGE_MAX; $age++) {
            $buckets[$age] = 0;
        }

        foreach ($this->query->ages() as $age => $count) {
            $buckets[max(self::AGE_MIN - 1, min(self::AGE_MAX, $age))] += $count;
        }

        $rows = [];

        foreach ($buckets as $age => $count) {
            $label = match (true) {
                $age < self::AGE_MIN  => lang('Statistik.ageUnder', [self::AGE_MIN]),
                $age >= self::AGE_MAX => lang('Statistik.ageOver', [self::AGE_MAX]),
                default               => lang('Statistik.ageYears', [$age]),
            };
            $rows[] = ['label' => $label, 'count' => $count];
        }

        return ['id' => 'usia', 'title' => lang('Statistik.ageTitle'), 'intro' => lang('Statistik.ageIntro'), 'type' => 'bars', 'wide' => false, 'labelHeader' => lang('Statistik.ageHeader'), 'rows' => $rows, 'total' => self::sum($rows), 'ordered' => true];
    }

    // ------------------------------------------------------------------
    // Pendaftaran
    // ------------------------------------------------------------------

    /**
     * Per day, or per week when the registrations span more than 60 days (unless chosen).
     */
    public function timeMode(): string
    {
        if ($this->filter->timeMode !== null) {
            return $this->filter->timeMode;
        }

        $span = $this->query->dateSpan();

        return $span !== null && Time::parse($span[0])->difference(Time::parse($span[1]))->getDays() > 60 ? 'minggu' : 'hari';
    }

    private function timeline(): array
    {
        $mode = $this->timeMode();
        $rows = [];

        foreach ($this->query->timeline($mode) as $point) {
            $label = $mode === 'minggu'
                ? tanggal_indo($point['date']) . ' – ' . tanggal_indo(Time::parse($point['date'])->addDays(6)->toDateString())
                : tanggal_indo($point['date']);
            $rows[] = ['label' => $label, 'short' => self::shortDate($point['date']), 'count' => $point['count']];
        }

        return [
            'id'          => 'waktu',
            'title'       => lang('Statistik.timeTitle'),
            'chartTitle'  => lang('Statistik.timeChart')[$mode],
            'type'        => 'columns',
            'wide'        => true,
            'labelHeader' => lang('Statistik.timeHeader')[$mode],
            'rows'        => $rows,
            'total'       => self::sum($rows),
            'ordered'     => true,
            'switch'      => $this->switch(lang('Statistik.timeModeLabel'), 'waktu', (array) lang('Statistik.timeModes'), $mode, 'waktu'),
        ];
    }

    private function waves(): array
    {
        $rows = array_map(static fn (array $r): array => [
            'label' => $r['key'] === '0' || $r['label'] === '' ? lang('Statistik.waveNone') : $r['label'],
            'count' => $r['count'],
        ], $this->query->waves());

        return $this->meter('gelombang', lang('Statistik.waveTitle'), lang('Statistik.waveHeader'), $rows);
    }

    // ------------------------------------------------------------------
    // Perbandingan
    // ------------------------------------------------------------------

    private function crossWave(): array
    {
        $column = $this->filter->incomeParent === 'ibu' ? 'mother_income' : 'father_income';
        $data   = $this->query->incomeByWave($column);
        $names  = array_map(static fn (array $g): string => $g['key'] === '0' || $g['label'] === '' ? lang('Statistik.waveNone') : $g['label'], $data['groups']);

        return $this->stacked('silang-gelombang', lang('Statistik.crossWaveTitle'), lang('Statistik.crossWaveIntro'), lang('Statistik.waveHeader'), $data, $names);
    }

    private function crossDistrict(): array
    {
        $column    = $this->filter->incomeParent === 'ibu' ? 'mother_income' : 'father_income';
        $data      = $this->query->incomeByDistrict($column);
        $regencies = array_unique(array_filter(array_map(static fn (array $g): string => mb_strtolower($g['note'] ?? ''), $data['groups'])));
        $names     = array_map(static function (array $g) use ($regencies): string {
            if ($g['key'] === 'lain') {
                return lang('Statistik.crossOtherDist');
            }

            if ($g['label'] === '') {
                return lang('Statistik.notFilled');
            }

            // Several regencies: say which one, since district names repeat across the country.
            return count($regencies) > 1 && ($g['note'] ?? '') !== '' ? $g['label'] . ', ' . $g['note'] : $g['label'];
        }, $data['groups']);

        return $this->stacked('silang-kecamatan', lang('Statistik.crossDistTitle'), lang('Statistik.crossDistIntro'), lang('Statistik.levelDistrict'), $data, $names);
    }

    /**
     * @param array{groups: list<array<string, mixed>>, income: list<array{key: string, label: string}>, cells: array<string, array<string, int>>} $data
     * @param list<string>                                                                                                                         $names group labels
     */
    private function stacked(string $id, string $title, string $intro, string $header, array $data, array $names): array
    {
        $series = array_map(static fn (array $i): string => $i['key'] === '' ? lang('Statistik.notFilled') : $i['label'], $data['income']);
        $groups = [];

        foreach ($data['groups'] as $n => $group) {
            $cells = [];

            foreach ($data['income'] as $income) {
                $cells[] = (int) ($data['cells'][$group['key']][$income['key']] ?? 0);
            }

            $groups[] = ['label' => $names[$n], 'cells' => $cells, 'count' => (int) $group['count']];
        }

        return [
            'id'          => $id,
            'title'       => $title,
            'intro'       => $intro,
            'type'        => 'stacked',
            'wide'        => true,
            'labelHeader' => $header,
            'series'      => $series,
            'blankLast'   => $data['income'] !== [] && end($data['income'])['key'] === '',
            'groups'      => $groups,
            'rows'        => [],
            'total'       => array_sum(array_column($groups, 'count')),
            'ordered'     => true,
            'switch'      => $this->switch(lang('Statistik.crossIncomeLabel'), 'penghasilan', (array) lang('Statistik.crossIncome'), $this->filter->incomeParent, $id),
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @param list<array{label: string, count: int}> $rows
     */
    private function meter(string $id, string $title, string $header, array $rows, ?string $intro = null): array
    {
        return ['id' => $id, 'title' => $title, 'intro' => $intro, 'type' => 'meter', 'wide' => false, 'labelHeader' => $header, 'rows' => $rows, 'total' => self::sum($rows), 'ordered' => true];
    }

    /**
     * @param array<string, string> $options value => label
     */
    private function switch(string $label, string $param, array $options, string $current, string $anchor): array
    {
        $items = [];

        foreach ($options as $value => $text) {
            $items[] = ['label' => $text, 'url' => $this->url([$param => (string) $value], $anchor), 'active' => (string) $value === $current];
        }

        return ['label' => $label, 'options' => $items];
    }

    /**
     * @param list<array{count: int}> $rows
     */
    private static function sum(array $rows): int
    {
        return (int) array_sum(array_column($rows, 'count'));
    }

    private static function shortDate(string $date): string
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $time   = strtotime($date);

        return date('j', $time) . ' ' . $months[(int) date('n', $time) - 1];
    }

    /**
     * Rows for a chart: the first TOP rows and one "Lainnya (n kelompok)" row
     * with the sum of the rest. Ordered cards are never folded.
     *
     * @param array<string, mixed> $card
     *
     * @return list<array<string, mixed>>
     */
    public static function chartRows(array $card): array
    {
        $rows = $card['rows'];

        if ($card['ordered'] || count($rows) <= self::TOP + 1) {
            return $rows;
        }

        $rest = array_slice($rows, self::TOP);

        return [...array_slice($rows, 0, self::TOP), ['label' => lang('Statistik.otherGroups', [count($rest)]), 'count' => self::sum($rest), 'muted' => true]];
    }
}
