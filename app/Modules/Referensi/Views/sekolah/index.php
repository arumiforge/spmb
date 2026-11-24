<?php
/**
 * Previous-school list with search and filters.
 *
 * @var list<array<string, mixed>>                                           $schools
 * @var CodeIgniter\Pager\Pager                                              $pager
 * @var list<string>                                                         $regencies
 * @var array{q: string, jenjang: string, kabupaten: string, status: string} $filters
 * @var int                                                                  $unmapped
 */

use Modules\Referensi\Models\SchoolModel;

$input    = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$btnSec   = 'inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$place    = static fn (array $s): string => implode(', ', array_filter([$s['district'] ? 'Kec. ' . $s['district'] : '', (string) $s['regency']]));
$status   = static fn (array $s): array => (int) $s['is_active'] === 1 ? ['label' => lang('Referensi.active'), 'color' => 'green'] : ['label' => lang('Referensi.inactive'), 'color' => 'gray'];
$filtered = array_filter($filters) !== [];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= url_to('referensi.lain') ?>" class="<?= $btnSec ?>">
    <?= icon('inbox', 'h-4 w-4') ?><?= lang('Referensi.others') ?>
    <?php if ($unmapped > 0) : ?>
        <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300"><?= lang('Referensi.othersWaiting', [$unmapped]) ?></span>
    <?php endif ?>
</a>
<a href="<?= url_to('referensi.alamat') ?>" class="<?= $btnSec ?>"><?= icon('map-pin', 'h-4 w-4') ?><?= lang('Referensi.addressButton') ?></a>
<a href="<?= url_to('referensi.impor') ?>" class="<?= $btnSec ?>"><?= icon('copy', 'h-4 w-4') ?><?= lang('Referensi.import') ?></a>
<a href="<?= url_to('referensi.sekolah.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
    <?= icon('plus', 'h-4 w-4') ?><?= lang('Referensi.add') ?>
</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <form method="get" action="<?= url_to('referensi.sekolah') ?>" class="grid grid-cols-1 gap-3 border-b border-gray-200 p-4 sm:grid-cols-3 xl:grid-cols-[1fr_10rem_14rem_10rem_auto] dark:border-gray-700" role="search">
        <div class="relative sm:col-span-3 xl:col-span-1">
            <label for="q" class="sr-only"><?= lang('Referensi.searchLabel') ?></label>
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-4 w-4') ?></span>
            <input type="search" id="q" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="<?= lang('Referensi.searchLabel') ?>" class="<?= $input ?> ps-9">
        </div>
        <div>
            <label for="jenjang" class="sr-only"><?= lang('Referensi.levelFilter') ?></label>
            <select id="jenjang" name="jenjang" class="<?= $input ?>">
                <option value=""><?= lang('Referensi.allLevels') ?></option>
                <?php foreach (SchoolModel::LEVELS as $level) : ?>
                    <option value="<?= $level ?>" <?= $filters['jenjang'] === $level ? 'selected' : '' ?>><?= $level ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div>
            <label for="kabupaten" class="sr-only"><?= lang('Referensi.regencyFilter') ?></label>
            <select id="kabupaten" name="kabupaten" class="<?= $input ?>">
                <option value=""><?= lang('Referensi.allRegencies') ?></option>
                <?php foreach ($regencies as $regency) : ?>
                    <option value="<?= esc($regency, 'attr') ?>" <?= $filters['kabupaten'] === $regency ? 'selected' : '' ?>><?= esc($regency) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div>
            <label for="status" class="sr-only"><?= lang('Referensi.statusFilter') ?></label>
            <select id="status" name="status" class="<?= $input ?>">
                <option value=""><?= lang('Referensi.allStatus') ?></option>
                <option value="aktif" <?= $filters['status'] === 'aktif' ? 'selected' : '' ?>><?= lang('Referensi.active') ?></option>
                <option value="nonaktif" <?= $filters['status'] === 'nonaktif' ? 'selected' : '' ?>><?= lang('Referensi.inactive') ?></option>
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-3 xl:col-span-1">
            <button type="submit" class="flex-1 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 xl:flex-none"><?= lang('Referensi.filter') ?></button>
            <?php if ($filtered) : ?>
                <a href="<?= url_to('referensi.sekolah') ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.reset') ?></a>
            <?php endif ?>
        </div>
    </form>

    <?php if ($schools === []) : ?>
        <div class="p-4">
            <?= view('partials/empty_state', ['empty' => $filtered
                ? ['title' => lang('Referensi.noResultTitle'), 'text' => lang('Referensi.noResultText'), 'icon' => 'school', 'image' => 'state/kosong-pencarian', 'imageAlt' => lang('App.altNoResults')]
                : ['title' => lang('Referensi.emptyTitle'), 'text' => lang('Referensi.emptyText'), 'icon' => 'school', 'image' => 'state/kosong-sekolah-asal', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData')]]) ?>
        </div>
    <?php else : ?>
        <!-- Table on md and up -->
        <div class="relative hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= lang('Referensi.colName') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Referensi.colNpsn') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Referensi.colLevel') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Referensi.colPlace') ?></th>
                        <th scope="col" class="hidden px-4 py-3 lg:table-cell"><?= lang('Referensi.colSource') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Referensi.colStatus') ?></th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only"><?= lang('Referensi.colActions') ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schools as $s) : ?>
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= esc($s['name']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono"><?= $s['npsn'] !== null ? esc($s['npsn']) : '<span class="font-sans text-gray-400">' . lang('Referensi.noNpsn') . '</span>' ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= esc($s['level']) ?><?php if ($s['ownership'] !== null) : ?> <span class="text-gray-500 dark:text-gray-400">· <?= esc(lang('Referensi.ownership.' . $s['ownership'])) ?></span><?php endif ?></td>
                            <td class="px-4 py-3"><?= esc($place($s)) ?></td>
                            <td class="hidden px-4 py-3 lg:table-cell"><?= esc(lang('Referensi.source.' . $s['source'])) ?></td>
                            <td class="px-4 py-3"><?= view('partials/status_badge', ['badge' => $status($s)]) ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= url_to('referensi.sekolah.ubah', $s['id']) ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Referensi.edit') ?><span class="sr-only"> <?= esc($s['name']) ?></span></a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <!-- Stacked cards below md -->
        <ul class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
            <?php foreach ($schools as $s) : ?>
                <li class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white"><?= esc($s['name']) ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($s['level']) ?> · <?= $s['npsn'] !== null ? 'NPSN ' . esc($s['npsn']) : lang('Referensi.noNpsn') ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($place($s)) ?></p>
                        </div>
                        <?= view('partials/status_badge', ['badge' => $status($s)]) ?>
                    </div>
                    <a href="<?= url_to('referensi.sekolah.ubah', $s['id']) ?>" class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Referensi.edit') ?></a>
                </li>
            <?php endforeach ?>
        </ul>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 p-4 sm:flex-row dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.total', [number_format($pager->getTotal(), 0, ',', '.')]) ?></p>
            <?= $pager->links() ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
