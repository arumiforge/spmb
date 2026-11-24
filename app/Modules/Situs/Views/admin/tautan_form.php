<?php
/**
 * Add or edit one link of the footer's "Tautan" column.
 *
 * @var array{label: string, url: string, active: bool} $link
 * @var int|null                                        $position Null for a new link
 */
$action = $position === null ? url_to('situs.tautan.tambah') : url_to('situs.tautan.ubah', $position);
$card   = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= $action ?>" class="form-lebar">
    <?= csrf_field() ?>
    <section class="<?= $card ?>">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <?= view('partials/form_field', ['field' => ['name' => 'label', 'label' => lang('Situs.linkFieldLabel'), 'value' => $link['label'], 'required' => true, 'help' => lang('Situs.linkFieldLabelHelp'), 'attrs' => ['maxlength' => '40']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'url', 'label' => lang('Situs.linkFieldUrl'), 'value' => $link['url'], 'required' => true, 'help' => lang('Situs.linkHelp'), 'placeholder' => '/jadwal', 'attrs' => ['maxlength' => '255', 'autocomplete' => 'off', 'inputmode' => 'url']]]) ?>
            <div class="md:col-span-2">
                <?= view('partials/form_field', ['field' => ['name' => 'active', 'type' => 'checkbox', 'label' => lang('Situs.linkFieldActive'), 'value' => $link['active'] ? '1' : '0']]) ?>
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row">
        <a href="<?= url_to('situs.tautan.admin') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Situs.linkSave') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
