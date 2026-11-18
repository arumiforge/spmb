<?php
/**
 * Applicant detail for staff; other modules add sections via the applicant_admin panel.
 *
 * @var array<string, mixed>       $applicant
 * @var string                     $waveName
 * @var string                     $schoolName
 * @var string|null                $creatorName
 * @var list<array<string, mixed>> $duplicates
 * @var bool                       $canEdit
 * @var bool                       $canDelete
 * @var string|null                $privateLink
 */
$a       = $applicant;
$card    = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2      = 'mb-4 text-lg font-semibold text-gray-900 dark:text-white';
$input   = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
$label   = static fn (string $f): string => lang('Pendaftaran.fields.' . $f);
$value   = static fn ($v): string => $v === null || $v === '' ? '-' : (string) $v;
$gender  = lang('Status.gender');
$rows    = [
    'sectionStudent' => [
        'full_name'  => $a['full_name'],
        'gender'     => $gender[$a['gender']] ?? $a['gender'],
        'birth_place' => $a['birth_place'],
        'birth_date' => tanggal_indo($a['birth_date']),
        'nisn'       => $a['nisn'],
        'nik'        => $a['nik'],
        'kk_number'  => $a['kk_number'],
        'religion'   => $a['religion'],
        'phone'      => $a['phone'],
    ],
    'sectionAddress' => [
        'address'  => $a['address'],
        'village'  => $a['village'],
        'district' => $a['district'],
        'regency'  => $a['regency'],
        'province' => $a['province'],
    ],
    'sectionParents' => [
        'father_name'       => $a['father_name'],
        'father_occupation' => $a['father_occupation'],
        'mother_name'       => $a['mother_name'],
        'mother_occupation' => $a['mother_occupation'],
        'guardian_name'     => $a['guardian_name'],
        'guardian_relation' => $a['guardian_relation'],
        'parent_phone'      => $a['parent_phone'],
        'parent_email'      => $a['parent_email'],
    ],
    'sectionSchool' => [
        'previous_school_name' => $schoolName,
        'graduation_year'      => $a['graduation_year'],
    ],
];
$waPhone = preg_replace('/\D/', '', (string) $a['parent_phone']) ?? '';
$waPhone = str_starts_with($waPhone, '0') ? '62' . substr($waPhone, 1) : $waPhone;
$button  = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<?php if ($canEdit) : ?>
    <a href="<?= url_to('pendaftar.ubah', $a['id']) ?>" class="<?= $button ?> bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Pendaftaran.edit') ?></a>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <?php if ($privateLink !== null) : ?>
            <section class="rounded-lg border border-primary-200 bg-primary-50 p-5 sm:p-6 dark:border-primary-800 dark:bg-gray-800" role="status">
                <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.linkTitle') ?></h2>
                <p class="mb-3 text-sm text-gray-700 dark:text-gray-300"><?= lang('Pendaftaran.linkOnce') ?> <?= lang('Pendaftaran.linkIntro') ?></p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input type="text" readonly value="<?= esc($privateLink, 'attr') ?>" aria-label="<?= lang('Pendaftaran.linkTitle') ?>" class="<?= $input ?> font-mono">
                    <button type="button" data-copy="<?= esc($privateLink, 'attr') ?>" class="<?= $button ?> border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= icon('copy', 'h-4 w-4') ?><?= lang('App.copy') ?></button>
                    <?php if ($waPhone !== '') : ?>
                        <a href="https://wa.me/<?= esc($waPhone, 'url') ?>?text=<?= rawurlencode(lang('Pendaftaran.whatsappText', [setting('School.name'), $a['full_name'], $a['registration_number'], $privateLink])) ?>" target="_blank" rel="noopener" class="<?= $button ?> bg-green-600 text-white hover:bg-green-700"><?= lang('Pendaftaran.whatsappShare') ?></a>
                    <?php endif ?>
                </div>
            </section>
        <?php endif ?>

        <?php if ((int) $a['possible_duplicate'] === 1) : ?>
            <section class="rounded-lg border border-yellow-300 bg-yellow-50 p-5 sm:p-6 dark:border-yellow-800 dark:bg-gray-800">
                <h2 class="mb-1 flex items-center gap-2 text-lg font-semibold text-yellow-900 dark:text-yellow-300"><?= icon('warning', 'h-5 w-5') ?><?= lang('Pendaftaran.reviewTitle') ?></h2>
                <p class="mb-4 text-sm text-yellow-900 dark:text-gray-300"><?= lang('Pendaftaran.reviewIntro') ?></p>
                <?php if ($duplicates === []) : ?>
                    <p class="mb-4 text-sm text-gray-700 dark:text-gray-300"><?= lang('Pendaftaran.duplicateNone') ?></p>
                <?php else : ?>
                    <?= view('Modules\Pendaftaran\Views\partials\duplicates', ['duplicates' => ['rows' => $duplicates, 'link' => true]]) ?>
                <?php endif ?>
                <?php if ($canEdit) : ?>
                    <form method="post" action="<?= url_to('pendaftar.data-ganda', $a['id']) ?>" class="mt-4 space-y-3">
                        <?= csrf_field() ?>
                        <?= view('partials/form_field', ['field' => ['name' => 'note', 'type' => 'textarea', 'rows' => 2, 'label' => lang('Pendaftaran.reviewNote'), 'required' => true]]) ?>
                        <button type="submit" class="<?= $button ?> w-full bg-yellow-600 text-white hover:bg-yellow-700 sm:w-auto"><?= lang('Pendaftaran.reviewButton') ?></button>
                    </form>
                <?php endif ?>
            </section>
        <?php endif ?>

        <?php foreach ($rows as $section => $fields) : ?>
            <section class="<?= $card ?>">
                <h2 class="<?= $h2 ?>"><?= lang('Pendaftaran.' . $section) ?></h2>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <?php foreach ($fields as $field => $v) : ?>
                        <div class="min-w-0">
                            <dt class="text-gray-500 dark:text-gray-400"><?= $label($field) ?></dt>
                            <dd class="mt-0.5 break-words font-medium text-gray-900 dark:text-white"><?= esc($value($v)) ?></dd>
                        </div>
                    <?php endforeach ?>
                </dl>
            </section>
        <?php endforeach ?>

        <?= module_panels('applicant_admin', ['applicant' => $a]) ?>
    </div>

    <div class="space-y-6">
        <section class="<?= $card ?>">
            <h2 class="<?= $h2 ?>"><?= lang('Pendaftaran.detailRegistration') ?></h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.numberLabel') ?></dt><dd class="mt-0.5 font-mono text-base font-semibold text-gray-900 dark:text-white"><?= esc($a['registration_number']) ?></dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.fields.wave_id') ?></dt><dd class="mt-0.5 text-gray-900 dark:text-white"><?= esc($waveName) ?></dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.statusTitle') ?></dt><dd class="mt-1"><?= view('partials/status_badge', ['badge' => status_badge_for('applicant', $a['status'])]) ?></dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.documents') ?></dt><dd class="mt-1"><?= view('partials/status_badge', ['badge' => status_badge_for('documents', $a['document_status'])]) ?></dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.method') ?></dt><dd class="mt-1"><?= view('partials/status_badge', ['badge' => status_badge_for('method', $a['method'])]) ?></dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.registeredAt') ?></dt><dd class="mt-0.5 text-gray-900 dark:text-white"><?= esc(tanggal_indo($a['registered_at'], true)) ?></dd></div>
                <?php if ($creatorName !== null) : ?>
                    <div><dt class="text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.registeredBy') ?></dt><dd class="mt-0.5 text-gray-900 dark:text-white"><?= esc($creatorName) ?></dd></div>
                <?php endif ?>
                <?php if (! empty($a['notes'])) : ?>
                    <div><dt class="text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.fields.notes') ?></dt><dd class="mt-0.5 whitespace-pre-line text-gray-900 dark:text-white"><?= esc($a['notes']) ?></dd></div>
                <?php endif ?>
            </dl>
        </section>

        <?php if ($canEdit) : ?>
            <section class="<?= $card ?>">
                <h2 class="<?= $h2 ?>"><?= lang('Pendaftaran.statusTitle') ?></h2>
                <form method="post" action="<?= url_to('pendaftar.status', $a['id']) ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <?= view('partials/form_field', ['field' => ['name' => 'status', 'type' => 'select', 'label' => lang('Pendaftaran.statusTitle'), 'options' => lang('Status.applicant'), 'value' => $a['status'], 'required' => true]]) ?>
                    <?= view('partials/form_field', ['field' => ['name' => 'note', 'id' => 'status-note', 'label' => lang('Pendaftaran.statusNote')]]) ?>
                    <button type="submit" class="<?= $button ?> w-full bg-primary-600 text-white hover:bg-primary-700"><?= lang('Pendaftaran.statusSave') ?></button>
                </form>
            </section>

            <section class="<?= $card ?>">
                <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.linkTitle') ?></h2>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.linkIntro') ?></p>
                <form method="post" action="<?= url_to('pendaftar.tautan', $a['id']) ?>" data-confirm="<?= esc(lang('Pendaftaran.linkRenewConfirm'), 'attr') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="<?= $button ?> w-full border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('key', 'h-4 w-4') ?><?= lang('Pendaftaran.linkRenew') ?></button>
                </form>
            </section>
        <?php endif ?>

        <?php if ($canDelete) : ?>
            <form method="post" action="<?= url_to('pendaftar.hapus', $a['id']) ?>" data-confirm="<?= esc(lang('Pendaftaran.deleteConfirm', [$a['full_name'], $a['registration_number']]), 'attr') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="<?= $button ?> w-full border border-red-200 bg-white text-red-700 hover:bg-red-50 dark:border-red-800 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700"><?= lang('Pendaftaran.deleteButton') ?></button>
            </form>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
