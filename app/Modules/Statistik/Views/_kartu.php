<?php
/**
 * One Statistik card (see StatistikReport for the card array): a folding card
 * with an optional switch and location trail, then a chart with its table
 * folded under "Lihat tabel" (bars, columns, stacked) or a table with small
 * bars in it (meter). Counts of 1–2 show as "< 3" (Angka::show).
 *
 * @var array<string, mixed> $card
 */

use Modules\Statistik\Libraries\Angka;
use Modules\Statistik\Libraries\StatistikReport;

$id         = (string) $card['id'];
$total      = (int) $card['total'];
$wide       = ! empty($card['wide']) ? ' xl:col-span-2 2xl:col-span-3' : '';
$th         = 'px-3 py-2 font-medium';
$chartTitle = (string) ($card['chartTitle'] ?? $card['title']);
$muted      = static fn (array $row): bool => ! empty($row['muted']) || $row['label'] === lang('Statistik.notFilled');
$grey       = '#9ca3af';
?>
<div id="<?= esc($id, 'attr') ?>" class="min-w-0 scroll-mt-20<?= $wide ?>">
<?= lipatan_buka(['id' => $id, 'title' => $card['title'], 'intro' => $card['intro'] ?? null, 'heading' => 'h3', 'class' => 'shadow-xs']) ?>
    <?php if (! empty($card['switch'])) : ?>
        <nav aria-label="<?= esc($card['switch']['label'], 'attr') ?>" class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500 dark:text-gray-400" aria-hidden="true"><?= esc($card['switch']['label']) ?>:</span>
            <span class="inline-flex flex-wrap rounded-lg shadow-xs">
                <?php $last = count($card['switch']['options']) - 1; ?>
                <?php foreach ($card['switch']['options'] as $j => $option) : ?>
                    <a href="<?= esc($option['url'], 'attr') ?>"<?= $option['active'] ? ' aria-current="true"' : '' ?>
                       class="inline-flex min-h-10 items-center border px-3 text-sm font-medium focus:z-10 focus:ring-2 focus:ring-primary-500 focus:outline-none <?= $j === 0 ? 'rounded-s-lg' : '-ms-px' ?> <?= $j === $last ? 'rounded-e-lg' : '' ?> <?= $option['active'] ? 'z-10 border-primary-600 bg-primary-600 text-white dark:border-primary-500' : 'border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700' ?>"><?= esc($option['label']) ?></a>
                <?php endforeach ?>
            </span>
        </nav>
    <?php endif ?>

    <?php if (! empty($card['trail'])) : ?>
        <nav aria-label="<?= esc(lang('Statistik.locationTrail'), 'attr') ?>" class="mb-4">
            <ol class="flex flex-wrap items-center gap-1 text-sm">
                <?php foreach ($card['trail'] as $j => $step) : ?>
                    <li class="flex items-center gap-1">
                        <?php if ($j > 0) : ?><?= icon('chevron-right', 'h-4 w-4 text-gray-400') ?><?php endif ?>
                        <?php if ($step['url'] !== null) : ?>
                            <a href="<?= esc($step['url'], 'attr') ?>" class="font-medium text-primary-700 hover:underline dark:text-primary-400"><?= esc($step['label']) ?></a>
                        <?php else : ?>
                            <span class="font-medium text-gray-900 dark:text-white" aria-current="page"><?= esc($step['label']) ?></span>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ol>
        </nav>
    <?php endif ?>

    <?php if ($total === 0) : ?>
        <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Statistik.noRows') ?></p>
    <?php elseif ($card['type'] === 'meter') : ?>
        <?php $max = max(1, ...array_column($card['rows'], 'count')); ?>
        <table class="w-full text-sm text-gray-700 dark:text-gray-300">
            <caption class="sr-only"><?= esc($card['title']) ?></caption>
            <thead class="text-xs text-gray-500 dark:text-gray-400">
                <tr>
                    <th scope="col" class="pb-2 text-start font-medium"><?= esc($card['labelHeader']) ?></th>
                    <th scope="col" class="pb-2 ps-3 text-end font-medium"><?= lang('Statistik.colCount') ?></th>
                    <th scope="col" class="pb-2 ps-3 text-end font-medium"><?= lang('Statistik.colPercent') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($card['rows'] as $row) : ?>
                    <tr>
                        <th scope="row" class="py-2 text-start font-normal">
                            <span class="text-gray-900 dark:text-white"><?= esc($row['label']) ?></span>
                            <span class="mt-1.5 block h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700" aria-hidden="true">
                                <span class="block h-2 rounded-full <?= $muted($row) ? 'bg-gray-400' : 'bg-primary-600 dark:bg-primary-500' ?>" style="width: <?= $row['count'] > 0 ? max(2, (int) round($row['count'] / $max * 100)) : 0 ?>%"></span>
                            </span>
                        </th>
                        <td class="py-2 ps-3 text-end align-top whitespace-nowrap tabular-nums"><?= Angka::show($row['count']) ?></td>
                        <td class="py-2 ps-3 text-end align-top whitespace-nowrap text-gray-500 tabular-nums dark:text-gray-400"><?= Angka::percent($row['count'], $total) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php else : ?>
        <?php
        if ($card['type'] === 'stacked') {
            $series = [];

            foreach ($card['series'] as $s => $name) {
                $one = ['name' => $name, 'data' => array_map(static fn (array $g): int => $g['cells'][$s], $card['groups'])];

                // "Belum diisi" is grey, apart from the light → dark income steps.
                if (! empty($card['blankLast']) && $s === count($card['series']) - 1) {
                    $one['color'] = $grey;
                }

                $series[] = $one;
            }

            $labels = array_column($card['groups'], 'label');
            $chart  = [
                'chart'  => ['type' => 'bar', 'height' => max(220, 90 + count($labels) * 44), 'stacked' => true, 'stackType' => '100%'],
                'series' => $series,
                'xaxis'  => ['categories' => $labels, 'tickAmount' => 5, 'labels' => ['trim' => false]],
                'yaxis'  => ['labels' => ['maxWidth' => 200]],
                // Phones: the legend moves below and takes one line per income range; give it room (639, so it
                // applies after the shared 640 px defaults of charts.js, which set a smaller height).
                'responsive'  => [['breakpoint' => 639, 'options' => ['chart' => ['height' => 140 + count($labels) * 36 + count($series) * 26], 'xaxis' => ['tickAmount' => 4]]]],
                'plotOptions' => ['bar' => ['horizontal' => true, 'barHeight' => '62%', 'borderRadiusWhenStacked' => 'last']],
                'legend'      => ['position' => 'top', 'horizontalAlign' => 'left'],
                'grid'        => ['xaxis' => ['lines' => ['show' => true]], 'yaxis' => ['lines' => ['show' => false]], 'padding' => ['right' => 24]],
                'tooltip'     => ['shared' => false, 'intersect' => true],
                'spmb'        => ['mask' => Angka::HIDE_BELOW, 'sequential' => true, 'gap' => true, 'percentAxis' => true],
            ];
            $summary = lang('Statistik.crossSummary', [$card['title'], count($labels)]);
        } else {
            $rows   = StatistikReport::chartRows($card);
            $labels = array_column($rows, 'label');
            $counts = array_map('intval', array_column($rows, 'count'));
            $links  = array_map(static fn (array $r): ?string => $r['url'] ?? null, $rows);
            $chart  = $card['type'] === 'columns'
                ? [
                    'chart'       => ['type' => 'bar', 'height' => 300],
                    'series'      => [['name' => lang('Statistik.colCount'), 'data' => $counts]],
                    'xaxis'       => ['categories' => array_column($rows, 'short'), 'labels' => ['trim' => false, 'rotate' => -45, 'hideOverlappingLabels' => true]],
                    'yaxis'       => ['min' => 0, 'forceNiceScale' => true, 'decimalsInFloat' => 0],
                    'plotOptions' => ['bar' => ['columnWidth' => count($rows) > 30 ? '85%' : '55%']],
                    'legend'      => ['show' => false],
                    'spmb'        => ['mask' => Angka::HIDE_BELOW],
                ]
                : [
                    'chart' => ['type' => 'bar', 'height' => max(160, 50 + count($rows) * 30)],
                    // "Belum diisi" and "Lainnya" are grey, the groups themselves in the brand colour.
                    'series'      => [['name' => lang('Statistik.colCount'), 'data' => array_map(static fn (array $r): array => ['x' => $r['label'], 'y' => (int) $r['count']] + ($muted($r) ? ['fillColor' => $grey] : []), $rows)]],
                    'xaxis'       => ['categories' => $labels, 'decimalsInFloat' => 0, 'tickAmount' => 5],
                    'yaxis'       => ['labels' => ['maxWidth' => 220]],
                    'plotOptions' => ['bar' => ['horizontal' => true, 'barHeight' => '70%']],
                    'grid'        => ['xaxis' => ['lines' => ['show' => true]], 'yaxis' => ['lines' => ['show' => false]], 'padding' => ['top' => -24, 'right' => 24]],
                    'legend'      => ['show' => false],
                    'spmb'        => ['mask' => Angka::HIDE_BELOW] + (array_filter($links) !== [] ? ['links' => $links] : []),
                ];
            $summary = '';
        }

        if ($summary === '') {
            // "Calon murid per kecamatan. Terbanyak: Jati (12), Bae (8), Gebog (< 3)."
            $ranked = $rows;
            usort($ranked, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
            $best    = array_map(static fn (array $r): string => lang('Statistik.chartTopList', [$r['label'], Angka::show($r['count'])]), array_slice(array_filter($ranked, static fn (array $r): bool => $r['count'] > 0), 0, 3));
            $summary = lang('Statistik.chartSummary', [$chartTitle, implode(', ', $best)]);
        }
        ?>
        <div class="min-w-0" data-chart="<?= esc(json_encode($chart, JSON_THROW_ON_ERROR), 'attr') ?>" role="img" aria-label="<?= esc($summary, 'attr') ?>" style="min-height: <?= (int) $chart['chart']['height'] ?>px"></div>
        <?php if (! empty($card['hint'])) : ?>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= esc($card['hint']) ?></p>
        <?php endif ?>

        <?php // The location table holds the links to the next level: open by default.?>
        <details class="mt-4 rounded-lg border border-gray-200 dark:border-gray-700"<?= ! empty($card['trail']) ? ' open' : '' ?>>
            <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-primary-500 focus:outline-none dark:text-white">
                <?= $card['type'] !== 'stacked' && count($card['rows']) > count($rows ?? []) ? lang('Statistik.tableAll', [count($card['rows'])]) : lang('Statistik.tableToggle') ?>
            </summary>
            <div class="max-h-96 overflow-auto border-t border-gray-200 dark:border-gray-700">
                <table class="w-full text-start text-sm text-gray-600 dark:text-gray-400">
                    <caption class="sr-only"><?= esc($chartTitle) ?></caption>
                    <?php if ($card['type'] === 'stacked') : ?>
                        <thead class="sticky top-0 bg-gray-50 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="<?= $th ?> text-start"><?= esc($card['labelHeader']) ?></th>
                                <?php foreach ($card['series'] as $name) : ?>
                                    <th scope="col" class="<?= $th ?> text-end"><?= esc($name) ?></th>
                                <?php endforeach ?>
                                <th scope="col" class="<?= $th ?> text-end"><?= lang('Statistik.colTotal') ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($card['groups'] as $group) : ?>
                                <tr>
                                    <th scope="row" class="px-3 py-2 text-start font-normal text-gray-900 dark:text-white"><?= esc($group['label']) ?></th>
                                    <?php foreach ($group['cells'] as $cell) : ?>
                                        <?php $pct = Angka::percent($cell, $group['count']); ?>
                                        <td class="px-3 py-2 text-end whitespace-nowrap tabular-nums"><?= Angka::show($cell) ?><?php if ($pct !== '' && $cell > 0) : ?> <span class="text-xs text-gray-500 dark:text-gray-400">(<?= $pct ?>)</span><?php endif ?></td>
                                    <?php endforeach ?>
                                    <td class="px-3 py-2 text-end font-medium whitespace-nowrap text-gray-900 tabular-nums dark:text-white"><?= Angka::show($group['count']) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    <?php else : ?>
                        <?php $note = ! empty($card['noteHeader']); ?>
                        <thead class="sticky top-0 bg-gray-50 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="<?= $th ?> text-start"><?= esc($card['labelHeader']) ?></th>
                                <?php if ($note) : ?><th scope="col" class="<?= $th ?> text-start"><?= esc($card['noteHeader']) ?></th><?php endif ?>
                                <th scope="col" class="<?= $th ?> text-end"><?= lang('Statistik.colCount') ?></th>
                                <th scope="col" class="<?= $th ?> text-end"><?= lang('Statistik.colPercent') ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($card['rows'] as $row) : ?>
                                <tr>
                                    <th scope="row" class="px-3 py-2 text-start font-normal text-gray-900 dark:text-white">
                                        <?php if (! empty($row['url'])) : ?>
                                            <a href="<?= esc($row['url'], 'attr') ?>" class="font-medium text-primary-700 hover:underline dark:text-primary-400"><?= esc($row['label']) ?></a>
                                        <?php else : ?>
                                            <?= esc($row['label']) ?>
                                        <?php endif ?>
                                    </th>
                                    <?php if ($note) : ?><td class="px-3 py-2"><?= esc((string) ($row['note'] ?? '')) ?></td><?php endif ?>
                                    <td class="px-3 py-2 text-end whitespace-nowrap tabular-nums"><?= Angka::show($row['count']) ?></td>
                                    <td class="px-3 py-2 text-end whitespace-nowrap tabular-nums"><?= Angka::percent($row['count'], $total) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot class="border-t border-gray-200 font-medium text-gray-900 dark:border-gray-700 dark:text-white">
                            <tr>
                                <th scope="row" class="px-3 py-2 text-start"><?= lang('Statistik.rowTotal') ?></th>
                                <?php if ($note) : ?><td></td><?php endif ?>
                                <td class="px-3 py-2 text-end tabular-nums"><?= Angka::show($total) ?></td>
                                <td class="px-3 py-2 text-end tabular-nums">100%</td>
                            </tr>
                        </tfoot>
                    <?php endif ?>
                </table>
            </div>
        </details>
    <?php endif ?>
<?= lipatan_tutup() ?>
</div>
