<?php
/**
 * Add or edit one AI connection (Super Admin). The saved API key is never sent back: the field stays
 * empty and only the last 4 characters are shown; leaving it empty keeps the saved key.
 *
 * @var array<string, mixed> $provider without api_key
 * @var bool                 $isNew
 * @var bool                 $hasKey  The app has an encryption key
 */
$action = $isNew ? url_to('asisten.koneksi.tambah') : url_to('asisten.koneksi.ubah', $provider['id']);
$card   = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$hint   = (string) ($provider['key_hint'] ?? '');
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= $action ?>" class="form-lebar" autocomplete="off">
    <?= csrf_field() ?>
    <?php if (! $hasKey) : ?>
        <div class="flex items-start gap-3 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-gray-800 dark:text-red-400" role="alert">
            <?= icon('triangle-alert', 'mt-0.5 h-5 w-5 shrink-0') ?><p><?= lang('Asisten.noEncryptionKey') ?></p>
        </div>
    <?php endif ?>
    <section class="<?= $card ?>">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <?= view('partials/form_field', ['field' => ['name' => 'name', 'label' => lang('Asisten.fieldName'), 'value' => (string) $provider['name'], 'required' => true, 'help' => lang('Asisten.fieldNameHelp'), 'attrs' => ['maxlength' => '80']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'model', 'label' => lang('Asisten.fieldModel'), 'value' => (string) $provider['model'], 'required' => true, 'help' => lang('Asisten.fieldModelHelp'), 'attrs' => ['maxlength' => '120', 'dir' => 'ltr', 'spellcheck' => 'false']]]) ?>
            <div class="md:col-span-2">
                <?= view('partials/form_field', ['field' => ['name' => 'base_url', 'type' => 'url', 'label' => lang('Asisten.fieldBaseUrl'), 'value' => (string) $provider['base_url'], 'required' => true, 'help' => lang('Asisten.fieldBaseUrlHelp'), 'attrs' => ['maxlength' => '255', 'dir' => 'ltr', 'spellcheck' => 'false', 'inputmode' => 'url']]]) ?>
            </div>
            <div class="md:col-span-2">
                <?= view('partials/form_field', ['field' => [
                    'name'     => 'api_key',
                    'type'     => 'password',
                    'label'    => lang('Asisten.fieldApiKey'),
                    'value'    => '',
                    'required' => $isNew,
                    'help'     => $isNew ? lang('Asisten.fieldApiKeyHelpNew') : lang('Asisten.fieldApiKeyHelpKeep', [$hint]),
                    'attrs'    => ['maxlength' => '500', 'autocomplete' => 'new-password', 'dir' => 'ltr', 'spellcheck' => 'false'],
                ]]) ?>
            </div>
            <?= view('partials/form_field', ['field' => ['name' => 'timeout_seconds', 'type' => 'number', 'label' => lang('Asisten.fieldTimeout'), 'value' => (string) $provider['timeout_seconds'], 'required' => true, 'help' => lang('Asisten.fieldTimeoutHelp'), 'attrs' => ['min' => '3', 'max' => '120', 'step' => '1']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'max_tokens', 'type' => 'number', 'label' => lang('Asisten.fieldMaxTokens'), 'value' => (string) $provider['max_tokens'], 'required' => true, 'help' => lang('Asisten.fieldMaxTokensHelp'), 'attrs' => ['min' => '50', 'max' => '4000', 'step' => '1']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'temperature', 'type' => 'number', 'label' => lang('Asisten.fieldTemperature'), 'value' => (string) $provider['temperature'], 'required' => true, 'help' => lang('Asisten.fieldTemperatureHelp'), 'attrs' => ['min' => '0', 'max' => '2', 'step' => '0.05', 'inputmode' => 'decimal']]]) ?>
            <div class="flex items-end">
                <?= view('partials/form_field', ['field' => ['name' => 'is_active', 'type' => 'checkbox', 'label' => lang('Asisten.fieldActive'), 'value' => (string) (int) $provider['is_active']]]) ?>
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row">
        <a href="<?= url_to('asisten.ai') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Asisten.providerSave') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
