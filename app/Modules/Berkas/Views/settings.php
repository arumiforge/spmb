<?php

use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\DocumentStorage;

/**
 * @var list<array<string, mixed>> $types
 * @var array<string, string>      $values
 */
$card   = 'rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800';
$h2     = 'text-lg font-semibold text-gray-900 dark:text-white';
$btn    = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$iconBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700';
$last   = count($types) - 1;
$badges = static function (array $type): string {
    $html = view('partials/status_badge', ['badge' => (bool) $type['is_required'] ? ['label' => lang('Berkas.badgeRequired'), 'color' => 'primary'] : ['label' => lang('Berkas.badgeOptional'), 'color' => 'gray']]);

    if ((bool) $type['may_follow']) {
        $html .= ' ' . view('partials/status_badge', ['badge' => ['label' => lang('Berkas.badgeMayFollow'), 'color' => 'yellow']]);
    }

    if (! (bool) $type['is_active']) {
        $html .= ' ' . view('partials/status_badge', ['badge' => ['label' => lang('Berkas.badgeInactive'), 'color' => 'gray']]);
    }

    if (DocumentTypeModel::hasTemplate($type)) {
        $html .= ' ' . view('partials/status_badge', ['badge' => (bool) $type['template_enabled'] ? ['label' => lang('Berkas.badgeTemplateOn'), 'color' => 'blue'] : ['label' => lang('Berkas.badgeTemplateOff'), 'color' => 'gray']]);
    }

    return $html;
};
$rules = static fn (array $type): string => lang('Berkas.rulesLine', [DocumentTypeModel::formatLabel($type), DocumentStorage::sizeLabel((int) $type['max_size_kb'])]);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= url_to('berkas.jenis.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
    <?= icon('plus', 'h-4 w-4') ?><?= lang('Berkas.addType') ?>
</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php // Two columns from xl: the list on the left, the photo settings beside it, so wide screens have no empty band. ?>
<div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
    <section class="<?= $card ?>" aria-labelledby="types-heading">
        <div class="border-b border-gray-200 p-4 sm:px-6 dark:border-gray-700">
            <h2 id="types-heading" class="<?= $h2 ?>"><?= lang('Berkas.typesHeading') ?></h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Berkas.typesHelp') ?></p>
        </div>

        <?php if ($types === []) : ?>
            <div class="p-4">
                <?= view('partials/empty_state', ['empty' => ['title' => lang('Berkas.typesEmptyTitle'), 'text' => lang('Berkas.typesEmptyText'), 'icon' => 'clipboard', 'image' => 'state/kosong-jenis-berkas', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData'), 'action' => ['label' => lang('Berkas.addType'), 'url' => 'admin/pengaturan/berkas/tambah']]]) ?>
            </div>
        <?php else : ?>
            <ol class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($types as $i => $type) : ?>
                    <li class="flex flex-col gap-3 p-4 sm:px-6 lg:flex-row lg:items-center xl:flex-col xl:items-stretch 2xl:flex-row 2xl:items-center">
                        <div class="order-last flex shrink-0 gap-1 lg:order-first xl:order-last 2xl:order-first" aria-label="<?= lang('Berkas.colOrder') ?>">
                            <form method="post" action="<?= url_to('berkas.jenis.pindah', $type['id']) ?>">
                                <?= csrf_field() ?><input type="hidden" name="arah" value="naik">
                                <button type="submit" class="<?= $iconBtn ?>" <?= $i === 0 ? 'disabled' : '' ?> title="<?= lang('Berkas.moveUp') ?>">
                                    <span class="sr-only"><?= lang('Berkas.moveUp') ?>: <?= esc($type['name']) ?></span><?= icon('chevron-down', 'h-4 w-4 rotate-180') ?>
                                </button>
                            </form>
                            <form method="post" action="<?= url_to('berkas.jenis.pindah', $type['id']) ?>">
                                <?= csrf_field() ?><input type="hidden" name="arah" value="turun">
                                <button type="submit" class="<?= $iconBtn ?>" <?= $i === $last ? 'disabled' : '' ?> title="<?= lang('Berkas.moveDown') ?>">
                                    <span class="sr-only"><?= lang('Berkas.moveDown') ?>: <?= esc($type['name']) ?></span><?= icon('chevron-down', 'h-4 w-4') ?>
                                </button>
                            </form>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 dark:text-white"><?= esc($type['name']) ?> <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(<?= esc($type['code']) ?>)</span></p>
                            <?php if (! empty($type['description'])) : ?>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"><?= esc($type['description']) ?></p>
                            <?php endif ?>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300"><?= esc($rules($type)) ?></p>
                            <div class="mt-2 flex flex-wrap gap-1"><?= $badges($type) ?></div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2 lg:order-last xl:order-none 2xl:order-last">
                            <a href="<?= url_to('berkas.jenis.ubah', $type['id']) ?>" class="<?= $btn ?>"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Berkas.edit') ?></a>
                            <form method="post" action="<?= url_to('berkas.jenis.hapus', $type['id']) ?>" data-confirm="<?= esc(lang('Berkas.deleteConfirm', [$type['name']]), 'attr') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="<?= $btn ?> text-red-700 dark:text-red-400"><?= icon('x', 'h-4 w-4') ?><?= lang('Berkas.delete') ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ol>
        <?php endif ?>
    </section>

    <form method="post" action="<?= url_to('berkas.pengaturan') ?>" class="<?= $card ?> p-5 sm:p-6" aria-labelledby="image-heading">
        <?= csrf_field() ?>
        <h2 id="image-heading" class="<?= $h2 ?>"><?= lang('Berkas.imageHeading') ?></h2>
        <p class="mb-4 mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Berkas.imageHelp') ?></p>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
            <?= view('partials/form_field', ['field' => ['name' => 'imageMaxEdge', 'type' => 'number', 'label' => lang('Berkas.imageMaxEdge'), 'value' => $values['imageMaxEdge'], 'required' => true, 'help' => lang('Berkas.imageMaxEdgeHelp'), 'attrs' => ['min' => '600', 'max' => '4000', 'step' => '1', 'inputmode' => 'numeric']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'imageQuality', 'type' => 'number', 'label' => lang('Berkas.imageQuality'), 'value' => $values['imageQuality'], 'required' => true, 'help' => lang('Berkas.imageQualityHelp'), 'attrs' => ['min' => '10', 'max' => '95', 'step' => '1', 'inputmode' => 'numeric']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'deadline', 'type' => 'date', 'label' => lang('Berkas.deadline'), 'value' => $values['deadline'], 'help' => lang('Berkas.deadlineHelp')]]) ?>
        </div>
        <div class="mt-6">
            <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= lang('Berkas.saveSettings') ?></button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
