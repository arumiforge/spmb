<?php
/**
 * Public home page: "Daftar Sekarang" while online registration is open,
 * "Cek Status" always (with Pendaftaran on), links to requirements and schedule.
 *
 * @var array{registration: bool, banner: array{state: string, text: string, wave: array<string, mixed>|null}|null, onlineOpen: bool, situs: bool} $home
 */
$primary   = 'inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-6 py-3.5 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-900';
$secondary = 'inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3.5 text-base font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$banner    = $home['banner'];
$intro     = match (true) {
    ! $home['registration']                                   => lang('Situs.homeClosedText'),
    $home['onlineOpen']                                       => lang('Situs.homeOpenText'),
    $banner !== null && $banner['state'] === 'offline'        => lang('Situs.homeOfflineText'),
    default                                                   => lang('Situs.homeClosedText'),
};
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<section class="bg-white dark:bg-gray-900">
    <div class="mx-auto grid max-w-screen-xl items-center gap-8 px-4 py-10 sm:py-16 md:grid-cols-[1fr_minmax(0,24rem)] lg:grid-cols-[1fr_minmax(0,28rem)]">
        <div class="text-center md:text-start">
            <p class="mb-3 text-sm font-semibold uppercase tracking-wider text-primary-700 dark:text-primary-400"><?= esc(setting('School.tagline')) ?></p>
            <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-5xl dark:text-white"><?= esc(setting('School.name')) ?></h1>
            <p class="mx-auto mb-6 max-w-2xl text-lg text-gray-500 md:mx-0 dark:text-gray-400"><?= esc($intro) ?></p>

            <?php if ($banner !== null && $banner['wave'] !== null) : ?>
                <p class="mx-auto mb-6 inline-flex max-w-2xl items-start gap-2 rounded-lg bg-primary-50 px-4 py-3 text-start text-sm font-medium text-primary-800 md:mx-0 dark:bg-gray-800 dark:text-primary-300">
                    <?= icon($banner['state'] === 'upcoming' ? 'clock' : 'calendar', 'h-5 w-5 shrink-0') ?><span><?= esc($banner['text']) ?></span>
                </p>
            <?php endif ?>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap md:justify-start sm:justify-center">
                <?php if ($home['onlineOpen']) : ?>
                    <a href="<?= site_url('daftar') ?>" class="<?= $primary ?>"><?= lang('Situs.register') ?><?= icon('arrow-right', 'h-5 w-5') ?></a>
                <?php endif ?>
                <?php if ($home['registration']) : ?>
                    <a href="<?= site_url('cek-status') ?>" class="<?= $home['onlineOpen'] ? $secondary : $primary ?>"><?= icon('search', 'h-5 w-5') ?><?= lang('Situs.checkStatus') ?></a>
                <?php endif ?>
                <?php if ($home['situs']) : ?>
                    <a href="<?= url_to('situs.persyaratan') ?>" class="<?= $secondary ?>"><?= icon('clipboard', 'h-5 w-5') ?><?= lang('Situs.seeRequirements') ?></a>
                <?php else : ?>
                    <a href="<?= site_url('panduan/calon-murid') ?>" class="<?= $home['registration'] ? $secondary : $primary ?>"><?= icon('book', 'h-5 w-5') ?><?= lang('Situs.homeGuide') ?></a>
                <?php endif ?>
            </div>
        </div>
        <div class="mx-auto w-full max-w-xs overflow-hidden rounded-2xl bg-white md:max-w-none">
            <?= illustration('hero/hero-beranda', lang('App.altHero'), 'h-auto w-full', false) ?>
        </div>
    </div>
</section>

<?php if ($home['registration']) : ?>
    <section class="border-t border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50" aria-labelledby="cara-daftar">
        <div class="mx-auto max-w-screen-xl px-4 py-10">
            <h2 id="cara-daftar" class="mb-6 text-center text-2xl font-bold text-gray-900 md:text-start dark:text-white"><?= lang('Situs.homeStepsTitle') ?></h2>
            <ol class="grid gap-4 md:grid-cols-3">
                <?php foreach (['homeStep1', 'homeStep2', 'homeStep3'] as $i => $key) : ?>
                    <li class="flex items-start gap-4 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-600 font-bold text-white"><?= $i + 1 ?></span>
                        <p class="pt-2 text-gray-800 dark:text-gray-200"><?= lang('Situs.' . $key) ?></p>
                    </li>
                <?php endforeach ?>
            </ol>
            <p class="mt-6 text-center text-sm md:text-start">
                <a href="<?= site_url('panduan/calon-murid') ?>" class="inline-flex items-center gap-1 font-medium text-primary-700 hover:underline dark:text-primary-400"><?= icon('book', 'h-4 w-4') ?><?= lang('Situs.homeGuide') ?></a>
            </p>
        </div>
    </section>
<?php endif ?>
<?= $this->endSection() ?>
