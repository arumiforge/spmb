<?php

use Modules\Situs\Libraries\SiteLink;
use Modules\Situs\Models\SiteNewsModel;

/**
 * Admin page for the news ticker ("Berita singkat"): switch and speed, then the items.
 *
 * @var list<array<string, mixed>> $items
 * @var bool                       $enabled
 * @var int                        $interval
 */
$card    = 'rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800';
$btn     = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$iconBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$today   = CodeIgniter\I18n\Time::today()->toDateString();
$showing = count(array_filter($items, static fn (array $item): bool => SiteNewsModel::isShowing($item, $today)));
$last    = count($items) - 1;
$move    = static function (int $id, string $direction, bool $disabled, string $label) use ($iconBtn): string {
    return '<form method="post" action="' . esc(url_to('situs.berita.pindah', $id), 'attr') . '">' . csrf_field()
        . '<input type="hidden" name="arah" value="' . $direction . '">'
        . '<button type="submit" class="' . $iconBtn . '"' . ($disabled ? ' disabled' : '') . ' title="' . esc($label, 'attr') . '">'
        . '<span class="sr-only">' . esc($label) . '</span>' . icon('chevron-down', 'h-4 w-4' . ($direction === 'naik' ? ' rotate-180' : '')) . '</button></form>';
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<div class="flex flex-wrap gap-2">
    <a href="<?= site_url('/') ?>" target="_blank" rel="noopener" class="<?= $btn ?> bg-white dark:bg-gray-800"><?= icon('eye', 'h-4 w-4') ?><?= lang('Situs.viewSite') ?></a>
    <a href="<?= url_to('situs.berita.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
        <?= icon('plus', 'h-4 w-4') ?><?= lang('Situs.newsAdd') ?>
    </a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <form method="post" action="<?= url_to('situs.berita.pengaturan') ?>" class="<?= $card ?>">
        <?= csrf_field() ?>
        <h2 class="border-b border-gray-200 px-4 py-3 text-base font-semibold text-gray-900 sm:px-6 dark:border-gray-700 dark:text-white"><?= lang('Situs.newsSettingsTitle') ?></h2>
        <div class="grid grid-cols-1 gap-5 p-4 sm:px-6 md:grid-cols-2">
            <div>
                <input type="hidden" name="enabled" value="0">
                <label class="inline-flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="enabled" value="1" class="peer sr-only" <?= old('enabled', $enabled ? '1' : '0') === '1' ? 'checked' : '' ?> aria-describedby="berita-aktif-help">
                    <span class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full bg-gray-200 peer-checked:bg-primary-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white rtl:peer-checked:after:-translate-x-full dark:bg-gray-700 dark:peer-focus:ring-primary-800"></span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white"><?= lang('Situs.newsEnabled') ?></span>
                </label>
                <p id="berita-aktif-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Situs.newsEnabledHelp') ?></p>
            </div>
            <div><?= view('partials/form_field', ['field' => ['name' => 'interval', 'type' => 'number', 'label' => lang('Situs.newsInterval'), 'value' => (string) $interval, 'required' => true, 'help' => lang('Situs.newsIntervalHelp'), 'attrs' => ['min' => '3', 'max' => '30', 'step' => '1', 'inputmode' => 'numeric']]]) ?></div>
        </div>
        <div class="flex justify-end border-t border-gray-200 p-4 sm:px-6 dark:border-gray-700">
            <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= lang('Situs.newsSettingsSave') ?></button>
        </div>
    </form>

    <?php if (! $enabled && $items !== []) : ?>
        <div class="flex items-start gap-3 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-gray-800 dark:text-yellow-300" role="status">
            <?= icon('info', 'mt-0.5 h-5 w-5 shrink-0') ?><p><?= lang('Situs.newsOffNotice') ?></p>
        </div>
    <?php endif ?>

    <?php if ($items === []) : ?>
        <?= view('partials/empty_state', ['empty' => ['title' => lang('Situs.newsEmptyTitle'), 'text' => lang('Situs.newsEmptyText'), 'icon' => 'megaphone', 'image' => 'state/kosong-berita', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData'), 'action' => ['label' => lang('Situs.newsAdd'), 'url' => 'admin/berita-singkat/tambah']]]) ?>
    <?php else : ?>
        <section class="<?= $card ?>" aria-labelledby="daftar-berita">
            <div class="border-b border-gray-200 p-4 sm:px-6 dark:border-gray-700">
                <h2 id="daftar-berita" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Situs.newsListTitle') ?></h2>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc(lang('Situs.newsCount', [count($items), $showing])) ?></p>
            </div>
            <ol class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($items as $i => $item) : ?>
                    <?php
                    $active = (bool) $item['is_active'];
                    $from   = (string) ($item['starts_on'] ?? '');
                    $until  = (string) ($item['ends_on'] ?? '');
                    $badge  = match (true) {
                        ! $active                    => ['label' => lang('Situs.badgeHidden'), 'color' => 'gray'],
                        $from !== '' && $from > $today => ['label' => lang('Situs.newsBadgeScheduled'), 'color' => 'yellow'],
                        $until !== '' && $until < $today => ['label' => lang('Situs.newsBadgeExpired'), 'color' => 'gray'],
                        default                      => ['label' => lang('Situs.badgeShown'), 'color' => 'green'],
                    };
                    $dates = array_filter([$from !== '' ? lang('Situs.newsDateFrom', [tanggal_indo($from)]) : '', $until !== '' ? lang('Situs.newsDateUntil', [tanggal_indo($until)]) : '']);
                    ?>
                    <li id="berita-<?= (int) $item['id'] ?>" class="flex scroll-mt-24 flex-col gap-3 p-4 sm:px-6 lg:flex-row lg:items-center">
                        <div class="order-last flex shrink-0 gap-1 lg:order-first">
                            <?= $move((int) $item['id'], 'naik', $i === 0, lang('Situs.moveUp') . ': ' . $item['text']) ?>
                            <?= $move((int) $item['id'], 'turun', $i === $last, lang('Situs.moveDown') . ': ' . $item['text']) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 dark:text-white"><?= esc($item['text']) ?></p>
                            <?php if ((string) $item['url'] !== '') : ?>
                                <p class="mt-1 flex items-center gap-1.5 break-all text-sm text-gray-600 dark:text-gray-300"><?= icon('link', 'h-4 w-4 shrink-0') ?><?= esc($item['url']) ?></p>
                            <?php endif ?>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <?= view('partials/status_badge', ['badge' => $badge]) ?>
                                <?php if ($dates !== []) : ?>
                                    <span class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400"><?= icon('calendar-days', 'h-4 w-4') ?><?= esc(lang('Situs.newsDates', [implode(' ', $dates)])) ?></span>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2 lg:order-last">
                            <a href="<?= url_to('situs.berita.ubah', $item['id']) ?>" class="<?= $btn ?>"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Situs.edit') ?></a>
                            <form method="post" action="<?= url_to('situs.berita.aktifkan', $item['id']) ?>">
                                <?= csrf_field() ?><input type="hidden" name="aktif" value="<?= $active ? '0' : '1' ?>">
                                <button type="submit" class="<?= $btn ?>"><?= icon($active ? 'eye-off' : 'eye', 'h-4 w-4') ?><?= lang($active ? 'Situs.hide' : 'Situs.show') ?></button>
                            </form>
                            <form method="post" action="<?= url_to('situs.berita.hapus', $item['id']) ?>" data-confirm="<?= esc(lang('Situs.newsDeleteConfirm', [$item['text']]), 'attr') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="<?= $btn ?> text-red-700 dark:text-red-400"><?= icon('trash', 'h-4 w-4') ?><?= lang('Situs.delete') ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ol>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
