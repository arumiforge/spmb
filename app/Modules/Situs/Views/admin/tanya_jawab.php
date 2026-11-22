<?php

use Modules\Situs\Models\SiteQuestionModel;

/**
 * Admin list of questions for /tanya-jawab, grouped by topic (D-072).
 *
 * @var list<array{topic: string, items: list<array<string, mixed>>}> $groups
 * @var int                                                          $incomplete
 */
$card    = 'rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800';
$btn     = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$iconBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$lastTopic = count($groups) - 1;
$preview   = static function (string $markdown): string {
    $text = trim((string) preg_replace(['/\[([^\]]*)\]\([^)]*\)/', '/[*_`#>]+/', '/\s+/u'], ['$1', '', ' '], $markdown));

    return mb_strlen($text) > 160 ? rtrim(mb_substr($text, 0, 157)) . '…' : $text;
};
$move = static function (string $action, array $hidden, string $direction, bool $disabled, string $label) use ($iconBtn): string {
    $fields = csrf_field();

    foreach ($hidden + ['arah' => $direction] as $name => $value) {
        $fields .= '<input type="hidden" name="' . esc($name, 'attr') . '" value="' . esc($value, 'attr') . '">';
    }

    return '<form method="post" action="' . esc($action, 'attr') . '">' . $fields
        . '<button type="submit" class="' . $iconBtn . '"' . ($disabled ? ' disabled' : '') . ' title="' . esc($label, 'attr') . '">'
        . '<span class="sr-only">' . esc($label) . '</span>' . icon('chevron-down', 'h-4 w-4' . ($direction === 'naik' ? ' rotate-180' : '')) . '</button></form>';
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<div class="flex flex-wrap gap-2">
    <a href="<?= url_to('situs.tanya-jawab') ?>" target="_blank" rel="noopener" class="<?= $btn ?> bg-white dark:bg-gray-800"><?= icon('eye', 'h-4 w-4') ?><?= lang('Situs.qaViewPage') ?></a>
    <a href="<?= url_to('situs.tanya-jawab.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
        <?= icon('plus', 'h-4 w-4') ?><?= lang('Situs.qaAdd') ?>
    </a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($groups === []) : ?>
    <?= view('partials/empty_state', ['empty' => ['title' => lang('Situs.qaAdminEmptyTitle'), 'text' => lang('Situs.qaAdminEmptyText'), 'icon' => 'message-circle-question-mark', 'image' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData'), 'action' => ['label' => lang('Situs.qaAdd'), 'url' => 'admin/tanya-jawab/tambah']]]) ?>
<?php else : ?>
    <div class="space-y-6">
        <?php if ($incomplete > 0) : ?>
            <div class="flex items-start gap-3 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-gray-800 dark:text-yellow-300" role="status">
                <?= icon('warning', 'mt-0.5 h-5 w-5 shrink-0') ?>
                <p><?= esc(lang('Situs.qaIncomplete', [$incomplete])) ?></p>
            </div>
        <?php endif ?>

        <?php foreach ($groups as $t => $group) : ?>
            <?php $last = count($group['items']) - 1; $headingId = 'topik-' . $t; ?>
            <section class="<?= $card ?>" aria-labelledby="<?= $headingId ?>">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-gray-700">
                    <div class="min-w-0">
                        <h2 id="<?= $headingId ?>" class="text-lg font-semibold text-gray-900 dark:text-white"><?= esc($group['topic']) ?></h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc(lang('Situs.qaQuestionCount', [count($group['items'])])) ?></p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <div class="flex gap-1">
                            <?= $move(url_to('situs.tanya-jawab.topik-pindah'), ['topik' => $group['topic']], 'naik', $t === 0, lang('Situs.qaTopicMoveUp') . ': ' . $group['topic']) ?>
                            <?= $move(url_to('situs.tanya-jawab.topik-pindah'), ['topik' => $group['topic']], 'turun', $t === $lastTopic, lang('Situs.qaTopicMoveDown') . ': ' . $group['topic']) ?>
                        </div>
                        <a href="<?= esc(url_to('situs.tanya-jawab.tambah') . '?topik=' . rawurlencode($group['topic']), 'attr') ?>" class="<?= $btn ?>"><?= icon('plus', 'h-4 w-4') ?><?= lang('Situs.qaAddToTopic') ?></a>
                    </div>
                </div>
                <ol class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($group['items'] as $i => $item) : ?>
                        <?php $shown = (bool) $item['is_published']; $todo = SiteQuestionModel::needsSchoolInput($item); ?>
                        <li id="pertanyaan-<?= (int) $item['id'] ?>" class="flex scroll-mt-24 flex-col gap-3 p-4 sm:px-6 lg:flex-row lg:items-center">
                            <div class="order-last flex shrink-0 gap-1 lg:order-first">
                                <?= $move(url_to('situs.tanya-jawab.pindah', $item['id']), [], 'naik', $i === 0, lang('Situs.qaMoveUp') . ': ' . $item['question']) ?>
                                <?= $move(url_to('situs.tanya-jawab.pindah', $item['id']), [], 'turun', $i === $last, lang('Situs.qaMoveDown') . ': ' . $item['question']) ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 dark:text-white"><?= esc($item['question']) ?></p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300"><?= esc($preview((string) $item['answer'])) ?></p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    <?= view('partials/status_badge', ['badge' => $shown ? ['label' => lang('Situs.qaBadgeShown'), 'color' => 'green'] : ['label' => lang('Situs.qaBadgeHidden'), 'color' => 'gray']]) ?>
                                    <?php if ($todo) : ?>
                                        <?= view('partials/status_badge', ['badge' => ['label' => lang('Situs.qaBadgeIncomplete'), 'color' => 'yellow']]) ?>
                                    <?php endif ?>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2 lg:order-last">
                                <a href="<?= url_to('situs.tanya-jawab.ubah', $item['id']) ?>" class="<?= $btn ?>"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Situs.qaEdit') ?></a>
                                <?php if ($shown || ! $todo) : ?>
                                    <form method="post" action="<?= url_to('situs.tanya-jawab.tampilkan', $item['id']) ?>">
                                        <?= csrf_field() ?><input type="hidden" name="tampil" value="<?= $shown ? '0' : '1' ?>">
                                        <button type="submit" class="<?= $btn ?>"><?= icon($shown ? 'eye-off' : 'eye', 'h-4 w-4') ?><?= lang($shown ? 'Situs.qaHide' : 'Situs.qaShow') ?></button>
                                    </form>
                                <?php endif ?>
                                <form method="post" action="<?= url_to('situs.tanya-jawab.hapus', $item['id']) ?>" data-confirm="<?= esc(lang('Situs.qaDeleteConfirm', [$item['question']]), 'attr') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="<?= $btn ?> text-red-700 dark:text-red-400"><?= icon('x', 'h-4 w-4') ?><?= lang('Situs.qaDelete') ?></button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach ?>
                </ol>
            </section>
        <?php endforeach ?>
    </div>
<?php endif ?>
<?= $this->endSection() ?>
