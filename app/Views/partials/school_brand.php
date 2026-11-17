<?php
/**
 * School logo + name (white label).
 *
 * @var array{size?: string, class?: string, href?: string} $brand
 */
$brand ??= [];
$logo  = school_logo_url();
$size  = $brand['size'] ?? 'h-8 w-8';
?>
<a href="<?= esc($brand['href'] ?? base_url('/'), 'attr') ?>" class="flex min-w-0 items-center gap-2 <?= esc($brand['class'] ?? '', 'attr') ?>">
    <?php if ($logo !== null) : ?>
        <img src="<?= esc($logo, 'attr') ?>" class="<?= esc($size, 'attr') ?> shrink-0 object-contain" alt="Logo <?= esc(setting('School.name'), 'attr') ?>">
    <?php else : ?>
        <span class="<?= esc($size, 'attr') ?> flex shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white"><?= icon('school', 'h-5 w-5') ?></span>
    <?php endif ?>
    <span class="truncate text-lg font-semibold text-gray-900 dark:text-white"><?= esc(setting('School.shortName') ?: setting('School.name')) ?></span>
</a>
