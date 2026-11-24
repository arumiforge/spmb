<?php
/**
 * Admin list of the footer's "Tautan" column (FooterLinks). Links are addressed by position (1-based).
 *
 * @var list<array{label: string, url: string, active: bool, available: bool}> $links
 * @var bool                                                  $custom False while the default list is used
 * @var bool                                                  $full   No more links can be added
 */
$card    = 'rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800';
$btn     = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$iconBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$last    = count($links) - 1;
$move    = static function (int $position, string $direction, bool $disabled, string $label) use ($iconBtn): string {
    return '<form method="post" action="' . esc(url_to('situs.tautan.pindah', $position), 'attr') . '">' . csrf_field()
        . '<input type="hidden" name="arah" value="' . $direction . '">'
        . '<button type="submit" class="' . $iconBtn . '"' . ($disabled ? ' disabled' : '') . ' title="' . esc($label, 'attr') . '">'
        . '<span class="sr-only">' . esc($label) . '</span>' . icon('chevron-down', 'h-4 w-4' . ($direction === 'naik' ? ' rotate-180' : '')) . '</button></form>';
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<div class="flex flex-wrap gap-2">
    <?php if ($custom) : ?>
        <form method="post" action="<?= url_to('situs.tautan.kembalikan') ?>" data-confirm="<?= esc(lang('Situs.linksResetConfirm'), 'attr') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="<?= $btn ?> bg-white dark:bg-gray-800"><?= icon('undo', 'h-4 w-4') ?><?= lang('Situs.linksResetButton') ?></button>
        </form>
    <?php endif ?>
    <?php if (! $full) : ?>
        <a href="<?= url_to('situs.tautan.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            <?= icon('plus', 'h-4 w-4') ?><?= lang('Situs.linksAdd') ?>
        </a>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <?php if (! $custom) : ?>
        <div class="flex items-start gap-3 rounded-lg bg-gray-100 p-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300" role="status">
            <?= icon('info', 'mt-0.5 h-5 w-5 shrink-0') ?><p><?= lang('Situs.linksDefaultNote') ?></p>
        </div>
    <?php endif ?>

    <?php if ($links === []) : ?>
        <?= view('partials/empty_state', ['empty' => ['title' => lang('Situs.linksEmptyTitle'), 'text' => lang('Situs.linksEmptyText'), 'icon' => 'link', 'image' => 'state/kosong-tautan', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData'), 'action' => ['label' => lang('Situs.linksAdd'), 'url' => 'admin/tautan-footer/tambah']]]) ?>
    <?php else : ?>
        <section class="<?= $card ?>" aria-label="<?= esc(lang('Situs.linksAdminTitle'), 'attr') ?>">
            <ol class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($links as $i => $link) : ?>
                    <?php $position = $i + 1; ?>
                    <li id="tautan-<?= $position ?>" class="flex scroll-mt-24 flex-col gap-3 p-4 sm:px-6 lg:flex-row lg:items-center">
                        <div class="order-last flex shrink-0 gap-1 lg:order-first">
                            <?= $move($position, 'naik', $i === 0, lang('Situs.moveUp') . ': ' . $link['label']) ?>
                            <?= $move($position, 'turun', $i === $last, lang('Situs.moveDown') . ': ' . $link['label']) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 dark:text-white"><?= esc($link['label']) ?></p>
                            <p class="mt-1 flex items-center gap-1.5 break-all text-sm text-gray-600 dark:text-gray-300"><?= icon('link', 'h-4 w-4 shrink-0') ?><?= esc($link['url']) ?></p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <?php if (! $link['active']) : ?>
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('Situs.badgeHidden'), 'color' => 'gray']]) ?>
                                <?php elseif ($link['available']) : ?>
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('Situs.badgeShown'), 'color' => 'green']]) ?>
                                <?php else : ?>
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('Situs.linkBadgeUnavailable'), 'color' => 'yellow']]) ?>
                                    <span class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Situs.linkUnavailableHelp') ?></span>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2 lg:order-last">
                            <a href="<?= url_to('situs.tautan.ubah', $position) ?>" class="<?= $btn ?>"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Situs.edit') ?></a>
                            <form method="post" action="<?= url_to('situs.tautan.tampilkan', $position) ?>">
                                <?= csrf_field() ?><input type="hidden" name="tampil" value="<?= $link['active'] ? '0' : '1' ?>">
                                <button type="submit" class="<?= $btn ?>"><?= icon($link['active'] ? 'eye-off' : 'eye', 'h-4 w-4') ?><?= lang($link['active'] ? 'Situs.hide' : 'Situs.show') ?></button>
                            </form>
                            <form method="post" action="<?= url_to('situs.tautan.hapus', $position) ?>" data-confirm="<?= esc(lang('Situs.linkDeleteConfirm', [$link['label']]), 'attr') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="<?= $btn ?> text-red-700 dark:text-red-400"><?= icon('trash', 'h-4 w-4') ?><?= lang('Situs.delete') ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ol>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
