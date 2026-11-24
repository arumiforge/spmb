<?php
/**
 * Sekolah Asal → Alamat pendaftar (D-111): state of the region list and the
 * one-time matching of typed applicant addresses (preview, then save).
 *
 * @var bool                                                                                                    $available
 * @var string                                                                                                  $version
 * @var array<string, mixed>|null                                                                               $manifest
 * @var int                                                                                                     $pending
 * @var array{total: int, coded: int}                                                                           $counts
 * @var array{checked: int, full: int, partial: int, none: int, changed: int, rows: list<array<string, mixed>>}|null $preview
 * @var bool                                                                                                    $regionSet
 */

use Modules\Referensi\Libraries\AddressBackfill;

$card    = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$btn     = 'inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800';
$btnSec  = 'inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$n       = static fn (int|string $v): string => number_format((int) $v, 0, ',', '.');
$colors  = ['full' => 'green', 'partial' => 'yellow', 'none' => 'red'];
$levels  = lang('Referensi.regionLevels');
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="form-lebar">
    <section class="<?= $card ?>" aria-labelledby="alamat-daftar">
        <h2 id="alamat-daftar" class="mb-3 flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white"><?= icon('map', 'h-5 w-5 text-primary-600 dark:text-primary-400') ?><?= lang('Referensi.addressListTitle') ?></h2>
        <?php if ($available && is_array($manifest)) : ?>
            <p class="text-sm text-gray-700 dark:text-gray-300"><?= esc(lang('Referensi.addressListLoaded', [$version, $n($manifest['provinces'] ?? 0), $n($manifest['regencies'] ?? 0), $n($manifest['districts'] ?? 0), $n($manifest['villages'] ?? 0)])) ?></p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.addressListSource') ?></p>
            <?php if (! $regionSet) : ?>
                <p class="mt-4 flex items-start gap-2 rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-gray-900 dark:text-blue-400">
                    <?= icon('info', 'mt-0.5 h-4 w-4 shrink-0') ?>
                    <span><?= lang('Referensi.addressRegionHint') ?> <a href="<?= url_to('pengaturan.sekolah') ?>" class="font-medium underline"><?= lang('Referensi.addressRegionLink') ?></a></span>
                </p>
            <?php endif ?>
        <?php else : ?>
            <p class="flex items-start gap-2 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-gray-900 dark:text-yellow-300"><?= icon('triangle-alert', 'mt-0.5 h-4 w-4 shrink-0') ?><?= lang('Referensi.addressNoList') ?></p>
        <?php endif ?>
    </section>

    <?php if ($available) : ?>
        <section class="<?= $card ?>" aria-labelledby="alamat-pendaftar">
            <h2 id="alamat-pendaftar" class="mb-3 flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white"><?= icon('map-pin', 'h-5 w-5 text-primary-600 dark:text-primary-400') ?><?= lang('Referensi.addressCountTitle') ?></h2>
            <p class="text-sm text-gray-700 dark:text-gray-300"><?= esc(lang('Referensi.addressCounts', [$n($counts['coded']), $n($counts['total'])])) ?></p>
            <?php if ($pending > 0) : ?>
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300"><?= esc(lang('Referensi.addressPending', [$n($pending)])) ?></p>
                <?php if ($preview === null) : ?>
                    <div class="mt-4">
                        <a href="<?= url_to('referensi.alamat') ?>?pratinjau=1#hasil" class="<?= $btn ?>"><?= icon('search', 'h-4 w-4') ?><?= lang('Referensi.addressPreview') ?></a>
                    </div>
                <?php endif ?>
            <?php else : ?>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.addressNothingPending') ?></p>
            <?php endif ?>
        </section>
    <?php endif ?>

    <?php if (is_array($preview) && $preview['checked'] > 0) : ?>
        <section id="hasil" class="<?= $card ?>" aria-labelledby="alamat-hasil">
            <h2 id="alamat-hasil" class="mb-2 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Referensi.addressPreviewTitle') ?></h2>
            <p class="text-sm text-gray-700 dark:text-gray-300"><?= esc(lang('Referensi.addressPreviewSummary', [$n($preview['checked']), $n($preview['full']), $n($preview['partial']), $n($preview['none']), $n($preview['changed'])])) ?></p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.addressPreviewNote') ?></p>

            <?php if ($preview['rows'] !== []) : ?>
                <div class="mt-4 max-h-[32rem] overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                        <thead class="sticky top-0 bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-3 py-2"><?= lang('Referensi.addressColName') ?></th>
                                <th scope="col" class="px-3 py-2"><?= lang('Referensi.addressColTyped') ?></th>
                                <th scope="col" class="px-3 py-2"><?= lang('Referensi.addressColResult') ?></th>
                                <th scope="col" class="px-3 py-2"><?= lang('Referensi.addressColStatus') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview['rows'] as $row) : ?>
                                <tr class="border-t border-gray-100 align-top dark:border-gray-700">
                                    <td class="px-3 py-2">
                                        <span class="block font-medium text-gray-900 dark:text-white"><?= esc($row['name']) ?></span>
                                        <span class="font-mono text-xs"><?= esc($row['number']) ?></span>
                                    </td>
                                    <td class="px-3 py-2"><?= esc($row['typed']) ?></td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white"><?= esc($row['result'] !== '' ? $row['result'] : '—') ?></td>
                                    <td class="px-3 py-2">
                                        <?= view('partials/status_badge', ['badge' => ['label' => lang('Referensi.addressStatus')[$row['status']], 'color' => $colors[$row['status']]]]) ?>
                                        <?php if ($row['unmatched'] !== []) : ?>
                                            <span class="mt-1 block text-xs"><?= esc(lang('Referensi.addressMissing', [implode(', ', array_map(static fn (string $l): string => (string) ($levels[$l] ?? $l), $row['unmatched']))])) ?></span>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($preview['rows']) >= AddressBackfill::REPORT_ROWS) : ?>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.addressPreviewMore', [AddressBackfill::REPORT_ROWS]) ?></p>
                <?php endif ?>
            <?php endif ?>

            <?php if ($preview['changed'] > 0) : ?>
                <form method="post" action="<?= url_to('referensi.alamat') ?>" class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end" data-confirm="<?= esc(lang('Referensi.addressSaveConfirm'), 'attr') ?>">
                    <?= csrf_field() ?>
                    <a href="<?= url_to('referensi.alamat') ?>" class="<?= $btnSec ?>"><?= lang('App.cancel') ?></a>
                    <button type="submit" class="<?= $btn ?>"><?= icon('check', 'h-4 w-4') ?><?= lang('Referensi.addressSave') ?></button>
                </form>
            <?php endif ?>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
