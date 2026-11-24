<?php
/**
 * "Buka semua" / "Tutup semua" for the folding cards on a page (or inside the
 * nearest [data-lipatan-lingkup]). Hidden without JavaScript (the buttons need it).
 *
 * @var string|null $class
 */
$btn = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700';
?>
<div class="flex flex-wrap items-center justify-end gap-1 <?= esc($class ?? '', 'attr') ?>" data-lipatan-tombol hidden>
    <button type="button" class="<?= $btn ?>" data-lipatan-semua="buka"><?= icon('chevrons-down-up', 'h-4 w-4') ?><?= lang('App.foldOpenAll') ?></button>
    <button type="button" class="<?= $btn ?>" data-lipatan-semua="tutup"><?= icon('chevron-up', 'h-4 w-4') ?><?= lang('App.foldCloseAll') ?></button>
</div>
