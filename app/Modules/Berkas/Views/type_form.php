<?php

use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\TemplateStorage;

/**
 * @var array<string, mixed> $type
 * @var bool                 $isNew
 */
$action  = $isNew ? url_to('berkas.jenis.tambah') : url_to('berkas.jenis.ubah', $type['id']);
$current = DocumentTypeModel::extensionsOf($type);
$posted  = old('formats');
$checked = is_array($posted) ? $posted : array_values(array_unique(array_map(static fn (string $e): string => $e === 'jpeg' ? 'jpg' : $e, $current)));
$errors  = session('errors');
$fmtErr  = is_array($errors) ? ($errors['formats'] ?? null) : null;
$card    = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$hasTpl  = ! $isNew && DocumentTypeModel::hasTemplate($type);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="form-lebar">
    <?= csrf_field() ?>
    <section class="<?= $card ?>">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><?= view('partials/form_field', ['field' => ['name' => 'name', 'label' => lang('Berkas.fieldName'), 'value' => $type['name'], 'required' => true, 'help' => lang('Berkas.fieldNameHelp'), 'attrs' => ['maxlength' => '100']]]) ?></div>
            <?= view('partials/form_field', ['field' => ['name' => 'code', 'label' => lang('Berkas.fieldCode'), 'value' => $type['code'], 'required' => true, 'help' => lang('Berkas.fieldCodeHelp'), 'attrs' => ['maxlength' => '30', 'pattern' => '[a-z0-9_-]+', 'autocapitalize' => 'none']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'max_size_kb', 'type' => 'number', 'label' => lang('Berkas.fieldMaxSize'), 'value' => (string) $type['max_size_kb'], 'required' => true, 'help' => lang('Berkas.fieldMaxSizeHelp'), 'attrs' => ['min' => '100', 'max' => '10240', 'step' => '1', 'inputmode' => 'numeric']]]) ?>
            <div class="sm:col-span-2"><?= view('partials/form_field', ['field' => ['name' => 'description', 'label' => lang('Berkas.fieldDescription'), 'value' => (string) ($type['description'] ?? ''), 'help' => lang('Berkas.fieldDescriptionHelp'), 'attrs' => ['maxlength' => '255']]]) ?></div>

            <fieldset class="sm:col-span-2" <?= $fmtErr !== null ? 'aria-describedby="formats-error"' : '' ?>>
                <legend class="mb-2 block text-sm font-medium <?= $fmtErr !== null ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white' ?>"><?= lang('Berkas.fieldFormats') ?></legend>
                <div class="flex flex-wrap gap-x-6 gap-y-3">
                    <?php foreach (['jpg' => 'Berkas.formatJpg', 'png' => 'Berkas.formatPng', 'pdf' => 'Berkas.formatPdf'] as $value => $label) : ?>
                        <label class="flex items-center gap-2 text-sm text-gray-900 dark:text-gray-300">
                            <input type="checkbox" name="formats[]" value="<?= $value ?>" <?= in_array($value, $checked, true) ? 'checked' : '' ?>
                                   class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800">
                            <?= lang($label) ?>
                        </label>
                    <?php endforeach ?>
                </div>
                <?php if ($fmtErr !== null) : ?>
                    <p id="formats-error" class="mt-2 text-sm text-red-600 dark:text-red-500"><?= esc($fmtErr) ?></p>
                <?php endif ?>
            </fieldset>

            <div class="space-y-3 sm:col-span-2">
                <?= view('partials/form_field', ['field' => ['name' => 'is_required', 'type' => 'checkbox', 'label' => lang('Berkas.fieldRequired'), 'value' => (string) (int) $type['is_required']]]) ?>
                <?= view('partials/form_field', ['field' => ['name' => 'may_follow', 'type' => 'checkbox', 'label' => lang('Berkas.fieldMayFollow'), 'value' => (string) (int) $type['may_follow']]]) ?>
                <?= view('partials/form_field', ['field' => ['name' => 'is_active', 'type' => 'checkbox', 'label' => lang('Berkas.fieldActive'), 'value' => (string) (int) $type['is_active']]]) ?>
            </div>
        </div>
    </section>

    <section class="<?= $card ?>" aria-labelledby="template-heading">
        <h2 id="template-heading" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Berkas.templateHeading') ?></h2>
        <p class="mb-4 mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Berkas.templateHelp') ?></p>
        <?php // One column: a plain stack, not a grid, so the fields keep their natural height (no D-095 row tracks). ?>
        <div class="space-y-5">
            <?php if ($hasTpl) : ?>
                <div class="flex flex-col gap-3 rounded-lg bg-gray-50 p-3 text-sm sm:flex-row sm:items-center sm:justify-between dark:bg-gray-700">
                    <p class="min-w-0 text-gray-900 dark:text-white">
                        <span class="text-gray-500 dark:text-gray-400"><?= lang('Berkas.templateCurrent') ?>:</span>
                        <span class="break-all font-medium"><?= esc(TemplateStorage::downloadName($type)) ?></span>
                    </p>
                    <a href="<?= url_to('berkas.jenis.contoh', $type['id']) ?>" class="inline-flex shrink-0 items-center gap-1.5 font-medium text-primary-700 hover:underline dark:text-primary-400"><?= icon('download', 'h-4 w-4') ?><?= lang('Berkas.templateDownload') ?></a>
                </div>
            <?php endif ?>
            <?= view('partials/form_field', ['field' => ['name' => 'template', 'type' => 'file', 'label' => lang('Berkas.templateFile'), 'help' => lang('Berkas.templateFileHelp'), 'attrs' => ['accept' => TemplateStorage::accept()]]]) ?>
            <?php if ($hasTpl) : ?>
                <?= view('partials/form_field', ['field' => ['name' => 'remove_template', 'type' => 'checkbox', 'label' => lang('Berkas.templateRemove'), 'value' => '0']]) ?>
            <?php endif ?>
            <?= view('partials/form_field', ['field' => ['name' => 'template_label', 'label' => lang('Berkas.templateLabelField'), 'value' => (string) ($type['template_label'] ?? ''), 'help' => lang('Berkas.templateLabelHelp'), 'placeholder' => lang('Berkas.templateDefaultLabel'), 'attrs' => ['maxlength' => '60']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'template_enabled', 'type' => 'checkbox', 'label' => lang('Berkas.templateEnabled'), 'value' => (string) (int) ($type['template_enabled'] ?? 0)]]) ?>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row">
        <a href="<?= url_to('berkas.pengaturan') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Berkas.saveType') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
