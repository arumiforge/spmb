<?php
/**
 * Applicant corrects contact and address fields (identity fields: staff only).
 *
 * @var array<string, mixed>                        $applicant
 * @var Modules\Pendaftaran\Libraries\ApplicantForm $form
 * @var list<string>                                $fields
 */
$card = 'rounded-xl border border-gray-200 bg-white p-5 shadow-xs sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$big  = '[&_input]:py-3 [&_input]:text-base [&_select]:py-3 [&_select]:text-base [&_textarea]:text-base';
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
    <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= lang('Pendaftaran.editTitlePublic') ?></h1>
    <p class="mt-2 text-base text-gray-600 dark:text-gray-300"><?= esc($applicant['full_name']) ?> · <span class="font-mono"><?= esc($applicant['registration_number']) ?></span></p>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.myEditNote') ?></p>

    <form method="post" action="<?= url_to('cek-status.ubah') ?>" class="mt-6 space-y-6 <?= $big ?>" novalidate>
        <?= csrf_field() ?>
        <?= view('Modules\Pendaftaran\Views\partials\applicant_fields', ['applicantForm' => ['form' => $form, 'values' => $applicant, 'fields' => $fields, 'card' => $card]]) ?>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="<?= url_to('cek-status.data') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-3 text-center text-base font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"><?= lang('Pendaftaran.cancel') ?></a>
            <button type="submit" class="rounded-lg bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Pendaftaran.editSave') ?></button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
