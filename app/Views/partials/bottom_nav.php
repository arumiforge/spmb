<?php
/**
 * Mobile bottom bar (D-081): up to five items, the middle one a raised round button without text.
 * Hidden from md up. Layouts add bottom padding (pb-24 md:pb-0) so content is never covered.
 *
 * @var array{label: string, items: list<array{label: string, url?: string, icon: string, active?: bool, center?: bool, art?: string|null, attrs?: string}>} $bottomNav
 */
$items = $bottomNav['items'] ?? [];
?>
<?php if ($items !== []) : ?>
<nav class="nav-bawah md:hidden" aria-label="<?= esc($bottomNav['label'], 'attr') ?>" data-no-print>
    <ul class="nav-bawah-daftar" style="--nav-bawah-kolom: <?= count($items) ?>">
        <?php foreach ($items as $item) : ?>
            <?php
            $active = ! empty($item['active']);
            $tag    = isset($item['url']) ? 'a' : 'button';
            $href   = isset($item['url']) ? ' href="' . esc(site_url($item['url']), 'attr') . '"' : ' type="button"';
            ?>
            <li class="<?= ! empty($item['center']) ? 'nav-bawah-tengah-slot' : '' ?>">
                <<?= $tag ?><?= $href ?> <?= $item['attrs'] ?? '' ?> <?= $active ? 'aria-current="page"' : '' ?>
                    <?= ! empty($item['center']) ? 'title="' . esc($item['label'], 'attr') . '"' : '' ?>
                    class="<?= ! empty($item['center']) ? 'nav-bawah-tengah' : 'nav-bawah-item' ?><?= $active ? ' is-aktif' : '' ?>">
                    <?php if (! empty($item['center'])) : ?>
                        <?php // The raised middle button shows no text (user, 2026-11-23); its name is read by screen readers. ?>
                        <?php $art = empty($item['art']) ? '' : illustration($item['art'], '', 'h-full w-full object-contain', false); ?>
                        <span class="nav-bawah-tengah-bulat<?= $art !== '' ? ' ada-seni' : '' ?>" aria-hidden="true"><?= $art !== '' ? $art : icon($item['icon'], 'h-8 w-8') ?></span>
                        <span class="sr-only"><?= esc($item['label']) ?></span>
                    <?php else : ?>
                        <?= icon($item['icon'], 'h-6 w-6') ?>
                        <span class="nav-bawah-label"><?= esc($item['label']) ?></span>
                    <?php endif ?>
                </<?= $tag ?>>
            </li>
        <?php endforeach ?>
    </ul>
</nav>
<?php endif ?>
