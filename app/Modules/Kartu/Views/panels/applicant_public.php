<?php
/**
 * Card section on the applicant's own page (slot applicant_public): download
 * button, or why the card is not available yet.
 *
 * @var array<string, mixed> $applicant applicants row
 */
use Modules\Kartu\Services\CardAvailability;

if (empty($applicant['id'])) {
    return;
}

$allowed   = CardAvailability::allows($applicant);
$cancelled = $applicant['status'] === 'cancelled';
?>
<section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800" aria-labelledby="panel-kartu-publik">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <?= icon('key', 'h-6 w-6 shrink-0 text-primary-700 dark:text-primary-400') ?>
            <div>
                <h2 id="panel-kartu-publik" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Kartu.panelTitle') ?></h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    <?= $allowed ? lang('Kartu.downloadHelp') : ($cancelled ? lang('Kartu.cancelledApplicant') : lang('Kartu.notYetText')) ?>
                </p>
            </div>
        </div>
        <?php if ($allowed) : ?>
            <a href="<?= site_url('cek-status/kartu') ?>" class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-3 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800">
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V4M7 14H5a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1h-2m-1-5-4 5-4-5m9 8h.01"/></svg>
                <?= lang('Kartu.download') ?>
            </a>
        <?php endif ?>
    </div>
</section>
