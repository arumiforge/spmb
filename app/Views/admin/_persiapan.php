<?php
/**
 * "Persiapan sistem" card on the dashboard (D-099): what is still missing before
 * the system runs fully. Folds closed by itself once every step is done.
 *
 * @var list<array{key: string, group: string, title: string, hint: string, url: string, done: bool, required: bool}> $setup
 */
use App\Libraries\SetupChecklist;

$progress = SetupChecklist::progress($setup);
$next     = null;

foreach ($setup as $step) {
    if (! $step['done'] && ($next === null || ($step['required'] && ! $next['required']))) {
        $next = $step;

        if ($step['required']) {
            break;
        }
    }
}

$groups = [];

foreach ($setup as $step) {
    $groups[$step['group']][] = $step;
}

$allDone = $progress['done'] === $progress['total'];
$badge   = view('partials/status_badge', ['badge' => $progress['requiredLeft'] > 0
    ? ['label' => $progress['requiredLeft'] . ' langkah wajib tersisa', 'color' => 'yellow']
    : ['label' => $allDone ? 'Semua siap' : 'Siap dipakai', 'color' => 'green']], ['saveData' => false]);
?>
<?= lipatan_buka([
    'id'    => 'persiapan',
    'title' => 'Persiapan sistem',
    'intro' => $progress['done'] . ' dari ' . $progress['total'] . ' langkah selesai',
    'icon'  => 'list-checks',
    'badge' => $badge,
    'open'  => ! $allDone,
    'class' => 'persiapan mt-6 motion-rise',
]) ?>
    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700" role="progressbar" aria-valuenow="<?= $progress['percent'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Persiapan sistem">
        <div class="h-2 rounded-full bg-primary-600 transition-[width] motion-reduce:transition-none" style="width: <?= $progress['percent'] ?>%"></div>
    </div>

    <?php if ($next !== null) : ?>
        <div class="mt-5 flex flex-col gap-3 rounded-lg border border-primary-200 bg-primary-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-primary-900 dark:bg-gray-700/50">
            <div class="flex items-start gap-3">
                <?= icon('rocket', 'mt-0.5 h-5 w-5 shrink-0 text-primary-700 dark:text-primary-300') ?>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Berikutnya: <?= esc($next['title']) ?></p>
                    <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300"><?= esc($next['hint']) ?></p>
                </div>
            </div>
            <a href="<?= esc(site_url($next['url']), 'attr') ?>" class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">Kerjakan<?= icon('arrow-right', 'h-4 w-4') ?></a>
        </div>
    <?php else : ?>
        <p class="mt-5 flex items-center gap-2 text-sm text-green-700 dark:text-green-400"><?= icon('circle-check', 'h-5 w-5') ?>Semua langkah sudah selesai. Sistem siap dipakai sepenuhnya.</p>
    <?php endif ?>

    <div class="mt-5 grid grid-cols-1 gap-x-8 gap-y-6 md:grid-cols-2 2xl:grid-cols-3">
        <?php foreach ($groups as $group => $steps) : ?>
            <section>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><?= esc($group) ?></h3>
                <ol class="space-y-1">
                    <?php foreach ($steps as $step) : ?>
                        <li>
                            <a href="<?= esc(site_url($step['url']), 'attr') ?>" class="group/langkah flex items-start gap-3 rounded-lg p-2 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-gray-700/50" data-langkah="<?= esc($step['key'], 'attr') ?>" data-selesai="<?= $step['done'] ? '1' : '0' ?>">
                                <?php if ($step['done']) : ?>
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-600 text-white"><?= icon('circle-check', 'h-5 w-5') ?></span>
                                <?php else : ?>
                                    <span class="mt-0.5 h-5 w-5 shrink-0 rounded-full border-2 <?= $step['required'] ? 'border-yellow-500' : 'border-gray-300 dark:border-gray-500' ?>" aria-hidden="true"></span>
                                <?php endif ?>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium <?= $step['done'] ? 'text-gray-500 line-through decoration-gray-300 dark:text-gray-400 dark:decoration-gray-600' : 'text-gray-900 group-hover/langkah:text-primary-700 dark:text-white dark:group-hover/langkah:text-primary-300' ?>">
                                        <?= esc($step['title']) ?>
                                        <span class="sr-only"><?= $step['done'] ? '(selesai)' : ($step['required'] ? '(wajib, belum selesai)' : '(saran, belum selesai)') ?></span>
                                    </span>
                                    <?php if (! $step['done']) : ?>
                                        <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400"><?= esc($step['hint']) ?></span>
                                    <?php endif ?>
                                </span>
                                <?php if (! $step['done'] && ! $step['required']) : ?>
                                    <span class="shrink-0 rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300" aria-hidden="true">Saran</span>
                                <?php endif ?>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ol>
            </section>
        <?php endforeach ?>
    </div>
<?= lipatan_tutup() ?>
