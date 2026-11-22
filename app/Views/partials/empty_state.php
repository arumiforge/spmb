<?php
/**
 * Friendly empty state with one clear action.
 *
 * image: painted illustration path for illustration(), e.g. 'state/kosong-data' (preferred over icon).
 *
 * @var array{title: string, text?: string, icon?: string, image?: string, imageAlt?: string, action?: array{label: string, url: string}} $empty
 */
$picture = empty($empty['image']) ? '' : illustration($empty['image'], $empty['imageAlt'] ?? '', 'mx-auto h-32 w-auto sm:h-40');
?>
<div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-600 dark:bg-gray-800">
    <?php if ($picture !== '') : ?>
        <div class="mb-4"><?= $picture ?></div>
    <?php else : ?>
        <?= icon($empty['icon'] ?? 'inbox', 'mb-4 h-12 w-12 text-primary-600 dark:text-primary-400') ?>
    <?php endif ?>
    <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= esc($empty['title']) ?></h3>
    <?php if (! empty($empty['text'])) : ?>
        <p class="mb-5 max-w-md text-sm text-gray-500 dark:text-gray-400"><?= esc($empty['text']) ?></p>
    <?php endif ?>
    <?php if (! empty($empty['action'])) : ?>
        <a href="<?= esc(site_url($empty['action']['url']), 'attr') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            <?= icon('plus', 'h-4 w-4') ?><?= esc($empty['action']['label']) ?>
        </a>
    <?php endif ?>
</div>
