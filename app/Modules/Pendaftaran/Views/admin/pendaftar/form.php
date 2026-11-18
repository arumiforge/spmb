<?php
/**
 * Offline registration (FR-REG-02) and staff edits of applicant data.
 *
 * @var array<string, mixed>|null             $applicant  null when adding
 * @var Modules\Pendaftaran\Libraries\ApplicantForm $form
 * @var array<string, mixed>                  $values
 * @var list<string>|null                     $fields
 * @var array<string, string>                 $waves
 * @var list<array<string, mixed>>            $duplicates
 */
$action = $applicant === null ? url_to('pendaftar.tambah') : url_to('pendaftar.ubah', $applicant['id']);
$back   = $applicant === null ? url_to('pendaftar') : url_to('pendaftar.detail', $applicant['id']);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= esc($action, 'attr') ?>" class="max-w-3xl space-y-6" novalidate>
    <?= csrf_field() ?>

    <?php if ($duplicates !== []) : ?>
        <section class="rounded-lg border border-yellow-300 bg-yellow-50 p-5 sm:p-6 dark:border-yellow-800 dark:bg-gray-800" role="alert">
            <h2 class="mb-1 flex items-center gap-2 text-lg font-semibold text-yellow-900 dark:text-yellow-300"><?= icon('warning', 'h-5 w-5') ?><?= lang('Pendaftaran.duplicateTitle') ?></h2>
            <p class="mb-4 text-sm text-yellow-900 dark:text-gray-300"><?= lang('Pendaftaran.duplicateIntro') ?></p>
            <?= view('Modules\Pendaftaran\Views\partials\duplicates', ['duplicates' => ['rows' => $duplicates, 'link' => true]]) ?>
            <label class="mt-4 flex items-start gap-3 text-sm font-medium text-gray-900 dark:text-white">
                <input type="checkbox" name="confirm_duplicate" value="1" class="mt-0.5 h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                <?= lang('Pendaftaran.duplicateConfirm') ?>
            </label>
        </section>
    <?php endif ?>

    <?= view('Modules\Pendaftaran\Views\partials\applicant_fields', ['applicantForm' => [
        'form'   => $form,
        'values' => $values,
        'fields' => $fields,
        'waves'  => $waves,
    ]]) ?>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="<?= esc($back, 'attr') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('Pendaftaran.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Pendaftaran.saveApplicant') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
