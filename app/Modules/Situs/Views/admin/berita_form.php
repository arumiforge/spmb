<?php
/**
 * Add or edit one news item of the ticker.
 *
 * @var array<string, mixed> $item
 * @var bool                 $isNew
 */
$action = $isNew ? url_to('situs.berita.tambah') : url_to('situs.berita.ubah', $item['id']);
$card   = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= $action ?>" class="form-lebar">
    <?= csrf_field() ?>
    <section class="<?= $card ?>">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <?= view('partials/form_field', ['field' => ['name' => 'text', 'type' => 'textarea', 'rows' => 2, 'label' => lang('Situs.newsFieldText'), 'value' => (string) $item['text'], 'required' => true, 'help' => lang('Situs.newsFieldTextHelp'), 'attrs' => ['maxlength' => '160']]]) ?>
            </div>
            <div class="md:col-span-2">
                <?= view('partials/form_field', ['field' => ['name' => 'url', 'label' => lang('Situs.newsFieldUrl'), 'value' => (string) $item['url'], 'help' => lang('Situs.linkHelp'), 'placeholder' => '/jadwal', 'attrs' => ['maxlength' => '255', 'autocomplete' => 'off', 'inputmode' => 'url']]]) ?>
            </div>
            <?= view('partials/form_field', ['field' => ['name' => 'starts_on', 'type' => 'date', 'label' => lang('Situs.fieldStartsOn'), 'value' => (string) ($item['starts_on'] ?? ''), 'help' => lang('Situs.dateHelp')]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'ends_on', 'type' => 'date', 'label' => lang('Situs.fieldEndsOn'), 'value' => (string) ($item['ends_on'] ?? ''), 'help' => lang('Situs.dateHelp')]]) ?>
            <div class="md:col-span-2">
                <?= view('partials/form_field', ['field' => ['name' => 'is_active', 'type' => 'checkbox', 'label' => lang('Situs.newsFieldActive'), 'value' => (string) (int) $item['is_active']]]) ?>
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row">
        <a href="<?= url_to('situs.berita.admin') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Situs.newsSave') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
