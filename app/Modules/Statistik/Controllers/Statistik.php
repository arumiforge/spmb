<?php

declare(strict_types=1);

namespace Modules\Statistik\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Statistik\Libraries\Angka;
use Modules\Statistik\Libraries\StatistikFilter;
use Modules\Statistik\Libraries\StatistikQuery;
use Modules\Statistik\Libraries\StatistikReport;

/**
 * Laporan → Statistik (laporan.view): applicant statistics for school
 * planning (D-111), filtered by academic year, wave and status. Only counts,
 * never names; groups of 1–2 show as "< 3". Each section's tables can be
 * downloaded as CSV (laporan.export) with the same filters and view switches.
 */
class Statistik extends BaseController
{
    public function index(): string
    {
        $filter = StatistikFilter::fromQuery($this->request->getGet());
        $report = $this->report($filter);
        $totals = $report->totals();
        $user   = auth()->user();

        return view('Modules\Statistik\Views\index', [
            'title'       => lang('Statistik.title'),
            'subtitle'    => lang('Statistik.intro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Statistik.group')], ['label' => lang('Statistik.menu')]],
            'filter'      => $filter,
            'report'      => $report,
            'totals'      => $totals,
            'sections'    => $totals['total'] > 0 ? $report->sections() : [],
            'canExport'   => $user !== null && $user->can('laporan.export'),
        ]);
    }

    /**
     * CSV of one section (UTF-8 with BOM so spreadsheet programs read the
     * Indonesian text; semicolons would not be needed: values are quoted).
     */
    public function unduh(string $section): ResponseInterface
    {
        if (! in_array($section, StatistikReport::SECTIONS, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $filter = StatistikFilter::fromQuery($this->request->getGet());
        $data   = $this->report($filter)->section($section);
        $lines  = [
            [lang('Statistik.title') . ' – ' . $data['title']],
            [lang('Statistik.csvFilters'), self::describe($filter)],
            [lang('Statistik.csvMade'), tanggal_indo(date('Y-m-d H:i:s'), true)],
            [lang('Statistik.privacyText')],
        ];

        foreach ($data['cards'] as $card) {
            $lines[] = [];
            $lines[] = [$card['title'] . (isset($card['chartTitle']) && $card['chartTitle'] !== $card['title'] ? ' – ' . $card['chartTitle'] : '')];
            array_push($lines, ...self::tableLines($card));
        }

        $handle = fopen('php://temp', 'r+b');

        foreach ($lines as $line) {
            fputcsv($handle, $line, ',', '"', '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        $file = lang('Statistik.csvFile', [$section, str_replace('/', '-', $filter->year)]);

        return $this->response
            ->setContentType('text/csv', 'UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $file . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    private function report(StatistikFilter $filter): StatistikReport
    {
        return new StatistikReport($filter, new StatistikQuery($filter), site_url('admin/statistik'));
    }

    /**
     * Rows of one card's table for the CSV (counts masked like on the page).
     *
     * @param array<string, mixed> $card
     *
     * @return list<list<string>>
     */
    public static function tableLines(array $card): array
    {
        if ($card['type'] === 'stacked') {
            $lines = [[$card['labelHeader'], ...$card['series'], lang('Statistik.colTotal')]];

            foreach ($card['groups'] as $group) {
                $lines[] = [$group['label'], ...array_map(Angka::show(...), $group['cells']), Angka::show($group['count'])];
            }

            return $lines;
        }

        $note  = ! empty($card['noteHeader']);
        $lines = [[$card['labelHeader'], ...($note ? [$card['noteHeader']] : []), lang('Statistik.colCount'), lang('Statistik.colPercent')]];

        foreach ($card['rows'] as $row) {
            $lines[] = [$row['label'], ...($note ? [(string) ($row['note'] ?? '')] : []), Angka::show($row['count']), Angka::percent($row['count'], $card['total'])];
        }

        $lines[] = [lang('Statistik.rowTotal'), ...($note ? [''] : []), Angka::show($card['total']), $card['total'] > 0 ? '100%' : ''];

        return $lines;
    }

    /**
     * "Tahun ajaran 2027/2028 · Semua gelombang · Semua, kecuali yang dibatalkan".
     */
    public static function describe(StatistikFilter $filter): string
    {
        $wave = match (true) {
            $filter->wave === 'tanpa' => lang('Statistik.filterWaveNone'),
            is_int($filter->wave)     => $filter->waves[$filter->wave] ?? '',
            default                   => lang('Statistik.filterWaveAll'),
        };

        return lang('Statistik.filterYear') . ' ' . $filter->year . ' · ' . $wave . ' · ' . lang('Statistik.statusOptions')[$filter->status];
    }
}
