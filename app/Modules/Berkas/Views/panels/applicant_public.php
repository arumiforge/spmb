<?php

use Modules\Berkas\Services\PanelData;

/**
 * Documents section on the applicant's own page (/cek-status/data): one row
 * per active document type with upload / replace / "menyusul". Renders
 * nothing without an applicant or when no document types are active.
 *
 * @var array<string, mixed>|null $applicant
 */
$applicant = $applicant ?? null;

if (! is_array($applicant) || empty($applicant['id'])) {
    return;
}

$rows = PanelData::rows((int) $applicant['id']);

if ($rows === []) {
    return;
}

$errors   = session('berkas_error');
$deadline = PanelData::deadline();
$overall  = (string) ($applicant['document_status'] ?? 'incomplete');
$hasFollow = array_filter($rows, static fn (array $r): bool => (bool) $r['type']['may_follow']) !== [];
?>
<section id="berkas" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="berkas-title">
    <div class="border-b border-gray-200 p-4 sm:px-5 dark:border-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 id="berkas-title" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Berkas.panelTitle') ?></h2>
            <p class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <span><?= lang('Berkas.overallStatus') ?>:</span>
                <?= view('partials/status_badge', ['badge' => status_badge_for('documents', $overall)], ['saveData' => false]) ?>
            </p>
        </div>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300"><?= lang('Berkas.panelIntro') ?></p>
        <?php if ($deadline !== null && $hasFollow) : ?>
            <p class="mt-2 flex items-center gap-2 text-sm font-medium text-yellow-800 dark:text-yellow-300"><?= icon('clock', 'h-4 w-4') ?><?= esc(lang('Berkas.deadlineInfo', [$deadline])) ?></p>
        <?php endif ?>
        <?php if (($applicant['status'] ?? '') === 'cancelled') : ?>
            <p class="mt-2 text-sm text-red-700 dark:text-red-400"><?= lang('Berkas.cancelledInfo') ?></p>
        <?php endif ?>
    </div>
    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
        <?php foreach ($rows as $row) : ?>
            <?= view('Modules\Berkas\Views\partials\document_row', ['item' => [
                'row'       => $row,
                'mode'      => 'public',
                'applicant' => $applicant,
                'error'     => is_array($errors) ? ($errors[(int) $row['type']['id']] ?? null) : null,
            ]], ['saveData' => false]) ?>
        <?php endforeach ?>
    </ul>
</section>
