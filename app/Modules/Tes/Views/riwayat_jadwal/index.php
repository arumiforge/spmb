<?php

use CodeIgniter\Pager\Pager;

/**
 * Schedule change log with filters (query string, shareable).
 *
 * @var list<array<string, mixed>> $rows     TestScheduleChangeModel::detailed()
 * @var Pager                      $pager
 * @var array<string, string>      $filters
 * @var array<string, string>      $kinds    code => label
 */
$input    = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$filtered = array_filter($filters, static fn (string $v): bool => $v !== '') !== [];
$colors   = ['allocated' => 'blue', 'assigned' => 'blue', 'moved' => 'yellow', 'removed' => 'red'];
$kind     = static fn (array $r): string => view('partials/status_badge', ['badge' => ['label' => $kinds[$r['kind']] ?? $r['kind'], 'color' => $colors[$r['kind']] ?? 'gray']]);
$by       = static fn (array $r): string => $r['changed_by'] === null ? lang('Tes.system') : (string) ($r['staff_name'] ?? lang('Tes.system'));
$change   = static fn (array $r): string => view('Modules\Tes\Views\riwayat_jadwal\change', ['change' => $r], ['saveData' => false]);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <form method="get" action="<?= url_to('tes.riwayat-jadwal') ?>" class="grid grid-cols-1 gap-3 border-b border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))_auto] dark:border-gray-700" role="search">
        <div class="relative sm:col-span-2 lg:col-span-4 2xl:col-span-1 2xl:self-end">
            <label for="q" class="sr-only"><?= lang('TesPenjadwalan.searchLabel') ?></label>
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-4 w-4') ?></span>
            <input type="search" id="q" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="<?= lang('TesPenjadwalan.searchLabel') ?>" class="<?= $input ?> ps-9">
        </div>
        <div>
            <label for="dari" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400"><?= lang('TesPenjadwalan.logFrom') ?></label>
            <input type="date" id="dari" name="dari" value="<?= esc($filters['dari'], 'attr') ?>" class="<?= $input ?>">
        </div>
        <div>
            <label for="sampai" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400"><?= lang('TesPenjadwalan.logTo') ?></label>
            <input type="date" id="sampai" name="sampai" value="<?= esc($filters['sampai'], 'attr') ?>" class="<?= $input ?>">
        </div>
        <div class="sm:self-end">
            <label for="jenis" class="sr-only"><?= lang('TesPenjadwalan.logKind') ?></label>
            <select id="jenis" name="jenis" class="<?= $input ?>">
                <option value=""><?= lang('TesPenjadwalan.logKind') ?></option>
                <?php foreach ($kinds as $code => $label) : ?>
                    <option value="<?= esc($code, 'attr') ?>" <?= $filters['jenis'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="flex items-center justify-end gap-3 sm:self-end">
            <?php if ($filtered) : ?>
                <a href="<?= url_to('tes.riwayat-jadwal') ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.reset') ?></a>
            <?php endif ?>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700"><?= lang('TesPenjadwalan.filter') ?></button>
        </div>
    </form>

    <?php if ($rows === []) : ?>
        <div class="p-4">
            <?= view('partials/empty_state', ['empty' => $filtered
                ? ['title' => lang('TesPenjadwalan.emptySearchTitle'), 'text' => lang('TesPenjadwalan.emptySearchText'), 'icon' => 'search', 'image' => 'state/kosong-pencarian', 'imageAlt' => lang('App.altNoResults')]
                : ['title' => lang('TesPenjadwalan.logEmptyTitle'), 'text' => lang('TesPenjadwalan.logEmptyText'), 'icon' => 'clock', 'image' => 'state/kosong-riwayat-jadwal', 'fallback' => 'state/kosong-riwayat', 'imageAlt' => lang('App.altNoData')],
            ]) ?>
        </div>
    <?php else : ?>
        <div class="relative hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colWhen') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colApplicant') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colKind') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colChange') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colReason') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colBy') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r) : ?>
                        <tr class="border-b border-gray-100 align-top last:border-0 dark:border-gray-700">
                            <td class="whitespace-nowrap px-4 py-3"><span class="text-gray-900 dark:text-white"><?= esc(tanggal_indo($r['created_at'])) ?></span><span class="block text-xs"><?= esc(date('H.i', strtotime((string) $r['created_at'])) . ' ' . setting('School.timezoneLabel')) ?></span></td>
                            <td class="px-4 py-3">
                                <a href="<?= url_to('pendaftar.detail', $r['applicant_id']) ?>" class="font-medium text-gray-900 hover:text-primary-700 hover:underline dark:text-white dark:hover:text-primary-400"><?= esc($r['full_name']) ?></a>
                                <p class="font-mono text-xs text-gray-500 dark:text-gray-400"><?= esc($r['registration_number']) ?></p>
                            </td>
                            <td class="px-4 py-3"><?= $kind($r) ?></td>
                            <td class="min-w-64 px-4 py-3"><?= $change($r) ?></td>
                            <td class="px-4 py-3"><?= esc($r['reason'] ?? lang('TesPenjadwalan.none')) ?></td>
                            <td class="px-4 py-3"><?= esc($by($r)) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-gray-100 lg:hidden dark:divide-gray-700">
            <?php foreach ($rows as $r) : ?>
                <li class="space-y-2 p-4 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="<?= url_to('pendaftar.detail', $r['applicant_id']) ?>" class="block truncate font-medium text-gray-900 hover:underline dark:text-white"><?= esc($r['full_name']) ?></a>
                            <p class="font-mono text-gray-600 dark:text-gray-300"><?= esc($r['registration_number']) ?></p>
                        </div>
                        <?= $kind($r) ?>
                    </div>
                    <div><?= $change($r) ?></div>
                    <?php if (! empty($r['reason'])) : ?>
                        <p class="text-gray-700 dark:text-gray-300">“<?= esc($r['reason']) ?>”</p>
                    <?php endif ?>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= esc(tanggal_indo($r['created_at'], true)) ?> · <?= esc($by($r)) ?></p>
                </li>
            <?php endforeach ?>
        </ul>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 p-4 sm:flex-row dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('TesPenjadwalan.logTotal', [number_format($pager->getTotal(), 0, ',', '.')]) ?></p>
            <?= $pager->links() ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
