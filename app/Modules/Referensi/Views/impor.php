<?php
/**
 * CSV/XLSX upload and the report of the last import (flash data).
 *
 * @var array{created: int, updated: int, unchanged: int, failed: int, more: int, file: string, errors: list<array{row: int, name: string, message: string}>}|null $report
 */
$card   = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$btnSec = 'inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <form method="post" action="<?= url_to('referensi.impor') ?>" enctype="multipart/form-data" class="<?= $card ?> space-y-5">
            <?= csrf_field() ?>
            <?= view('partials/form_field', ['field' => ['name' => 'berkas', 'label' => lang('Referensi.importFile'), 'type' => 'file', 'required' => true, 'help' => lang('Referensi.importFileHelp'), 'attrs' => ['accept' => '.xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']]]) ?>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="<?= url_to('referensi.sekolah') ?>" class="<?= $btnSec ?>"><?= lang('App.back') ?></a>
                <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Referensi.importSubmit') ?></button>
            </div>
        </form>

        <?php if (is_array($report)) : ?>
            <section class="<?= $card ?>" aria-labelledby="report-title">
                <h2 id="report-title" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Referensi.importReport') ?></h2>
                <p class="mt-1 break-all text-sm text-gray-500 dark:text-gray-400"><?= esc($report['file']) ?></p>
                <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <?php foreach (['created' => 'green', 'updated' => 'blue', 'unchanged' => 'gray', 'failed' => 'red'] as $key => $color) : ?>
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <dt class="text-xs text-gray-500 dark:text-gray-400"><?= lang('Referensi.import' . ucfirst($key)) ?></dt>
                            <dd class="mt-1 text-2xl font-semibold <?= $key === 'failed' && $report['failed'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' ?>"><?= number_format($report[$key], 0, ',', '.') ?></dd>
                        </div>
                    <?php endforeach ?>
                </dl>

                <?php if ($report['errors'] !== []) : ?>
                    <h3 class="mt-6 font-semibold text-gray-900 dark:text-white"><?= lang('Referensi.importErrorsTitle') ?></h3>
                    <div class="mt-3 max-h-96 overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                            <thead class="sticky top-0 bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th scope="col" class="px-3 py-2"><?= lang('Referensi.colRow') ?></th>
                                    <th scope="col" class="px-3 py-2"><?= lang('Referensi.colName') ?></th>
                                    <th scope="col" class="px-3 py-2"><?= lang('Referensi.colProblem') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['errors'] as $error) : ?>
                                    <tr class="border-t border-gray-100 dark:border-gray-700">
                                        <td class="px-3 py-2 font-mono"><?= (int) $error['row'] ?></td>
                                        <td class="px-3 py-2 text-gray-900 dark:text-white"><?= esc($error['name'] !== '' ? $error['name'] : '—') ?></td>
                                        <td class="px-3 py-2"><?= esc($error['message']) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($report['more'] > 0) : ?>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.importErrorsMore', [$report['more']]) ?></p>
                    <?php endif ?>
                <?php endif ?>
            </section>
        <?php endif ?>
    </div>

    <aside class="<?= $card ?> h-fit space-y-4">
        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white"><?= icon('info', 'h-5 w-5 text-primary-600 dark:text-primary-400') ?><?= lang('Referensi.importHowTitle') ?></h2>
        <p class="text-sm text-gray-600 dark:text-gray-400"><?= lang('Referensi.importHowText') ?></p>
        <p class="text-sm text-gray-600 dark:text-gray-400"><?= lang('Referensi.importHowSafe') ?></p>
        <a href="<?= url_to('referensi.impor.contoh') ?>" class="<?= $btnSec ?> w-full"><?= icon('copy', 'h-4 w-4') ?><?= lang('Referensi.importTemplate') ?></a>
    </aside>
</div>
<?= $this->endSection() ?>
