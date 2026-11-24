<?php
/**
 * Laporan → Statistik: filters, summary tiles, then five groups of folding
 * cards (asal, keluarga, murid, pendaftaran, perbandingan), each group with a
 * CSV download. Several cards per row from xl (summary page, D-102).
 *
 * @var StatistikFilter                                                           $filter
 * @var StatistikReport                                                           $report
 * @var array{total: int, online: int, offline: int, complete: int}               $totals
 * @var list<array{id: string, title: string, cards: list<array<string, mixed>>}> $sections
 * @var bool                                                                      $canExport
 */

use Modules\Statistik\Controllers\Statistik;
use Modules\Statistik\Libraries\Angka;
use Modules\Statistik\Libraries\StatistikFilter;
use Modules\Statistik\Libraries\StatistikReport;

$input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
$label = 'mb-2 block text-sm font-medium text-gray-900 dark:text-white';
$waves = ['' => lang('Statistik.filterWaveAll')] + $filter->waves + ['tanpa' => lang('Statistik.filterWaveNone')];
$tiles = [
    ['label' => lang('Statistik.tileTotal'), 'value' => $totals['total'], 'icon' => 'users', 'note' => lang('Statistik.filterYear') . ' ' . $filter->year],
    ['label' => lang('Statistik.tileOnline'), 'value' => $totals['online'], 'icon' => 'globe', 'note' => Angka::percent($totals['online'], $totals['total'])],
    ['label' => lang('Statistik.tileOffline'), 'value' => $totals['offline'], 'icon' => 'school', 'note' => Angka::percent($totals['offline'], $totals['total'])],
    ['label' => lang('Statistik.tileComplete'), 'value' => $totals['complete'], 'icon' => 'clipboard-check', 'note' => Angka::percent($totals['complete'], $totals['total'])],
];
// The view switches and the drill-down stay when the filters change.
$keep = array_filter([
    'kab'         => $filter->regency,
    'kec'         => $filter->district,
    'sekolah'     => $filter->schoolMode === 'kecamatan' ? null : $filter->schoolMode,
    'waktu'       => $filter->timeMode,
    'penghasilan' => $filter->incomeParent === 'ayah' ? null : $filter->incomeParent,
], static fn (?string $v): bool => $v !== null);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="get" action="<?= url_to('statistik') ?>" class="rounded-lg border border-gray-200 bg-white p-4 shadow-xs sm:p-5 dark:border-gray-700 dark:bg-gray-800" aria-labelledby="statistik-saring">
    <h2 id="statistik-saring" class="sr-only"><?= lang('Statistik.filterTitle') ?></h2>
    <?php foreach ($keep as $name => $value) : ?>
        <input type="hidden" name="<?= esc($name, 'attr') ?>" value="<?= esc($value, 'attr') ?>">
    <?php endforeach ?>
    <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-[repeat(3,minmax(0,1fr))_auto]">
        <div>
            <label for="tahun" class="<?= $label ?>"><?= lang('Statistik.filterYear') ?></label>
            <select id="tahun" name="tahun" class="<?= $input ?>">
                <?php foreach ($filter->years as $year) : ?>
                    <option value="<?= esc($year, 'attr') ?>"<?= $year === $filter->year ? ' selected' : '' ?>><?= esc($year) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div>
            <label for="gelombang" class="<?= $label ?>"><?= lang('Statistik.filterWave') ?></label>
            <select id="gelombang" name="gelombang" class="<?= $input ?>">
                <?php foreach ($waves as $value => $name) : ?>
                    <option value="<?= esc((string) $value, 'attr') ?>"<?= (string) $value === (string) ($filter->wave ?? '') ? ' selected' : '' ?>><?= esc($name) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div>
            <label for="status" class="<?= $label ?>"><?= lang('Statistik.filterStatus') ?></label>
            <select id="status" name="status" class="<?= $input ?>">
                <?php foreach ((array) lang('Statistik.statusOptions') as $value => $name) : ?>
                    <option value="<?= esc($value, 'attr') ?>"<?= $value === $filter->status ? ' selected' : '' ?>><?= esc($name) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="flex gap-3">
            <?php if ($filter->isFiltered()) : ?>
                <a href="<?= url_to('statistik') ?>" class="flex-1 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 lg:flex-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700"><?= lang('Statistik.filterReset') ?></a>
            <?php endif ?>
            <button type="submit" class="flex-1 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 lg:flex-none dark:focus:ring-primary-800"><?= lang('Statistik.filterApply') ?></button>
        </div>
    </div>
    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400"><?= esc(lang('Statistik.filterNow', [Statistik::describe($filter)])) ?></p>
</form>

<h2 class="sr-only"><?= lang('Statistik.tilesLabel') ?></h2>
<div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
    <?php foreach ($tiles as $i => $tile) : ?>
        <div class="motion-rise min-w-0 rounded-lg border border-gray-200 bg-white p-4 shadow-xs sm:p-5 dark:border-gray-700 dark:bg-gray-800" style="--rise-delay: <?= $i * 60 ?>ms">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($tile['label']) ?></p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 tabular-nums sm:text-3xl dark:text-white"><?= Angka::show($tile['value']) ?></p>
                </div>
                <?= icon($tile['icon'], 'h-6 w-6 shrink-0 text-primary-600 sm:h-7 sm:w-7 dark:text-primary-400') ?>
            </div>
            <?php if ($tile['note'] !== '') : ?>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400"><?= $i === 0 ? esc($tile['note']) : esc(lang('Statistik.tileOf', [$tile['note']])) ?></p>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>

<?php if ($sections === []) : ?>
    <div class="mt-6"><?= view('partials/empty_state', ['empty' => $filter->isFiltered()
        ? ['title' => lang('Statistik.emptyFilterTitle'), 'text' => lang('Statistik.emptyFilterText'), 'icon' => 'search', 'image' => 'state/kosong-statistik', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData')]
        : ['title' => lang('Statistik.emptyTitle'), 'text' => lang('Statistik.emptyText'), 'icon' => 'chart-column', 'image' => 'state/kosong-statistik', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData')],
    ]) ?></div>
<?php else : ?>
    <div class="mt-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <nav aria-label="<?= esc(lang('Statistik.jumpLabel'), 'attr') ?>">
            <ul class="flex flex-wrap gap-2">
                <?php foreach ($sections as $section) : ?>
                    <li><a href="#<?= esc($section['id'], 'attr') ?>" class="inline-flex min-h-10 items-center rounded-full border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-primary-800"><?= esc($section['title']) ?></a></li>
                <?php endforeach ?>
            </ul>
        </nav>
        <?= view('partials/lipatan_semua', ['class' => 'shrink-0'], ['saveData' => false]) ?>
    </div>

    <p class="mt-4 flex items-start gap-2 rounded-lg bg-primary-50 p-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
        <?= icon('shield-check', 'mt-0.5 h-5 w-5 shrink-0 text-primary-700 dark:text-primary-400') ?>
        <span><span class="font-semibold text-gray-900 dark:text-white"><?= lang('Statistik.privacyTitle') ?>.</span> <?= lang('Statistik.privacyText') ?></span>
    </p>

    <?php foreach ($sections as $section) : ?>
        <section id="<?= esc($section['id'], 'attr') ?>" class="mt-8 scroll-mt-20" aria-labelledby="judul-<?= esc($section['id'], 'attr') ?>">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 id="judul-<?= esc($section['id'], 'attr') ?>" class="text-xl font-semibold text-gray-900 dark:text-white"><?= esc($section['title']) ?></h2>
                <?php if ($canExport) : ?>
                    <a href="<?= esc(url_to('statistik.unduh', $section['id']) . $filter->query(), 'attr') ?>" download
                       class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                       aria-label="<?= esc(lang('Statistik.downloadLabel', [$section['title']]), 'attr') ?>"><?= icon('download', 'h-4 w-4') ?><?= lang('Statistik.download') ?></a>
                <?php endif ?>
            </div>
            <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-2 2xl:grid-cols-3">
                <?php foreach ($section['cards'] as $card) : ?>
                    <?= view('Modules\Statistik\Views\_kartu', ['card' => $card], ['saveData' => false]) ?>
                <?php endforeach ?>
            </div>
        </section>
    <?php endforeach ?>
<?php endif ?>
<?= $this->endSection() ?>

<?php if ($sections !== []) : ?>
    <?= $this->section('scripts') ?>
    <script src="<?= asset_url('charts.js') ?>" defer></script>
    <?= $this->endSection() ?>
<?php endif ?>
