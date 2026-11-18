<?php
/**
 * Registration done: number and private link, shown once (D-034).
 *
 * @var array{number: string, name: string, birth: string, link: string} $done
 */
$input  = 'block w-full min-w-0 rounded-lg border border-gray-300 bg-gray-50 p-3 font-mono text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
$button = 'inline-flex items-center justify-center gap-2 rounded-lg px-5 py-3 text-base font-medium';
$waText = lang('Pendaftaran.doneWaText', [setting('School.name'), $done['name'], $done['number'], tanggal_indo($done['birth']), $done['link']]);
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-2xl px-4 py-8 sm:py-12">
    <div class="text-center">
        <div class="mb-4 flex justify-center"><?= illustration('state/sukses-pendaftaran', lang('Pendaftaran.altDone'), 'h-40 w-auto sm:h-56', false) ?></div>
        <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= lang('Pendaftaran.doneTitle') ?></h1>
        <p class="mt-2 text-base text-gray-600 dark:text-gray-300"><?= esc(lang('Pendaftaran.doneIntro', [$done['name']])) ?></p>
    </div>

    <div class="mt-8 space-y-5">
        <section class="rounded-xl border-2 border-primary-200 bg-primary-50 p-5 text-center sm:p-6 dark:border-primary-800 dark:bg-gray-800">
            <h2 class="text-sm font-medium uppercase tracking-wide text-primary-800 dark:text-primary-300"><?= lang('Pendaftaran.doneNumber') ?></h2>
            <p class="mt-2 break-all font-mono text-3xl font-bold text-gray-900 sm:text-4xl dark:text-white"><?= esc($done['number']) ?></p>
            <button type="button" data-copy="<?= esc($done['number'], 'attr') ?>" class="<?= $button ?> mt-3 border border-gray-200 bg-white py-2 text-sm text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"><?= icon('copy', 'h-4 w-4') ?><?= lang('App.copy') ?></button>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300"><?= lang('Pendaftaran.doneNumberNote') ?></p>
        </section>

        <section class="rounded-xl border border-yellow-300 bg-yellow-50 p-5 sm:p-6 dark:border-yellow-800 dark:bg-gray-800">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white"><?= icon('key', 'h-5 w-5 text-yellow-700 dark:text-yellow-400') ?><?= lang('Pendaftaran.doneLinkTitle') ?></h2>
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300"><?= lang('Pendaftaran.doneLinkNote') ?></p>
            <input type="text" readonly value="<?= esc($done['link'], 'attr') ?>" aria-label="<?= lang('Pendaftaran.doneLinkTitle') ?>" class="<?= $input ?> mt-3">
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <button type="button" data-copy="<?= esc($done['link'], 'attr') ?>" class="<?= $button ?> border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"><?= icon('copy', 'h-5 w-5') ?><?= lang('App.copy') ?></button>
                <a href="https://wa.me/?text=<?= rawurlencode($waText) ?>" target="_blank" rel="noopener" class="<?= $button ?> bg-green-600 text-white hover:bg-green-700"><?= lang('Pendaftaran.doneSaveWa') ?></a>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.doneNext') ?></h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300"><?= lang('Pendaftaran.doneNextText') ?></p>
            <a href="<?= url_to('cek-status.data') ?>" class="<?= $button ?> mt-4 w-full bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto"><?= lang('Pendaftaran.doneOpen') ?><?= icon('arrow-right', 'h-5 w-5') ?></a>
        </section>

        <p class="text-center text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.doneTwice') ?></p>
    </div>
</div>
<?= $this->endSection() ?>
