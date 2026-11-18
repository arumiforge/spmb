<?php
/**
 * School logo + name (white label).
 *
 * With 'wide' => true and a landscape logo set in settings, the landscape logo
 * (and its dark-mode version) replaces logo + name.
 *
 * @var array{size?: string, class?: string, href?: string, wide?: bool, wideSize?: string} $brand
 */
$brand    ??= [];
$logo     = school_logo_url();
$size     = $brand['size'] ?? 'h-8 w-8';
$wideSize = $brand['wideSize'] ?? 'h-10';
$wide     = ($brand['wide'] ?? false) ? brand_asset_url('logoWide') : null;
$wideDark = $wide !== null ? (brand_asset_url('logoWideDark') ?? $wide) : null;
$name     = (string) setting('School.name');
?>
<a href="<?= esc($brand['href'] ?? base_url('/'), 'attr') ?>" class="flex min-w-0 items-center gap-2 <?= esc($brand['class'] ?? '', 'attr') ?>">
    <?php if ($wide !== null) : ?>
        <img src="<?= esc($wide, 'attr') ?>" class="<?= esc($wideSize, 'attr') ?> w-auto max-w-[70vw] shrink object-contain dark:hidden" alt="<?= esc($name, 'attr') ?>">
        <img src="<?= esc($wideDark, 'attr') ?>" class="<?= esc($wideSize, 'attr') ?> hidden w-auto max-w-[70vw] shrink object-contain dark:block" alt="<?= esc($name, 'attr') ?>">
    <?php else : ?>
        <?php if ($logo !== null) : ?>
            <img src="<?= esc($logo, 'attr') ?>" class="<?= esc($size, 'attr') ?> shrink-0 object-contain" alt="Logo <?= esc($name, 'attr') ?>">
        <?php else : ?>
            <span class="<?= esc($size, 'attr') ?> flex shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white"><?= icon('school', 'h-5 w-5') ?></span>
        <?php endif ?>
        <span class="truncate text-lg font-semibold text-gray-900 dark:text-white"><?= esc(setting('School.shortName') ?: $name) ?></span>
    <?php endif ?>
</a>
