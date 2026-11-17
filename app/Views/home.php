<?php
/**
 * Public home page placeholder (full site content arrives in phase 5).
 */
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<section class="bg-white dark:bg-gray-900">
    <div class="mx-auto max-w-screen-xl px-4 py-12 text-center sm:py-20">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wider text-primary-700 dark:text-primary-400"><?= esc(setting('School.tagline')) ?></p>
        <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-5xl dark:text-white"><?= esc(setting('School.name')) ?></h1>
        <p class="mx-auto mb-8 max-w-2xl text-lg text-gray-500 dark:text-gray-400">Pendaftaran murid baru akan dibuka di halaman ini. Sambil menunggu, baca panduan pendaftaran agar Bapak/Ibu siap saat pendaftaran dibuka.</p>
        <a href="<?= site_url('panduan/calon-murid') ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-6 py-3.5 text-base font-medium text-white hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900">
            <?= icon('book', 'h-5 w-5') ?> Baca Panduan Pendaftaran
        </a>
    </div>
</section>
<?= $this->endSection() ?>
