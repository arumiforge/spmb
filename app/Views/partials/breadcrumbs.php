<?php
/**
 * Breadcrumb bar. The last item is the current page (not a link).
 * On small screens the middle items collapse into "…".
 *
 * @var list<array{label: string, url?: string}> $breadcrumbs
 */
$breadcrumbs ??= [];
$last = count($breadcrumbs) - 1;
?>
<?php if ($breadcrumbs !== []) : ?>
<nav class="flex min-w-0" aria-label="<?= lang('App.breadcrumb') ?>">
    <ol class="inline-flex min-w-0 items-center space-x-1 text-sm font-medium md:space-x-2">
        <?php foreach ($breadcrumbs as $i => $crumb) : ?>
            <?php $middle = $i > 0 && $i < $last; ?>
            <?php if ($middle && $i === 1) : ?>
                <li class="flex items-center md:hidden" aria-hidden="true">
                    <?= icon('chevron', 'mx-1 h-4 w-4 text-gray-400 rtl:rotate-180') ?>
                    <span class="text-gray-500 dark:text-gray-400">…</span>
                </li>
            <?php endif ?>
            <li class="<?= $middle ? 'hidden md:flex' : 'flex' ?> min-w-0 items-center" <?= $i === $last ? 'aria-current="page"' : '' ?>>
                <?php if ($i > 0) : ?>
                    <?= icon('chevron', 'mx-1 h-4 w-4 shrink-0 text-gray-400 rtl:rotate-180') ?>
                <?php endif ?>
                <?php if ($i !== $last && ! empty($crumb['url'])) : ?>
                    <a href="<?= esc(site_url($crumb['url']), 'attr') ?>" class="inline-flex items-center gap-1.5 truncate text-gray-700 hover:text-primary-600 dark:text-gray-400 dark:hover:text-white">
                        <?php if ($i === 0) : ?><?= icon('home', 'h-4 w-4 shrink-0') ?><?php endif ?>
                        <?= esc($crumb['label']) ?>
                    </a>
                <?php else : ?>
                    <span class="inline-flex items-center gap-1.5 truncate text-gray-500 dark:text-gray-400">
                        <?php if ($i === 0) : ?><?= icon('home', 'h-4 w-4 shrink-0') ?><?php endif ?>
                        <?= esc($crumb['label']) ?>
                    </span>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ol>
</nav>
<?php endif ?>
