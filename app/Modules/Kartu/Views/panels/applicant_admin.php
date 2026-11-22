<?php
/**
 * Card section on the staff applicant page (slot applicant_admin): current card
 * state, print and renew. Renders nothing for staff without kartu.cetak.
 *
 * @var array<string, mixed> $applicant applicants row
 */
use Modules\Kartu\Models\CardTokenModel;
use Modules\Kartu\Services\CardAvailability;

$user = auth()->user();

if (empty($applicant['id']) || $user === null || ! $user->can('kartu.cetak')) {
    return;
}

$token     = model(CardTokenModel::class)->activeFor((int) $applicant['id']);
$cancelled = $applicant['status'] === 'cancelled';
$button    = 'inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium focus:outline-none focus:ring-4';
?>
<section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800" aria-labelledby="panel-kartu">
    <div class="mb-4 flex items-center gap-3">
        <?= icon('key', 'h-6 w-6 shrink-0 text-primary-700 dark:text-primary-400') ?>
        <h2 id="panel-kartu" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Kartu.panelTitle') ?></h2>
    </div>

    <?php if ($cancelled) : ?>
        <p class="text-sm text-gray-600 dark:text-gray-300"><?= lang('Kartu.cancelledStaff') ?></p>
    <?php else : ?>
        <div class="mb-4 space-y-1 text-sm">
            <?php if ($token !== null) : ?>
                <p class="flex flex-wrap items-center gap-2 text-gray-900 dark:text-white">
                    <?= view('partials/status_badge', ['badge' => ['label' => lang('Kartu.cardActive'), 'color' => 'green']]) ?>
                    <span class="text-gray-500 dark:text-gray-400"><?= lang('Kartu.cardIssuedAt', [tanggal_indo($token['created_at'], true)]) ?></span>
                </p>
                <p class="text-gray-500 dark:text-gray-400">
                    <?= $token['last_seen_at'] === null ? lang('Kartu.cardNeverSeen') : lang('Kartu.cardLastSeen', [tanggal_indo($token['last_seen_at'], true)]) ?>
                </p>
            <?php else : ?>
                <p class="text-gray-600 dark:text-gray-300"><?= lang('Kartu.cardNone') ?></p>
            <?php endif ?>
            <p class="text-gray-500 dark:text-gray-400"><?= CardAvailability::allows($applicant) ? lang('Kartu.applicantRule') : lang('Kartu.applicantWaits') ?></p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <a href="<?= site_url('admin/pendaftar/' . (int) $applicant['id'] . '/kartu') ?>" target="_blank" rel="noopener" class="<?= $button ?> bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-300 dark:focus:ring-primary-800">
                <?= icon('clipboard', 'h-4 w-4') ?><?= lang('Kartu.print') ?>
            </a>
            <?php if ($token !== null) : ?>
                <form method="post" action="<?= site_url('admin/pendaftar/' . (int) $applicant['id'] . '/kartu/baru') ?>" data-confirm="<?= esc(lang('Kartu.renewConfirm', [(string) $applicant['full_name']]), 'attr') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="<?= $button ?> w-full border border-gray-300 bg-white text-gray-900 hover:bg-gray-100 focus:ring-gray-100 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        <?= icon('key', 'h-4 w-4') ?><?= lang('Kartu.renew') ?>
                    </button>
                </form>
            <?php endif ?>
        </div>
        <?php if ($token !== null) : ?>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400"><?= lang('Kartu.renewHelp') ?></p>
        <?php endif ?>
    <?php endif ?>
</section>
