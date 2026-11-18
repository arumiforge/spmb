<?php

use Modules\Berkas\Services\PanelData;

/**
 * Documents section on the staff applicant page (/admin/pendaftar/<id>):
 * list, view, upload on behalf (pendaftar.edit) and verify (pendaftar.verifikasi).
 * Renders nothing without an applicant or when no document types are active.
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

$user      = auth()->user();
$canEdit   = $user !== null && $user->can('pendaftar.edit');
$canVerify = $user !== null && $user->can('pendaftar.verifikasi');
$errors    = session('berkas_error');
$overall   = (string) ($applicant['document_status'] ?? 'incomplete');
?>
<section id="berkas" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="berkas-title">
    <div class="border-b border-gray-200 p-4 sm:px-5 dark:border-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 id="berkas-title" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Berkas.panelTitle') ?></h2>
            <?= view('partials/status_badge', ['badge' => status_badge_for('documents', $overall)], ['saveData' => false]) ?>
        </div>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300"><?= lang('Berkas.panelIntroStaff') ?></p>
    </div>
    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
        <?php foreach ($rows as $row) : ?>
            <?= view('Modules\Berkas\Views\partials\document_row', ['item' => [
                'row'       => $row,
                'mode'      => 'admin',
                'applicant' => $applicant,
                'error'     => is_array($errors) ? ($errors[(int) $row['type']['id']] ?? null) : null,
                'canEdit'   => $canEdit,
                'canVerify' => $canVerify,
            ]], ['saveData' => false]) ?>
        <?php endforeach ?>
    </ul>
</section>
