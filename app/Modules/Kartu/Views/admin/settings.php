<?php
/**
 * Card settings (/admin/pengaturan/kartu).
 *
 * @var array{values: array<string, string>} $settings
 */
$values = $settings['values'];
$h2     = 'mb-4 text-lg font-semibold text-gray-900 dark:text-white';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= url_to('kartu.pengaturan') ?>" class="form-lebar">
    <?= csrf_field() ?>

    <?= lipatan_buka(['id' => 'kartu-availability', 'icon' => 'id-card', 'title' => lang('Kartu.sectionAvailability')]) ?>
        <fieldset>
            <legend class="mb-3 text-sm font-medium text-gray-900 dark:text-white"><?= lang('Kartu.availableWhen') ?></legend>
            <div class="space-y-3">
                <?php foreach (['immediately' => lang('Kartu.availableImmediately'), 'verified' => lang('Kartu.availableVerified')] as $value => $label) : ?>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-4 hover:bg-gray-50 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50 dark:border-gray-600 dark:hover:bg-gray-700 dark:has-[:checked]:bg-gray-700">
                        <input type="radio" name="availableWhen" value="<?= $value ?>" <?= old('availableWhen', $values['availableWhen']) === $value ? 'checked' : '' ?>
                               class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-primary-600">
                        <span class="text-sm font-medium text-gray-900 dark:text-white"><?= esc($label) ?></span>
                    </label>
                <?php endforeach ?>
            </div>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400"><?= lang('Kartu.availableWhenHelp') ?></p>
        </fieldset>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'kartu-text', 'icon' => 'file-text', 'title' => lang('Kartu.sectionText')]) ?>
        <div class="space-y-5">
            <?= view('partials/form_field', ['field' => ['name' => 'headerText', 'label' => lang('Kartu.headerText'), 'value' => $values['headerText'], 'required' => true, 'help' => lang('Kartu.headerTextHelp'), 'attrs' => ['maxlength' => '80']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'footerNote', 'label' => lang('Kartu.footerNote'), 'value' => $values['footerNote'], 'help' => lang('Kartu.footerNoteHelp'), 'attrs' => ['maxlength' => '150']]]) ?>
            <p class="flex items-start gap-2 text-sm text-gray-500 dark:text-gray-400"><?= icon('info', 'h-5 w-5 shrink-0') ?><?= lang('Kartu.preview') ?></p>
        </div>
    <?= lipatan_tutup() ?>

    <div class="flex justify-end">
        <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= lang('Kartu.save') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
