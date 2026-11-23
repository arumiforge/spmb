<?php
/**
 * @var array<string, mixed>                                                            $row
 * @var Modules\Riwayat\Libraries\AuditPresenter                                        $presenter
 * @var list<array{field: string, label: string, old: string|null, new: string|null}> $changes
 * @var array<int, string>                                                              $userNames
 */
$who        = $row['user_id'] === null ? lang('Riwayat.system') : ($userNames[(int) $row['user_id']] ?? lang('Riwayat.deletedUser', [$row['user_id']]));
$hasOld     = array_filter($changes, static fn (array $c): bool => $c['old'] !== null) !== [];
$hasNew     = array_filter($changes, static fn (array $c): bool => $c['new'] !== null) !== [];
$hasSubject = $row['subject_type'] !== null && $row['subject_id'] !== null;
$subjectUrl = $row['subject_type'] === 'users' && isset($userNames[(int) $row['subject_id']]) && auth()->user()->can('pengguna.edit')
    ? url_to('pengguna.ubah', (int) $row['subject_id'])
    : null;
$dt = 'text-sm text-gray-500 dark:text-gray-400';
$dd = 'mt-0.5 break-words text-sm font-medium text-gray-900 dark:text-white';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= url_to('riwayat') ?>" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.back') ?></a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <section class="min-w-0 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="yang-berubah">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 id="yang-berubah" class="text-base font-semibold text-gray-900 dark:text-white"><?= lang('Riwayat.changes') ?></h2>
            <?= view('partials/status_badge', ['badge' => ['label' => $presenter->eventLabel((string) $row['event']), 'color' => $presenter->eventColor((string) $row['event'])]]) ?>
        </div>

        <?php if ($changes === []) : ?>
            <p class="p-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Riwayat.noChanges') ?></p>
        <?php else : ?>
            <!-- Table on md and up -->
            <div class="relative hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-4 py-3"><?= lang('Riwayat.field') ?></th>
                            <?php if ($hasOld) : ?><th scope="col" class="px-4 py-3"><?= lang('Riwayat.before') ?></th><?php endif ?>
                            <?php if ($hasNew) : ?><th scope="col" class="px-4 py-3"><?= lang('Riwayat.after') ?></th><?php endif ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($changes as $c) : ?>
                            <tr class="border-b border-gray-100 align-top last:border-0 dark:border-gray-700">
                                <th scope="row" class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= esc($c['label']) ?></th>
                                <?php if ($hasOld) : ?><td class="break-words px-4 py-3"><?= $c['old'] === null ? '' : '<span class="rounded bg-red-50 px-1.5 py-0.5 text-red-800 line-through decoration-red-300 dark:bg-red-900/40 dark:text-red-300">' . esc($c['old']) . '</span>' ?></td><?php endif ?>
                                <?php if ($hasNew) : ?><td class="break-words px-4 py-3"><?= $c['new'] === null ? '' : '<span class="rounded bg-green-50 px-1.5 py-0.5 text-green-800 dark:bg-green-900/40 dark:text-green-300">' . esc($c['new']) . '</span>' ?></td><?php endif ?>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>

            <!-- Stacked cards below md -->
            <ul class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
                <?php foreach ($changes as $c) : ?>
                    <li class="p-4">
                        <p class="font-medium text-gray-900 dark:text-white"><?= esc($c['label']) ?></p>
                        <dl class="mt-2 space-y-2 text-sm">
                            <?php if ($c['old'] !== null) : ?>
                                <div><dt class="text-xs text-gray-500 dark:text-gray-400"><?= lang('Riwayat.before') ?></dt><dd class="mt-0.5 break-words text-red-800 line-through decoration-red-300 dark:text-red-300"><?= esc($c['old']) ?></dd></div>
                            <?php endif ?>
                            <?php if ($c['new'] !== null) : ?>
                                <div><dt class="text-xs text-gray-500 dark:text-gray-400"><?= lang('Riwayat.after') ?></dt><dd class="mt-0.5 break-words text-green-800 dark:text-green-300"><?= esc($c['new']) ?></dd></div>
                            <?php endif ?>
                        </dl>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <aside class="space-y-6">
        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800" aria-labelledby="siapa-kapan">
            <h2 id="siapa-kapan" class="mb-3 text-base font-semibold text-gray-900 dark:text-white"><?= lang('Riwayat.whoWhen') ?></h2>
            <dl class="space-y-3">
                <div><dt class="<?= $dt ?>"><?= lang('Riwayat.colTime') ?></dt><dd class="<?= $dd ?>"><?= esc(tanggal_indo($row['created_at'], true)) ?></dd></div>
                <div><dt class="<?= $dt ?>"><?= lang('Riwayat.colUser') ?></dt><dd class="<?= $dd ?>"><?= esc($who) ?></dd></div>
                <div><dt class="<?= $dt ?>"><?= lang('Riwayat.colSubject') ?></dt><dd class="<?= $dd ?>"><?= esc($presenter->subjectName($row['subject_type'], $row['subject_id'], $userNames)) ?></dd></div>
                <?php if (! empty($row['ip_address'])) : ?>
                    <div><dt class="<?= $dt ?>"><?= lang('Riwayat.ipAddress') ?></dt><dd class="<?= $dd ?>"><?= esc($row['ip_address']) ?></dd></div>
                <?php endif ?>
                <?php if (! empty($row['user_agent'])) : ?>
                    <div><dt class="<?= $dt ?>"><?= lang('Riwayat.device') ?></dt><dd class="mt-0.5 break-words text-sm text-gray-700 dark:text-gray-300"><?= esc($row['user_agent']) ?></dd></div>
                <?php endif ?>
                <?php if (! empty($row['url'])) : ?>
                    <div><dt class="<?= $dt ?>"><?= lang('Riwayat.page') ?></dt><dd class="mt-0.5 break-all text-sm text-gray-700 dark:text-gray-300"><?= esc(parse_url((string) $row['url'], PHP_URL_PATH) ?: $row['url']) ?></dd></div>
                <?php endif ?>
            </dl>
            <?php if ($hasSubject) : ?>
                <div class="mt-4 flex flex-col gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">
                    <a href="<?= esc(url_to('riwayat') . '?' . http_build_query(['data' => $row['subject_type'], 'id' => $row['subject_id']]), 'attr') ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= icon('clock', 'h-4 w-4') ?><?= lang('Riwayat.subjectHistory') ?></a>
                    <?php if ($subjectUrl !== null) : ?>
                        <a href="<?= esc($subjectUrl, 'attr') ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= icon('arrow-right', 'h-4 w-4') ?><?= lang('Riwayat.openSubject') ?></a>
                    <?php endif ?>
                </div>
            <?php endif ?>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
