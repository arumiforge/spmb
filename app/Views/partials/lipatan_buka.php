<?php
/**
 * Opens a folding card (D-100): a native <details> that opens and closes without
 * JavaScript; resources/js/lipatan.js remembers its state and opens it when it
 * holds an error. Always render it through the helpers (they keep `title` out of
 * the page's view data):
 *
 *   <?= lipatan_buka(['id' => 'kontak', 'title' => 'Kontak', 'intro' => '…', 'icon' => 'phone']) ?>
 *       …content…
 *   <?= lipatan_tutup() ?>
 *
 * @var string      $id      Unique on the page; used to remember the state
 * @var string      $title
 * @var string|null $intro   One line under the title
 * @var string|null $icon    Lucide icon name
 * @var string|null $badge   Ready-made HTML shown at the right of the header (e.g. a status badge)
 * @var bool|null   $open    Open by default (true)
 * @var string|null $class   Extra classes for the card (e.g. grid spans)
 * @var string|null $heading Heading tag (h2 by default)
 */
$open    ??= true;
$heading ??= 'h2';
$heading   = in_array($heading, ['h2', 'h3'], true) ? $heading : 'h2';
?>
<details class="lipatan group break-inside-avoid rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 <?= esc($class ?? '', 'attr') ?>" data-lipatan="<?= esc($id, 'attr') ?>"<?= $open ? ' open' : '' ?>>
    <summary class="flex cursor-pointer list-none items-start gap-3 rounded-lg p-5 hover:bg-gray-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-300 sm:p-6 dark:hover:bg-gray-700/40 dark:focus-visible:ring-primary-800 [&::-webkit-details-marker]:hidden">
        <?php if (! empty($icon)) : ?>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-primary-300" aria-hidden="true"><?= icon($icon, 'h-5 w-5') ?></span>
        <?php endif ?>
        <span class="min-w-0 flex-1">
            <<?= $heading ?> class="text-lg font-semibold text-gray-900 dark:text-white"><?= esc($title) ?></<?= $heading ?>>
            <?php if (! empty($intro)) : ?>
                <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400"><?= esc($intro) ?></span>
            <?php endif ?>
        </span>
        <?php if (! empty($badge)) : ?>
            <span class="shrink-0 self-center"><?= $badge ?></span>
        <?php endif ?>
        <span class="shrink-0 self-center text-gray-400 transition-transform group-open:rotate-180 motion-reduce:transition-none" aria-hidden="true"><?= icon('chevron-down', 'h-5 w-5') ?></span>
    </summary>
    <div class="px-5 pb-5 sm:px-6 sm:pb-6">
