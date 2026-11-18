<?php
/**
 * The applicant's own page (after the status check or private link). Other
 * modules add sections (documents, card) through the applicant_public panel.
 *
 * @var array<string, mixed>                        $applicant
 * @var string                                      $waveName
 * @var string                                      $schoolName
 * @var Modules\Pendaftaran\Libraries\ApplicantForm $form
 */

use Modules\Pendaftaran\Models\ApplicantModel;

$a      = $applicant;
$card   = 'rounded-xl border border-gray-200 bg-white p-5 shadow-xs sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$gender = lang('Status.gender');
$shown  = [
    'full_name'            => $a['full_name'],
    'gender'               => $gender[$a['gender']] ?? $a['gender'],
    'birth_place'          => $a['birth_place'],
    'birth_date'           => tanggal_indo($a['birth_date']),
    'nisn'                 => $a['nisn'],
    'nik'                  => ApplicantModel::maskNik($a['nik']),
    'kk_number'            => ApplicantModel::maskNik($a['kk_number']),
    'religion'             => $a['religion'],
    'phone'                => $a['phone'],
    'address'              => $a['address'],
    'village'              => $a['village'],
    'district'             => $a['district'],
    'regency'              => $a['regency'],
    'province'             => $a['province'],
    'father_name'          => $a['father_name'],
    'father_occupation'    => $a['father_occupation'],
    'mother_name'          => $a['mother_name'],
    'mother_occupation'    => $a['mother_occupation'],
    'guardian_name'        => $a['guardian_name'],
    'guardian_relation'    => $a['guardian_relation'],
    'parent_phone'         => $a['parent_phone'],
    'parent_email'         => $a['parent_email'],
    'previous_school_name' => $schoolName,
    'graduation_year'      => $a['graduation_year'],
];
// Show the fields the form asks, plus any hidden field that holds a value.
$shown = array_filter($shown, static fn ($v, string $f): bool => $form->shows($f) || ($a[$f] ?? null) !== null, ARRAY_FILTER_USE_BOTH);
$button = 'inline-flex items-center justify-center gap-2 rounded-lg px-5 py-3 text-base font-medium';
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.myTitle') ?></p>
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= esc(lang('Pendaftaran.myGreeting', [$a['full_name']])) ?></h1>
            <p class="mt-1 font-mono text-lg text-gray-700 dark:text-gray-300"><?= esc($a['registration_number']) ?></p>
        </div>
        <form method="post" action="<?= url_to('cek-status.keluar') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="<?= $button ?> w-full border border-gray-200 bg-white py-2.5 text-sm text-gray-900 hover:bg-gray-100 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"><?= icon('logout', 'h-4 w-4') ?><?= lang('Pendaftaran.mySignOut') ?></button>
        </form>
    </div>

    <div class="mt-6 space-y-6">
        <section class="<?= $card ?>">
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.myStatus') ?></dt>
                    <dd class="mt-1"><?= view('partials/status_badge', ['badge' => status_badge_for('applicant', $a['status'])]) ?></dd>
                    <dd class="mt-2 text-sm text-gray-600 dark:text-gray-300"><?= lang('Pendaftaran.myStatusHelp.' . $a['status']) ?></dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.myDocuments') ?></dt>
                    <dd class="mt-1"><?= view('partials/status_badge', ['badge' => status_badge_for('documents', $a['document_status'])]) ?></dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.myWave') ?></dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= esc($waveName) ?></dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.myRegisteredAt') ?></dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white"><?= esc(tanggal_indo($a['registered_at'])) ?></dd>
                </div>
            </dl>
        </section>

        <?= module_panels('applicant_public', ['applicant' => $a]) ?>

        <section class="<?= $card ?>">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.myData') ?></h2>
                <?php if ($a['status'] !== 'cancelled') : ?>
                    <a href="<?= url_to('cek-status.ubah') ?>" class="<?= $button ?> border border-gray-200 bg-white py-2.5 text-sm text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Pendaftaran.myEdit') ?></a>
                <?php endif ?>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <?php foreach ($shown as $field => $v) : ?>
                    <div class="min-w-0">
                        <dt class="text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.fields.' . $field) ?></dt>
                        <dd class="mt-0.5 break-words font-medium text-gray-900 dark:text-white"><?= esc($v === null || $v === '' ? '-' : (string) $v) ?></dd>
                    </div>
                <?php endforeach ?>
            </dl>
            <p class="mt-5 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.myEditNote') ?></p>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
