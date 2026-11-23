<?php
/**
 * Waves of one academic year.
 *
 * @var list<array<string, mixed>> $waves
 * @var list<string>               $years
 * @var string                     $year
 * @var string                     $currentYear
 */
$input  = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
$states = [
    'open'     => ['label' => lang('Pendaftaran.waveOpenNow'), 'color' => 'green'],
    'upcoming' => ['label' => lang('Pendaftaran.waveUpcoming'), 'color' => 'blue'],
    'finished' => ['label' => lang('Pendaftaran.waveFinished'), 'color' => 'gray'],
    'inactive' => ['label' => lang('Pendaftaran.waveInactive'), 'color' => 'red'],
    'full'     => ['label' => lang('Pendaftaran.waveFull'), 'color' => 'yellow'],
];
$cols   = lang('Pendaftaran.waveCols');
$quota  = static fn (array $w): string => $w['quota'] === null ? lang('Pendaftaran.waveNoQuota') : number_format((int) $w['quota'], 0, ',', '.');
$button = 'inline-flex items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<?php if ($year === $currentYear) : ?>
    <a href="<?= url_to('gelombang.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
        <?= icon('plus', 'h-4 w-4') ?><?= lang('Pendaftaran.waveAdd') ?>
    </a>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <?php if (count($years) > 1) : ?>
        <form method="get" action="<?= url_to('gelombang') ?>" class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-end dark:border-gray-700">
            <div class="sm:w-60">
                <label for="tahun" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Pendaftaran.waveYearFilter') ?></label>
                <select id="tahun" name="tahun" class="<?= $input ?>">
                    <?php foreach ($years as $y) : ?>
                        <option value="<?= esc($y, 'attr') ?>" <?= $y === $year ? 'selected' : '' ?>><?= esc($y) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700"><?= lang('App.search') ?></button>
        </form>
    <?php endif ?>

    <?php if ($waves === []) : ?>
        <div class="p-4">
            <?= view('partials/empty_state', ['empty' => [
                'title'    => lang('Pendaftaran.waveEmptyTitle'),
                'text'     => lang('Pendaftaran.waveEmptyText'),
                'icon'     => 'calendar',
                'image'    => 'state/kosong-gelombang', 'fallback' => 'state/kosong-data',
                'imageAlt' => lang('App.altNoData'),
                'action'   => $year === $currentYear ? ['label' => lang('Pendaftaran.waveAdd'), 'url' => 'admin/pengaturan/gelombang/tambah'] : null,
            ]]) ?>
        </div>
    <?php else : ?>
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= $cols['name'] ?></th>
                        <th scope="col" class="px-4 py-3"><?= $cols['dates'] ?></th>
                        <th scope="col" class="px-4 py-3 text-right"><?= $cols['quota'] ?></th>
                        <th scope="col" class="px-4 py-3 text-right"><?= $cols['applicants'] ?></th>
                        <th scope="col" class="px-4 py-3"><?= $cols['state'] ?></th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only"><?= $cols['actions'] ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($waves as $w) : ?>
                        <tr class="border-b border-gray-100 last:border-0 dark:border-gray-700">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 dark:text-white"><?= esc($w['name']) ?></p>
                                <?php if (! empty($w['description'])) : ?><p class="max-w-xs truncate text-gray-500 dark:text-gray-400"><?= esc($w['description']) ?></p><?php endif ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3"><?= esc(tanggal_indo($w['starts_on'])) ?> – <?= esc(tanggal_indo($w['ends_on'])) ?></td>
                            <td class="px-4 py-3 text-right"><?= esc($quota($w)) ?></td>
                            <td class="px-4 py-3 text-right"><?= number_format($w['applicants'], 0, ',', '.') ?></td>
                            <td class="px-4 py-3"><?= view('partials/status_badge', ['badge' => $states[$w['state']]]) ?></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="<?= url_to('gelombang.ubah', $w['id']) ?>" class="<?= $button ?> border-gray-200 text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Pendaftaran.edit') ?></a>
                                    <?php if ($w['applicants'] === 0) : ?>
                                        <form method="post" action="<?= url_to('gelombang.hapus', $w['id']) ?>" data-confirm="<?= esc(lang('Pendaftaran.waveDeleteConfirm', [$w['name']]), 'attr') ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="<?= $button ?> border-red-200 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-gray-700"><?= lang('Pendaftaran.delete') ?></button>
                                        </form>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
            <?php foreach ($waves as $w) : ?>
                <li class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-medium text-gray-900 dark:text-white"><?= esc($w['name']) ?></p>
                        <?= view('partials/status_badge', ['badge' => $states[$w['state']]]) ?>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?= esc(tanggal_indo($w['starts_on'])) ?> – <?= esc(tanggal_indo($w['ends_on'])) ?></p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= $cols['quota'] ?>: <?= esc($quota($w)) ?> · <?= $cols['applicants'] ?>: <?= number_format($w['applicants'], 0, ',', '.') ?></p>
                    <div class="mt-3 flex gap-2">
                        <a href="<?= url_to('gelombang.ubah', $w['id']) ?>" class="<?= $button ?> flex-1 border-gray-200 py-2.5 text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Pendaftaran.edit') ?></a>
                        <?php if ($w['applicants'] === 0) : ?>
                            <form method="post" action="<?= url_to('gelombang.hapus', $w['id']) ?>" class="flex-1" data-confirm="<?= esc(lang('Pendaftaran.waveDeleteConfirm', [$w['name']]), 'attr') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="<?= $button ?> w-full border-red-200 py-2.5 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-gray-700"><?= lang('Pendaftaran.delete') ?></button>
                            </form>
                        <?php endif ?>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
