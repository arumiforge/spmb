<?php
/**
 * @var list<array<string, mixed>>   $rows
 * @var CodeIgniter\Pager\Pager      $pager
 * @var list<array<string, mixed>>   $types
 * @var list<array<string, mixed>>   $waves
 * @var array{q: string, jenis: int, gelombang: int, status: string} $filters
 * @var array<string, string>        $statusFilters
 * @var bool                         $filtered
 */
$input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$check = 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <form method="get" action="<?= url_to('berkas.antrean') ?>" class="grid grid-cols-1 gap-3 border-b border-gray-200 p-4 sm:grid-cols-2 xl:grid-cols-[1fr_12rem_12rem_12rem_auto] dark:border-gray-700" role="search">
        <div class="relative sm:col-span-2 xl:col-span-1">
            <label for="q" class="sr-only"><?= lang('Berkas.searchLabel') ?></label>
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-4 w-4') ?></span>
            <input type="search" id="q" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="<?= lang('Berkas.searchLabel') ?>" class="<?= $input ?> ps-9">
        </div>
        <div>
            <label for="status" class="sr-only"><?= lang('Berkas.statusFilter') ?></label>
            <select id="status" name="status" class="<?= $input ?>">
                <?php foreach ($statusFilters as $slug => $code) : ?>
                    <option value="<?= $slug ?>" <?= $filters['status'] === $slug ? 'selected' : '' ?>><?= lang('Status.document.' . $code) ?></option>
                <?php endforeach ?>
                <option value="semua" <?= $filters['status'] === 'semua' ? 'selected' : '' ?>><?= lang('Berkas.allStatuses') ?></option>
            </select>
        </div>
        <div>
            <label for="jenis" class="sr-only"><?= lang('Berkas.typeFilter') ?></label>
            <select id="jenis" name="jenis" class="<?= $input ?>">
                <option value=""><?= lang('Berkas.allTypes') ?></option>
                <?php foreach ($types as $type) : ?>
                    <option value="<?= (int) $type['id'] ?>" <?= $filters['jenis'] === (int) $type['id'] ? 'selected' : '' ?>><?= esc($type['name']) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <?php if ($waves !== []) : ?>
            <div>
                <label for="gelombang" class="sr-only"><?= lang('Berkas.waveFilter') ?></label>
                <select id="gelombang" name="gelombang" class="<?= $input ?>">
                    <option value=""><?= lang('Berkas.allWaves') ?></option>
                    <?php foreach ($waves as $wave) : ?>
                        <option value="<?= (int) $wave['id'] ?>" <?= $filters['gelombang'] === (int) $wave['id'] ? 'selected' : '' ?>><?= esc($wave['name'] . ' ' . $wave['academic_year']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        <?php endif ?>
        <div class="flex gap-2 sm:col-span-2 xl:col-span-1">
            <button type="submit" class="flex-1 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 xl:flex-none"><?= lang('Berkas.filter') ?></button>
            <?php if ($filtered) : ?>
                <a href="<?= url_to('berkas.antrean') ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.reset') ?></a>
            <?php endif ?>
        </div>
    </form>

    <?php if ($rows === []) : ?>
        <div class="p-4">
            <?= view('partials/empty_state', ['empty' => $filtered
                ? ['title' => lang('Berkas.queueNoResultTitle'), 'text' => lang('Berkas.queueNoResultText'), 'icon' => 'search', 'image' => 'state/kosong-pencarian', 'imageAlt' => lang('App.altNoResults')]
                : ['title' => lang('Berkas.queueEmptyTitle'), 'text' => lang('Berkas.queueEmptyText'), 'icon' => 'inbox', 'image' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData')]]) ?>
        </div>
    <?php else : ?>
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= lang('Berkas.colApplicant') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Berkas.colDocument') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Berkas.colUploaded') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Berkas.colStatus') ?></th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only"><?= lang('Berkas.colActions') ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 dark:text-white"><?= esc($row['full_name']) ?></p>
                                <p class="font-mono text-xs text-gray-500 dark:text-gray-400"><?= esc($row['registration_number']) ?></p>
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white"><?= esc($row['type_name']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= esc(tanggal_indo($row['created_at'], true)) ?></td>
                            <td class="px-4 py-3"><?= view('partials/status_badge', ['badge' => status_badge_for('document', (string) $row['status'])]) ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= url_to('berkas.periksa', $row['id']) ?>" class="<?= $check ?>"><?= icon('eye', 'h-4 w-4') ?><?= lang('Berkas.verifyButton') ?><span class="sr-only">: <?= esc($row['type_name'] . ' ' . $row['full_name']) ?></span></a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
            <?php foreach ($rows as $row) : ?>
                <li class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="break-words font-medium text-gray-900 dark:text-white"><?= esc($row['full_name']) ?></p>
                            <p class="font-mono text-xs text-gray-500 dark:text-gray-400"><?= esc($row['registration_number']) ?></p>
                        </div>
                        <?= view('partials/status_badge', ['badge' => status_badge_for('document', (string) $row['status'])]) ?>
                    </div>
                    <p class="mt-2 text-sm text-gray-900 dark:text-white"><?= esc($row['type_name']) ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= esc(tanggal_indo($row['created_at'], true)) ?></p>
                    <a href="<?= url_to('berkas.periksa', $row['id']) ?>" class="<?= $check ?> mt-3 w-full py-2.5"><?= icon('eye', 'h-4 w-4') ?><?= lang('Berkas.verifyButton') ?></a>
                </li>
            <?php endforeach ?>
        </ul>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 p-4 sm:flex-row dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Berkas.total', [$pager->getTotal()]) ?></p>
            <?= $pager->links() ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
