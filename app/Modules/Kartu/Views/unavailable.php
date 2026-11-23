<?php
/**
 * Friendly page when the applicant cannot download the card yet (or at all).
 *
 * @var array{title: string, text: string} $notice
 */
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<section class="mx-auto max-w-lg px-4 py-8 text-center sm:py-12">
    <div class="mx-auto mb-6 max-w-xs"><?= illustration('section/kartu', lang('App.altSections')['section/kartu'], 'mx-auto h-44 w-auto sm:h-52', false) ?></div>
    <h1 class="mb-3 text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= esc($notice['title']) ?></h1>
    <p class="mb-6 text-gray-600 dark:text-gray-300"><?= esc($notice['text']) ?></p>
    <a href="<?= site_url('cek-status/data') ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-3 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800">
        <?= lang('Kartu.backToStatus') ?>
    </a>
</section>
<?= $this->endSection() ?>
