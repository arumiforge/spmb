<?php
/**
 * Add or edit a wave.
 *
 * @var array<string, mixed>|null $wave
 */
$f = static fn (string $name, string $label, array $extra = []): string => view('partials/form_field', ['field' => $extra + [
    'name'  => $name,
    'label' => $label,
    'value' => $wave[$name] ?? '',
]]);
$action = $wave === null ? url_to('gelombang') : url_to('gelombang.simpan', $wave['id']);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= esc($action, 'attr') ?>" class="max-w-3xl space-y-6">
    <?= csrf_field() ?>
    <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><?= $f('name', lang('Pendaftaran.waveName'), ['required' => true, 'help' => lang('Pendaftaran.waveNameHelp')]) ?></div>
            <?= $f('starts_on', lang('Pendaftaran.waveStartsOn'), ['type' => 'date', 'required' => true]) ?>
            <?= $f('ends_on', lang('Pendaftaran.waveEndsOn'), ['type' => 'date', 'required' => true, 'help' => lang('Pendaftaran.waveDatesHelp')]) ?>
            <?= $f('quota', lang('Pendaftaran.waveQuota'), ['type' => 'number', 'help' => lang('Pendaftaran.waveQuotaHelp'), 'attrs' => ['min' => '1', 'inputmode' => 'numeric']]) ?>
            <div class="sm:col-span-2"><?= $f('quota_mode', lang('Pendaftaran.waveQuotaMode'), ['type' => 'select', 'required' => true, 'options' => lang('Pendaftaran.waveQuotaModes'), 'value' => $wave['quota_mode'] ?? 'none', 'help' => lang('Pendaftaran.waveQuotaModeHelp')]) ?></div>
            <?= $f('online_quota', lang('Pendaftaran.waveOnlineQuota'), ['type' => 'number', 'help' => lang('Pendaftaran.waveOnlineQuotaHelp'), 'attrs' => ['min' => '1', 'inputmode' => 'numeric']]) ?>
            <div class="sm:col-span-2"><?= $f('description', lang('Pendaftaran.waveDescription'), ['type' => 'textarea']) ?></div>
            <div class="sm:col-span-2"><?= $f('is_active', lang('Pendaftaran.waveActive'), ['type' => 'checkbox', 'value' => $wave === null ? '1' : (string) $wave['is_active']]) ?></div>
        </div>
    </section>
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="<?= url_to('gelombang') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('Pendaftaran.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Pendaftaran.save') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
