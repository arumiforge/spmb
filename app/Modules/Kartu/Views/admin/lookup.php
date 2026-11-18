<?php
/**
 * Staff card lookup (/admin/kartu).
 *
 * @var array{query: string, result: array<string, mixed>|null} $lookup
 */
$result = $lookup['result'];
$button = 'inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium focus:outline-none focus:ring-4';
$cards  = [
    'valid'   => ['label' => lang('Kartu.cardActive'), 'color' => 'green'],
    'revoked' => ['label' => lang('Kartu.invalidTitle'), 'color' => 'red'],
    'none'    => ['label' => lang('Kartu.lookupNoCard'), 'color' => 'gray'],
];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="grid max-w-5xl gap-6 lg:grid-cols-[minmax(0,22rem)_1fr]">
    <form method="get" action="<?= url_to('kartu.cari') ?>" role="search" class="self-start rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <label for="q" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Kartu.lookupLabel') ?></label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-4 w-4') ?></span>
            <input id="q" name="q" type="search" value="<?= esc($lookup['query'], 'attr') ?>" autocomplete="off" autofocus required maxlength="300" aria-describedby="q-help"
                   class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 ps-10 text-base text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
        </div>
        <p id="q-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Kartu.lookupHelp') ?></p>
        <button type="submit" class="<?= $button ?> mt-4 w-full bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-300 dark:focus:ring-primary-800">
            <?= icon('search', 'h-4 w-4') ?><?= lang('Kartu.lookupButton') ?>
        </button>
    </form>

    <section aria-labelledby="hasil" aria-live="polite">
        <h2 id="hasil" class="sr-only"><?= lang('Kartu.lookupResult') ?></h2>
        <?php if ($result === null) : ?>
            <?= view('partials/empty_state', ['empty' => ['title' => lang('Kartu.lookupEmptyTitle'), 'text' => lang('Kartu.lookupEmptyText'), 'image' => 'state/kosong-pencarian', 'imageAlt' => '']]) ?>
        <?php elseif (! $result['found']) : ?>
            <?= view('partials/empty_state', ['empty' => ['title' => lang('Kartu.lookupNotFoundTitle'), 'text' => lang('Kartu.lookupNotFound', [mb_substr($lookup['query'], 0, 60)]), 'image' => 'state/tidak-ditemukan', 'imageAlt' => '']]) ?>
        <?php else : ?>
            <?php
            $applicant = $result['applicant'];
            $details   = $result['details'];
            $cancelled = $applicant['status'] === 'cancelled';
            ?>
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-start sm:justify-between dark:border-gray-700">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-semibold text-primary-700 dark:text-primary-400"><?= esc($details['number']) ?></p>
                        <p class="text-xl font-bold break-words text-gray-900 dark:text-white"><?= esc($details['name']) ?></p>
                    </div>
                    <?= view('partials/status_badge', ['badge' => $cards[$result['card']]]) ?>
                </div>
                <?php if ($result['card'] === 'revoked') : ?>
                    <div class="mx-5 mt-5 flex items-start gap-3 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-gray-700 dark:text-red-400" role="alert">
                        <?= icon('warning', 'h-5 w-5 shrink-0') ?><span><?= lang('Kartu.lookupRevoked') ?></span>
                    </div>
                <?php endif ?>
                <dl class="grid gap-x-6 gap-y-4 p-5 sm:grid-cols-2">
                    <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Kartu.wave') ?></dt><dd class="font-medium text-gray-900 dark:text-white"><?= esc($details['wave']) ?></dd></div>
                    <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Kartu.school') ?></dt><dd class="font-medium break-words text-gray-900 dark:text-white"><?= esc($details['school']) ?></dd></div>
                    <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Kartu.applicantStatus') ?></dt><dd class="mt-1"><?= view('partials/status_badge', ['badge' => status_badge_for('applicant', (string) $applicant['status'])]) ?></dd></div>
                    <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Kartu.documentStatus') ?></dt><dd class="mt-1"><?= view('partials/status_badge', ['badge' => status_badge_for('documents', (string) $applicant['document_status'])]) ?></dd></div>
                    <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Kartu.method') ?></dt><dd class="font-medium text-gray-900 dark:text-white"><?= esc($details['method']) ?></dd></div>
                    <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Kartu.testStatus') ?></dt><dd class="font-medium text-gray-900 dark:text-white"><?= esc($details['testStatus']) ?></dd></div>
                </dl>
                <div class="flex flex-col gap-3 border-t border-gray-200 p-5 sm:flex-row dark:border-gray-700">
                    <?php if (auth()->user()->can('pendaftar.view')) : ?>
                        <a href="<?= site_url('admin/pendaftar/' . $applicant['id']) ?>" class="<?= $button ?> bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-300 dark:focus:ring-primary-800">
                            <?= icon('user', 'h-4 w-4') ?><?= lang('Kartu.openApplicant') ?>
                        </a>
                    <?php endif ?>
                    <?php if (! $cancelled) : ?>
                        <a href="<?= url_to('kartu.cetak', $applicant['id']) ?>" target="_blank" rel="noopener" class="<?= $button ?> border border-gray-300 bg-white text-gray-900 hover:bg-gray-100 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            <?= icon('clipboard', 'h-4 w-4') ?><?= lang('Kartu.print') ?>
                        </a>
                    <?php endif ?>
                </div>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
