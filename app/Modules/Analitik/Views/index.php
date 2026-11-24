<?php
/**
 * Website → Pengunjung: summary tiles, visitors per day, most opened pages,
 * how counting works and (pengaturan.sekolah) the settings card.
 *
 * @var array<string, array{visitors: int, views: int}>          $totals see VisitStats::totals()
 * @var list<array{date: string, visitors: int, views: int}>     $daily
 * @var int                                                      $range  7 | 30 | 90
 * @var list<array{path: string, views: int, name: string|null}> $pages
 * @var bool                                                     $canSettings
 * @var array<string, string>                                    $values
 */

use Modules\Analitik\Controllers\Pengunjung;

$num  = static fn (int $n): string => number_format($n, 0, ',', '.');
$card = 'rounded-lg border border-gray-200 bg-white p-5 shadow-xs sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2   = 'text-lg font-semibold text-gray-900 dark:text-white';

$tiles = [
    'today'     => ['label' => lang('Analitik.today'), 'note' => tanggal_indo(date('Y-m-d'))],
    'yesterday' => ['label' => lang('Analitik.yesterday'), 'note' => null],
    'week'      => ['label' => lang('Analitik.week'), 'note' => null],
    'month'     => ['label' => lang('Analitik.month'), 'note' => null],
    'total'     => ['label' => lang('Analitik.total'), 'note' => null],
];

$hasData = $totals['total']['views'] > 0;

// Chart: visitors as an area, page views as a line, one axis (same unit). Dates as UTC midnight so labels match the day.
$toPoint   = static fn (array $day, string $key): array => [strtotime($day['date'] . ' 00:00:00 UTC') * 1000, $day[$key]];
$rangeSum  = array_sum(array_column($daily, 'visitors'));
$rangeView = array_sum(array_column($daily, 'views'));
$busiest   = $daily === [] ? null : array_reduce($daily, static fn (?array $best, array $day): array => $best === null || $day['visitors'] > $best['visitors'] ? $day : $best);
$chartText = $rangeView === 0 || $busiest === null
    ? lang('Analitik.chartEmpty', [$range])
    : lang('Analitik.chartSummary', [$range, $num($rangeSum), $num($rangeView), tanggal_indo($busiest['date']), $num($busiest['visitors'])]);
$chart = [
    'chart' => [
        'type'          => 'area',
        'height'        => 320,
        'defaultLocale' => 'id',
        'locales'       => [[
            'name'    => 'id',
            'options' => [
                'months'      => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                'shortMonths' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                'days'        => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                'shortDays'   => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            ],
        ]],
    ],
    'series' => [
        ['name' => lang('Analitik.visitors'), 'type' => 'area', 'data' => array_map(static fn (array $d): array => $toPoint($d, 'visitors'), $daily)],
        ['name' => lang('Analitik.views'), 'type' => 'line', 'data' => array_map(static fn (array $d): array => $toPoint($d, 'views'), $daily)],
    ],
    'stroke'  => ['width' => [2, 2], 'curve' => 'monotoneCubic'],
    'fill'    => ['type' => 'solid', 'opacity' => [0.18, 1]],
    'markers' => ['size' => 0, 'hover' => ['size' => 5]],
    'xaxis'   => ['type' => 'datetime', 'labels' => ['datetimeUTC' => true, 'format' => 'd MMM']],
    'yaxis'   => ['min' => 0, 'forceNiceScale' => true, 'decimalsInFloat' => 0],
    'tooltip' => ['shared' => true, 'intersect' => false, 'x' => ['format' => 'dddd, d MMMM yyyy']],
    'legend'  => ['position' => 'top', 'horizontalAlign' => 'left'],
];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<h2 class="sr-only"><?= lang('Analitik.tilesLabel') ?></h2>
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-5">
    <?php $i = 0;

foreach ($tiles as $key => $tile) : ?>
        <div class="motion-rise min-w-0 rounded-lg border border-gray-200 bg-white p-4 shadow-xs sm:p-5 dark:border-gray-700 dark:bg-gray-800<?= $key === 'total' ? ' col-span-2 sm:col-span-1' : '' ?>" style="--rise-delay: <?= $i++ * 60 ?>ms">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($tile['label']) ?></p>
            <p class="mt-2 text-2xl font-bold text-gray-900 tabular-nums sm:text-3xl dark:text-white"><?= $num($totals[$key]['visitors']) ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Analitik.visitorsUnit') ?></p>
            <p class="mt-3 text-sm text-gray-700 dark:text-gray-300"><?= lang('Analitik.viewsCount', ['<span class="font-semibold tabular-nums">' . $num($totals[$key]['views']) . '</span>']) ?></p>
            <?php if ($tile['note'] !== null) : ?><p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?= esc($tile['note']) ?></p><?php endif ?>
        </div>
    <?php endforeach ?>
</div>

<?php if (! $hasData) : ?>
    <div class="mt-6"><?= view('partials/empty_state', ['empty' => [
    'title'    => lang('Analitik.emptyTitle'),
    'text'     => lang('Analitik.emptyText'),
    'icon'     => 'chart-line',
    'image'    => 'state/kosong-pengunjung', 'fallback' => 'state/kosong-data',
    'imageAlt' => lang('App.altNoData'),
]]) ?></div>
<?php endif ?>

<?php // Two columns from xl (chart and explanation | pages and settings); on smaller screens the wrappers dissolve and `order` sets chart, pages, explanation, settings.?>
<div class="mt-6 grid grid-cols-1 items-start gap-4 xl:grid-cols-3">
    <div class="contents xl:col-span-2 xl:block xl:min-w-0 xl:space-y-4">
    <?php if ($hasData) : ?>
        <section class="<?= $card ?> order-1 min-w-0" aria-labelledby="pengunjung-grafik">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 id="pengunjung-grafik" class="<?= $h2 ?>"><?= lang('Analitik.chartTitle') ?></h2>
                <nav aria-label="<?= esc(lang('Analitik.rangeLabel'), 'attr') ?>" class="inline-flex rounded-lg shadow-xs">
                    <?php foreach (Pengunjung::RANGES as $j => $days) : ?>
                        <?php $active = $days === $range; ?>
                        <a href="<?= esc(site_url('admin/pengunjung') . '?rentang=' . $days, 'attr') ?>"<?= $active ? ' aria-current="page"' : '' ?>
                           class="inline-flex min-h-10 items-center border px-4 text-sm font-medium focus:z-10 focus:ring-2 focus:ring-primary-500 focus:outline-none <?= $j === 0 ? 'rounded-s-lg' : '' ?> <?= $j === count(Pengunjung::RANGES) - 1 ? 'rounded-e-lg' : '' ?> <?= $j > 0 ? '-ms-px' : '' ?> <?= $active ? 'z-10 border-primary-600 bg-primary-600 text-white dark:border-primary-500 dark:bg-primary-600' : 'border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700' ?>"><?= lang('Analitik.rangeDays', [$days]) ?></a>
                    <?php endforeach ?>
                </nav>
            </div>
            <div class="min-h-[320px] min-w-0" data-chart="<?= esc(json_encode($chart, JSON_THROW_ON_ERROR), 'attr') ?>" role="img" aria-label="<?= esc($chartText, 'attr') ?>"></div>
            <details class="mt-4 rounded-lg border border-gray-200 dark:border-gray-700">
                <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-primary-500 focus:outline-none dark:text-white"><?= lang('Analitik.tableToggle') ?></summary>
                <div class="max-h-80 overflow-auto border-t border-gray-200 dark:border-gray-700">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                        <caption class="sr-only"><?= esc($chartText) ?></caption>
                        <thead class="sticky top-0 bg-gray-50 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-4 py-2 font-medium"><?= lang('Analitik.colDate') ?></th>
                                <th scope="col" class="px-4 py-2 text-right font-medium"><?= lang('Analitik.visitors') ?></th>
                                <th scope="col" class="px-4 py-2 text-right font-medium"><?= lang('Analitik.views') ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach (array_reverse($daily) as $day) : ?>
                                <tr>
                                    <th scope="row" class="px-4 py-2 font-normal whitespace-nowrap text-gray-900 dark:text-white"><?= esc(tanggal_indo($day['date'])) ?></th>
                                    <td class="px-4 py-2 text-right tabular-nums"><?= $num($day['visitors']) ?></td>
                                    <td class="px-4 py-2 text-right tabular-nums"><?= $num($day['views']) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </details>
        </section>
    <?php endif ?>
        <section class="<?= $card ?> order-3" aria-labelledby="pengunjung-cara">
            <h2 id="pengunjung-cara" class="<?= $h2 ?> flex items-center gap-2"><?= icon('shield-check', 'h-5 w-5 shrink-0 text-primary-600 dark:text-primary-400') ?><?= lang('Analitik.howTitle') ?></h2>
            <ul class="mt-4 list-disc space-y-2 ps-5 text-sm text-gray-700 dark:text-gray-300">
                <?php foreach ((array) lang('Analitik.howItems') as $item) : ?>
                    <li><?= esc($item) ?></li>
                <?php endforeach ?>
            </ul>
        </section>
    </div>

    <div class="contents xl:block xl:min-w-0 xl:space-y-4">
    <?php if ($hasData) : ?>
        <section class="<?= $card ?> order-2 min-w-0" aria-labelledby="pengunjung-halaman">
            <h2 id="pengunjung-halaman" class="<?= $h2 ?>"><?= lang('Analitik.pagesTitle') ?></h2>
            <p class="mt-1 mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Analitik.pagesIntro') ?></p>
            <?php if ($pages === []) : ?>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Analitik.pagesEmpty') ?></p>
            <?php else : ?>
                <?php $max = max(1, ...array_column($pages, 'views')); ?>
                <ol class="space-y-4">
                    <?php foreach ($pages as $page) : ?>
                        <?php $pct = (int) round($page['views'] / $max * 100); ?>
                        <li>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-gray-900 dark:text-white"><?= esc($page['name'] ?? lang('Analitik.pageOther')) ?></span>
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">/<?= esc($page['path']) ?></span>
                                </span>
                                <span class="shrink-0 text-gray-700 tabular-nums dark:text-gray-300"><?= $num($page['views']) ?> <span class="sr-only"><?= lang('Analitik.colViews') ?></span></span>
                            </div>
                            <div class="mt-1.5 h-2 w-full rounded-full bg-primary-100 dark:bg-gray-700" aria-hidden="true">
                                <div class="h-2 rounded-full bg-primary-600 dark:bg-primary-500" style="width: <?= max(2, $pct) ?>%"></div>
                            </div>
                        </li>
                    <?php endforeach ?>
                </ol>
            <?php endif ?>
        </section>
    <?php endif ?>
    <?php if ($canSettings) : ?>
        <section class="<?= $card ?> order-4" aria-labelledby="pengunjung-pengaturan">
            <h2 id="pengunjung-pengaturan" class="<?= $h2 ?> mb-4"><?= lang('Analitik.settingsTitle') ?></h2>
            <form method="post" action="<?= url_to('analitik.pengaturan') ?>" class="space-y-5">
                <?= csrf_field() ?>
                <div>
                    <?= view('partials/form_field', ['field' => ['name' => 'showInFooter', 'label' => lang('Analitik.showInFooter'), 'type' => 'checkbox', 'value' => $values['showInFooter']]]) ?>
                    <p class="ms-7 mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Analitik.showInFooterHelp') ?></p>
                </div>
                <div>
                    <?= view('partials/form_field', ['field' => [
                    'name'     => 'retentionDays',
                    'label'    => lang('Analitik.retentionDays'),
                    'type'     => 'number',
                    'value'    => $values['retentionDays'],
                    'required' => true,
                    'help'     => lang('Analitik.retentionDaysHelp'),
                    'attrs'    => ['min' => (string) Pengunjung::RETENTION_MIN, 'max' => (string) Pengunjung::RETENTION_MAX, 'step' => '1', 'inputmode' => 'numeric'],
                ]]) ?>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 focus:outline-none sm:w-auto dark:focus:ring-primary-800"><?= lang('Analitik.save') ?></button>
                </div>
            </form>
        </section>
    <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>

<?php if ($hasData) : ?>
    <?= $this->section('scripts') ?>
    <script src="<?= asset_url('charts.js') ?>" defer></script>
    <?= $this->endSection() ?>
<?php endif ?>
