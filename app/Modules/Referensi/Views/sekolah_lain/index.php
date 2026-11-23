<?php
/**
 * School names typed by applicants that are not linked to the list yet.
 *
 * @var list<array{name: string, total: int|string}> $groups
 * @var int                                          $groupTotal
 * @var string                                       $links
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php if ($groups === []) : ?>
    <?= view('partials/empty_state', ['empty' => ['title' => lang('Referensi.othersEmptyTitle'), 'text' => lang('Referensi.othersEmptyText'), 'icon' => 'school', 'image' => 'state/kosong-sekolah-lain', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData'), 'action' => ['label' => lang('Referensi.listTitle'), 'url' => url_to('referensi.sekolah')]]]) ?>
<?php else : ?>
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            <?php foreach ($groups as $group) : ?>
                <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="break-words font-medium text-gray-900 dark:text-white"><?= esc($group['name']) ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.othersCount', [(int) $group['total']]) ?></p>
                    </div>
                    <a href="<?= url_to('referensi.lain.petakan') . '?' . http_build_query(['nama' => $group['name']]) ?>" class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                        <?= lang('Referensi.othersMap') ?><span class="sr-only"> <?= esc($group['name']) ?></span><?= icon('arrow-right', 'h-4 w-4') ?>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 p-4 sm:flex-row dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.total', [$groupTotal]) ?></p>
            <?= $links ?>
        </div>
    </div>
<?php endif ?>
<?= $this->endSection() ?>
