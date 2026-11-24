<?php
/**
 * Home page popup (Situs.popup* settings). The public layout renders this panel
 * on "/" only. Renders nothing unless the popup is on and inside its dates, or an
 * admin (situs.kelola) opens "/?popup=pratinjau".
 *
 * A native <dialog> (focus stays inside, Esc closes) opened by resources/js/popup.js
 * after the page loads; the script remembers in the browser that the visitor
 * closed this version (frequency once / daily / always).
 */
use Modules\Situs\Services\HomePopup;

$preview = service('request')->getGet('popup') === 'pratinjau' && HomePopup::canManage();
$popup   = HomePopup::forPage($preview);

if ($popup === null) {
    return;
}
?>
<dialog id="popup-beranda" class="popup-beranda" aria-labelledby="popup-beranda-judul" data-popup
        data-popup-versi="<?= (int) $popup['version'] ?>" data-popup-frekuensi="<?= esc($popup['frequency'], 'attr') ?>"<?= $popup['preview'] ? ' data-popup-pratinjau' : '' ?>>
    <div class="flex max-h-[inherit] flex-col">
        <div class="flex items-start justify-between gap-3 border-b border-gray-200 p-4 sm:px-5 dark:border-gray-700">
            <h2 id="popup-beranda-judul" class="pt-1.5 text-lg font-semibold text-gray-900 sm:text-xl dark:text-white"><bdi><?= esc($popup['title'] !== '' ? $popup['title'] : (string) setting('School.name')) ?></bdi></h2>
            <button type="button" data-popup-tutup class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                <?= icon('x', 'h-5 w-5') ?><span class="sr-only"><?= lang('Situs.popupCloseLabel') ?></span>
            </button>
        </div>
        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4 sm:p-5">
            <?php if ($popup['preview']) : ?>
                <p class="flex items-start gap-2 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-gray-700 dark:text-yellow-300"><?= icon('eye', 'mt-0.5 h-4 w-4 shrink-0') ?><?= lang('Situs.popupPreview') ?></p>
            <?php endif ?>
            <?php if ($popup['image'] !== null) : ?>
                <img src="<?= esc($popup['image'], 'attr') ?>" alt="" class="mx-auto h-auto max-h-[50vh] w-auto max-w-full rounded-lg object-contain">
            <?php endif ?>
            <?php if ($popup['html'] !== '') : ?>
                <div class="popup-isi space-y-3 text-base leading-relaxed text-gray-700 dark:text-gray-300" dir="auto"><?= $popup['html'] ?></div>
            <?php endif ?>
        </div>
        <div class="flex flex-col-reverse gap-2 border-t border-gray-200 p-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end sm:px-5 dark:border-gray-700">
            <button type="button" data-popup-tutup class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700"><?= lang('Situs.popupClose') ?></button>
            <?php if ($popup['attachment'] !== null) : ?>
                <a href="<?= esc($popup['attachment']['url'], 'attr') ?>" download="<?= esc($popup['attachment']['name'], 'attr') ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-primary-600 px-5 py-2.5 text-sm font-medium text-primary-700 hover:bg-primary-50 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-primary-500 dark:text-primary-300 dark:hover:bg-gray-700 dark:focus:ring-primary-800">
                    <?= icon('download', 'h-4 w-4') ?><?= lang('Situs.popupDownload') ?>
                </a>
            <?php endif ?>
            <?php if ($popup['button'] !== null) : ?>
                <a href="<?= esc($popup['button']['url'], 'attr') ?>"<?= $popup['button']['external'] ? ' target="_blank" rel="noopener"' : '' ?> data-popup-tombol class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                    <?= esc($popup['button']['label']) ?><?= icon($popup['button']['external'] ? 'external-link' : 'arrow-right', 'h-4 w-4') ?>
                    <?php if ($popup['button']['external']) : ?><span class="sr-only"><?= lang('Situs.opensNewTab') ?></span><?php endif ?>
                </a>
            <?php endif ?>
        </div>
    </div>
</dialog>
