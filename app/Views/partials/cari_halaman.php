<?php
/**
 * Search inside the current page (D-097): filters the parts of one container while the visitor
 * types, highlights the words and says how many parts match. Hidden until resources/js/ui.js
 * (initPageSearch) runs, because without JavaScript it could not do anything.
 *
 * @var array{
 *     target: string,      CSS selector of the container to search
 *     kind: string,        'bagian' (every <h2> with what follows) or 'butir' (every <details>)
 *     id: string,          id of the input
 *     label: string,
 *     placeholder: string,
 *     found: string,       status text with {n} for the number of matches
 *     none: string,        status text when nothing matches
 *     class?: string
 * } $search
 */
?>
<div class="<?= esc($search['class'] ?? '') ?>" role="search" data-cari-halaman data-cari-wadah="<?= esc($search['target'], 'attr') ?>" data-cari-jenis="<?= esc($search['kind'], 'attr') ?>"
     data-cari-ketemu="<?= esc($search['found'], 'attr') ?>" data-cari-kosong="<?= esc($search['none'], 'attr') ?>" hidden>
    <label for="<?= esc($search['id'], 'attr') ?>" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= esc($search['label']) ?></label>
    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-5 w-5') ?></span>
        <input type="search" id="<?= esc($search['id'], 'attr') ?>" autocomplete="off" enterkeyhint="search" placeholder="<?= esc($search['placeholder'], 'attr') ?>"
               class="block w-full rounded-lg border border-gray-300 bg-white py-3 ps-11 pe-11 text-base text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 [&::-webkit-search-cancel-button]:hidden">
        <button type="button" class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" data-cari-hapus hidden>
            <?= icon('x', 'h-5 w-5') ?><span class="sr-only"><?= lang('App.searchClear') ?></span>
        </button>
    </div>
    <p class="mt-2 min-h-5 text-sm text-gray-600 dark:text-gray-400" data-cari-status aria-live="polite"></p>
</div>
