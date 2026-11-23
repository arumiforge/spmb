<?php
/**
 * Mobile bottom bar (D-081): up to five items, the middle one raised and larger.
 * Hidden from md up. Layouts add bottom padding (pb-24 md:pb-0) so content is never covered.
 *
 * @var array{label: string, items: list<array{label: string, url?: string, icon: string, active?: bool, center?: bool, attrs?: string}>} $bottomNav
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
                    class="<?= ! empty($item['center']) ? 'nav-bawah-tengah' : 'nav-bawah-item' ?><?= $active ? ' is-aktif' : '' ?>">
                    <?php if (! empty($item['center'])) : ?>
                        <span class="nav-bawah-tengah-bulat" aria-hidden="true"><?= icon($item['icon'], 'h-7 w-7') ?></span>
                    <?php else : ?>
                        <?= icon($item['icon'], 'h-6 w-6') ?>
                    <?php endif ?>
                    <span class="nav-bawah-label"><?= esc($item['label']) ?></span>
                </<?= $tag ?>>
            </li>
        <?php endforeach ?>
    </ul>
</nav>
<?php endif ?>
