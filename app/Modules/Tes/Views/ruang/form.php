<?php
/**
 * Add or edit a test room.
 *
 * @var array<string, mixed>|null $room
 */
$f = static fn (string $name, string $label, array $extra = []): string => view('partials/form_field', ['field' => $extra + [
    'name'  => $name,
    'label' => $label,
    'value' => $room[$name] ?? '',
]]);
$action = $room === null ? url_to('tes.ruang.tambah') : url_to('tes.ruang.ubah', $room['id']);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= esc($action, 'attr') ?>" class="form-lebar">
    <?= csrf_field() ?>
    <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><?= $f('name', lang('TesJadwal.roomName'), ['required' => true, 'help' => lang('TesJadwal.roomNameHelp'), 'attrs' => ['maxlength' => '60']]) ?></div>
            <?= $f('capacity', lang('TesJadwal.roomCapacity'), ['type' => 'number', 'required' => true, 'help' => lang('TesJadwal.roomCapacityHelp'), 'attrs' => ['min' => '1', 'max' => '999', 'inputmode' => 'numeric']]) ?>
            <?= $f('sort_order', lang('TesJadwal.roomOrder'), ['type' => 'number', 'help' => lang('TesJadwal.roomOrderHelp'), 'value' => $room['sort_order'] ?? '0', 'attrs' => ['min' => '0', 'max' => '9999', 'inputmode' => 'numeric']]) ?>
            <div class="sm:col-span-2"><?= $f('location', lang('TesJadwal.roomLocation'), ['help' => lang('TesJadwal.roomLocationHelp'), 'attrs' => ['maxlength' => '100']]) ?></div>
            <div class="sm:col-span-2">
                <?= $f('is_active', lang('TesJadwal.roomActive'), ['type' => 'checkbox', 'value' => $room === null ? '1' : (string) $room['is_active']]) ?>
                <p class="ms-7 mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.roomDeactivateHint') ?></p>
            </div>
        </div>
    </section>
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="<?= url_to('tes.ruang') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('TesJadwal.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('TesJadwal.save') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
