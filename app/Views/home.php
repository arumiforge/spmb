<?php
/**
 * Public home page placeholder (full site content arrives in phase 5).
 */
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<section class="bg-white dark:bg-gray-900">
    <div class="mx-auto grid max-w-screen-xl items-center gap-8 px-4 py-10 sm:py-16 md:grid-cols-[1fr_minmax(0,24rem)] lg:grid-cols-[1fr_minmax(0,28rem)]">
        <div class="text-center md:text-start">
            <p class="mb-3 text-sm font-semibold uppercase tracking-wider text-primary-700 dark:text-primary-400"><?= esc(setting('School.tagline')) ?></p>
            <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-5xl dark:text-white"><?= esc(setting('School.name')) ?></h1>
            <p class="mx-auto mb-8 max-w-2xl text-lg text-gray-500 md:mx-0 dark:text-gray-400">Pendaftaran murid baru akan dibuka di halaman ini. Sambil menunggu, baca panduan pendaftaran agar Bapak/Ibu siap saat pendaftaran dibuka.</p>
            <a href="<?= site_url('panduan/calon-murid') ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-6 py-3.5 text-base font-medium text-white hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900">
                <?= icon('book', 'h-5 w-5') ?> Baca Panduan Pendaftaran
            </a>
        </div>
        <div class="mx-auto w-full max-w-xs overflow-hidden rounded-2xl bg-white md:max-w-none">
            <?= illustration('hero/hero-beranda', lang('App.altHero'), 'h-auto w-full', false) ?>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
