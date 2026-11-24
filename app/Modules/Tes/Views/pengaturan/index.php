<?php
/**
 * Placement test settings.
 *
 * @var array<string, mixed>  $values
 * @var array<string, string> $statuses code => label (cancelled left out)
 */

use Modules\Tes\Controllers\Pengaturan;

$f = static fn (string $name, string $label, array $extra = []): string => view('partials/form_field', ['field' => $extra + [
    'name'  => $name,
    'label' => $label,
    'value' => $values[$name] ?? '',
]]);
$h2      = 'mb-4 text-lg font-semibold text-gray-900 dark:text-white';
$help    = 'ms-7 mt-1 text-sm text-gray-500 dark:text-gray-400';
$errors  = session('errors');
$checked = old('eligibleStatuses', $values['eligibleStatuses']);
$checked = is_array($checked) ? $checked : [];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="tata-isi" data-lipatan-lingkup>
<form id="form-pengaturan-tes" method="post" action="<?= url_to('tes.pengaturan') ?>" class="form-lebar">
    <?= csrf_field() ?>
    <?= view('partials/lipatan_semua', [], ['saveData' => false]) ?>

    <?= lipatan_buka(['id' => 'tes-applicant', 'icon' => 'users', 'title' => lang('TesJadwal.settingsSectionApplicant')]) ?>
        <div class="space-y-5">
            <div>
                <?= $f('scheduleVisible', lang('TesJadwal.scheduleVisible'), ['type' => 'checkbox']) ?>
                <p class="<?= $help ?>"><?= lang('TesJadwal.scheduleVisibleHelp') ?></p>
            </div>
            <?= $f('instructions', lang('TesJadwal.instructions'), ['type' => 'textarea', 'rows' => 3, 'help' => lang('TesJadwal.instructionsHelp', [Pengaturan::INSTRUCTIONS_MAX]), 'attrs' => ['maxlength' => (string) Pengaturan::INSTRUCTIONS_MAX]]) ?>
        </div>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'tes-allocate', 'icon' => 'layout-grid', 'title' => lang('TesJadwal.settingsSectionAllocate')]) ?>
        <div class="space-y-5">
            <fieldset aria-describedby="eligible-help">
                <legend class="mb-2 block text-sm font-medium <?= isset($errors['eligibleStatuses']) ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white' ?>"><?= lang('TesJadwal.eligibleStatuses') ?></legend>
                <input type="hidden" name="eligibleStatuses[]" value="">
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <?php foreach ($statuses as $code => $label) : ?>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="eligibleStatuses[]" value="<?= esc($code, 'attr') ?>" <?= in_array($code, $checked, true) ? 'checked' : '' ?>
                                   class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <?= esc($label) ?>
                        </label>
                    <?php endforeach ?>
                </div>
                <p id="eligible-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.eligibleStatusesHelp') ?></p>
                <?php if (isset($errors['eligibleStatuses'])) : ?>
                    <p class="mt-2 text-sm text-red-600 dark:text-red-500"><?= esc($errors['eligibleStatuses']) ?></p>
                <?php endif ?>
            </fieldset>
            <div>
                <?= $f('requireCompleteDocuments', lang('TesJadwal.requireCompleteDocuments'), ['type' => 'checkbox']) ?>
                <p class="<?= $help ?>"><?= lang('TesJadwal.requireCompleteHelp') ?></p>
            </div>
        </div>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'tes-generator', 'icon' => 'clock', 'title' => lang('TesJadwal.settingsSectionGenerator')]) ?>
        <div class="sm:w-1/2">
            <?= $f('defaultDurationMinutes', lang('TesJadwal.defaultDuration'), ['type' => 'number', 'required' => true, 'help' => lang('TesJadwal.defaultDurationHelp'), 'attrs' => ['min' => '30', 'max' => '480', 'step' => '5', 'inputmode' => 'numeric']]) ?>
        </div>
    <?= lipatan_tutup() ?>

    <div class="flex justify-end">
        <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= lang('TesJadwal.save') ?></button>
    </div>
</form>
<?= view('partials/tata_isi_rail', ['form' => 'form-pengaturan-tes', 'save' => lang('TesJadwal.save')], ['saveData' => false]) ?>
</div>
<?= $this->endSection() ?>
