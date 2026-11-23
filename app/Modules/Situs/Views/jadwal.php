<?php
/**
 * Public wave schedule (/jadwal).
 *
 * @var array{waves: list<array<string, mixed>>, banner: array{state: string, text: string, wave: array<string, mixed>|null}, academicYear: string} $page
 */
$states = [
    'open'     => ['label' => lang('Situs.stateOpen'), 'color' => 'green'],
    'upcoming' => ['label' => lang('Situs.stateUpcoming'), 'color' => 'blue'],
    'closed'   => ['label' => lang('Situs.stateClosed'), 'color' => 'gray'],
];
$banners = [
    'open'     => ['icon' => 'check', 'box' => 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-gray-800 dark:text-green-400'],
    'offline'  => ['icon' => 'info', 'box' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-gray-800 dark:text-blue-400'],
    'upcoming' => ['icon' => 'clock', 'box' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-gray-800 dark:text-blue-400'],
    'closed'   => ['icon' => 'info', 'box' => 'border-gray-200 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'],
];
$banner = $page['banner'];
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-screen-lg px-4 py-6 sm:py-10">
    <header class="mb-6">
        <h1 class="mb-1 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white"><?= lang('Situs.scheduleTitle') ?></h1>
        <p class="mb-3 text-lg font-semibold text-primary-700 dark:text-primary-400"><?= lang('Situs.academicYear', [$page['academicYear']]) ?></p>
        <p class="max-w-2xl text-lg text-gray-600 dark:text-gray-300"><?= lang('Situs.scheduleIntro') ?></p>
    </header>

    <?php if ($page['waves'] === []) : ?>
        <?= view('partials/empty_state', ['empty' => ['title' => lang('Situs.noWavesTitle'), 'text' => lang('Situs.noWavesText'), 'image' => 'state/kosong-jadwal-pendaftaran', 'fallback' => 'state/kosong-data', 'imageAlt' => '']]) ?>
    <?php else : ?>
        <div class="mb-6 flex flex-col gap-4 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between <?= $banners[$banner['state']]['box'] ?>" role="status">
            <p class="flex items-start gap-3 font-medium"><?= icon($banners[$banner['state']]['icon'], 'h-6 w-6 shrink-0') ?><span><?= esc($banner['text']) ?></span></p>
            <?php if ($banner['state'] === 'open') : ?>
                <a href="<?= site_url('daftar') ?>" class="tombol-daftar tombol-daftar-penuh shrink-0"><span class="tombol-daftar-isi"><?= lang('Situs.register') ?><?= icon('arrow-right', 'h-4 w-4') ?></span></a>
            <?php endif ?>
        </div>

        <ol class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($page['waves'] as $wave) : ?>
                <?php $open = $wave['state'] === 'open'; ?>
                <li class="motion-rise flex flex-col rounded-lg border bg-white p-5 dark:bg-gray-800 <?= $open ? 'border-primary-500 ring-1 ring-primary-500' : 'border-gray-200 dark:border-gray-700' ?>">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?= esc($wave['name']) ?></h2>
                        <?= view('partials/status_badge', ['badge' => $states[$wave['state']]]) ?>
                    </div>
                    <p class="flex items-start gap-2 text-gray-700 dark:text-gray-300">
                        <?= icon('calendar', 'mt-0.5 h-5 w-5 shrink-0 text-gray-400') ?>
                        <span><?= esc(lang('Situs.dates', [tanggal_indo($wave['starts_on']), tanggal_indo($wave['ends_on'])])) ?></span>
                    </p>
                    <?php if ($wave['quota'] !== null && (int) $wave['quota'] > 0) : ?>
                        <p class="mt-2 flex items-start gap-2 text-gray-700 dark:text-gray-300"><?= icon('users', 'mt-0.5 h-5 w-5 shrink-0 text-gray-400') ?><span><?= esc(lang('Situs.quota', [number_format((int) $wave['quota'], 0, ',', '.')])) ?></span></p>
                    <?php endif ?>
                    <?php if (! empty($wave['description'])) : ?>
                        <div class="lipat mt-3 [--lipat-latar-gelap:var(--color-gray-800)]" data-lipat data-lipat-tinggi="6rem">
                            <p class="lipat-isi text-sm text-gray-600 dark:text-gray-400"><?= nl2br(esc($wave['description'])) ?></p>
                        </div>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ol>
    <?php endif ?>

    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
        <a href="<?= url_to('situs.persyaratan') ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-base font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700"><?= icon('clipboard', 'h-5 w-5') ?><?= lang('Situs.seeRequirements') ?></a>
        <a href="<?= site_url('cek-status') ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-base font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700"><?= icon('search', 'h-5 w-5') ?><?= lang('Situs.checkStatus') ?></a>
    </div>
</div>
<?= $this->endSection() ?>
