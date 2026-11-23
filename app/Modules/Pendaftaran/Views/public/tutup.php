<?php
/**
 * Online registration closed (switched off, no wave today, or quota full).
 *
 * @var string                    $reason switched_off|no_wave|full
 * @var array<string, mixed>|null $next   next wave of the year
 */
$wa     = preg_replace('/\D/', '', (string) setting('School.whatsapp')) ?? '';
$wa     = str_starts_with($wa, '0') ? '62' . substr($wa, 1) : $wa;
$button = 'inline-flex w-full items-center justify-center gap-2 rounded-lg px-5 py-3 text-base font-medium sm:w-auto';
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-2xl px-4 py-10 text-center sm:py-16">
    <div class="mb-6 flex justify-center"><?= illustration('state/pendaftaran-ditutup', lang('App.altRegistrationClosed'), 'h-40 w-auto sm:h-52', false) ?: illustration('state/kosong-data', lang('App.altNoData'), 'h-40 w-auto sm:h-52', false) ?></div>
    <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= lang('Pendaftaran.closedTitle') ?></h1>
    <p class="mt-3 text-base text-gray-600 dark:text-gray-300"><?= lang('Pendaftaran.closedReasons.' . $reason) ?></p>
    <?php if ($next !== null) : ?>
        <p class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-800 dark:bg-gray-800 dark:text-primary-300">
            <?= icon('calendar', 'h-4 w-4') ?><?= esc(lang('Pendaftaran.closedNext', [$next['name'], tanggal_indo($next['starts_on'])])) ?>
        </p>
    <?php endif ?>
    <p class="mt-6 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.closedCheck') ?></p>
    <div class="mt-4 flex flex-col justify-center gap-3 sm:flex-row">
        <a href="<?= url_to('cek-status') ?>" class="<?= $button ?> bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300"><?= lang('Pendaftaran.checkSubmit') ?></a>
        <?php if (service('modules')->isEnabled('Situs')) : ?>
            <a href="<?= site_url('jadwal') ?>" class="<?= $button ?> border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"><?= lang('Pendaftaran.seeSchedule') ?></a>
        <?php endif ?>
        <?php if ($wa !== '') : ?>
            <a href="https://wa.me/<?= esc($wa, 'url') ?>" target="_blank" rel="noopener" class="<?= $button ?> border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"><?= lang('Pendaftaran.contactCommittee') ?></a>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
