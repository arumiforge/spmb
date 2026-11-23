<?php
/**
 * Add / edit one previous school.
 *
 * @var array<string, mixed>|null $school
 * @var int                       $usedBy
 * @var list<string>              $provinces
 */

use Modules\Referensi\Models\SchoolModel;

$editing = $school !== null;
$action  = $editing ? url_to('referensi.sekolah.ubah', $school['id']) : url_to('referensi.sekolah.tambah');
$card    = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$btnSec  = 'rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700';
$levels  = array_combine(SchoolModel::LEVELS, SchoolModel::LEVELS);
$owners  = ['' => lang('Referensi.chooseOwnership'), 'negeri' => lang('Referensi.ownership.negeri'), 'swasta' => lang('Referensi.ownership.swasta')];
$active  = $editing && (int) $school['is_active'] === 1;
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <form method="post" action="<?= $action ?>" class="space-y-6 xl:col-span-2">
        <?= csrf_field() ?>
        <section class="<?= $card ?>">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><?= view('partials/form_field', ['field' => ['name' => 'name', 'label' => lang('Referensi.field.name'), 'required' => true, 'value' => $school['name'] ?? '', 'help' => lang('Referensi.nameHelp'), 'attrs' => ['maxlength' => '150', 'autocomplete' => 'off']]]) ?></div>
                <?= view('partials/form_field', ['field' => ['name' => 'level', 'label' => lang('Referensi.field.level'), 'type' => 'select', 'required' => true, 'options' => $levels, 'value' => $school['level'] ?? 'SD']]) ?>
                <?= view('partials/form_field', ['field' => ['name' => 'ownership', 'label' => lang('Referensi.field.ownership'), 'type' => 'select', 'options' => $owners, 'value' => $school['ownership'] ?? '']]) ?>
                <div class="sm:col-span-2"><?= view('partials/form_field', ['field' => ['name' => 'npsn', 'label' => lang('Referensi.field.npsn'), 'value' => $school['npsn'] ?? '', 'help' => lang('Referensi.npsnHelp'), 'attrs' => ['inputmode' => 'numeric', 'maxlength' => '8', 'pattern' => '[0-9]{8}', 'autocomplete' => 'off']]]) ?></div>
            </div>
        </section>

        <section class="<?= $card ?>">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <?= view('partials/form_field', ['field' => ['name' => 'province', 'label' => lang('Referensi.field.province'), 'value' => $school['province'] ?? ($provinces[0] ?? ''), 'attrs' => ['maxlength' => '60', 'list' => 'province-options']]]) ?>
                <?= view('partials/form_field', ['field' => ['name' => 'regency', 'label' => lang('Referensi.field.regency'), 'value' => $school['regency'] ?? '', 'help' => lang('Referensi.regencyHelp'), 'attrs' => ['maxlength' => '60']]]) ?>
                <?= view('partials/form_field', ['field' => ['name' => 'district', 'label' => lang('Referensi.field.district'), 'value' => $school['district'] ?? '', 'help' => lang('Referensi.districtHelp'), 'attrs' => ['maxlength' => '60']]]) ?>
                <?= view('partials/form_field', ['field' => ['name' => 'village', 'label' => lang('Referensi.field.village'), 'value' => $school['village'] ?? '', 'attrs' => ['maxlength' => '60']]]) ?>
                <div class="sm:col-span-2"><?= view('partials/form_field', ['field' => ['name' => 'address', 'label' => lang('Referensi.field.address'), 'type' => 'textarea', 'rows' => 2, 'value' => $school['address'] ?? '', 'attrs' => ['maxlength' => '255']]]) ?></div>
            </div>
            <datalist id="province-options">
                <?php foreach ($provinces as $province) : ?><option value="<?= esc($province, 'attr') ?>"></option><?php endforeach ?>
            </datalist>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="<?= url_to('referensi.sekolah') ?>" class="<?= $btnSec ?> text-center"><?= lang('App.back') ?></a>
            <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Referensi.save') ?></button>
        </div>
    </form>

    <?php if ($editing) : ?>
        <aside class="<?= $card ?> h-fit space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Referensi.statusTitle') ?></h2>
                <?= view('partials/status_badge', ['badge' => $active ? ['label' => lang('Referensi.active'), 'color' => 'green'] : ['label' => lang('Referensi.inactive'), 'color' => 'gray']]) ?>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= $active ? lang('Referensi.statusActive') : lang('Referensi.statusInactive') ?></p>
            <dl class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <div><dt class="inline"><?= lang('Referensi.colSource') ?>:</dt> <dd class="inline font-medium text-gray-900 dark:text-white"><?= esc(lang('Referensi.source.' . $school['source'])) ?></dd></div>
                <?php if ($usedBy > 0) : ?><div><dd><?= lang('Referensi.usedBy', [$usedBy]) ?></dd></div><?php endif ?>
            </dl>
            <?php if ($active) : ?>
                <form method="post" action="<?= url_to('referensi.sekolah.nonaktifkan', $school['id']) ?>" data-confirm="<?= esc(lang('Referensi.deactivateAsk', [$school['name']]), 'attr') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-300 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100 dark:border-red-700 dark:text-red-400 dark:hover:bg-gray-700"><?= icon('ban', 'h-4 w-4') ?><?= lang('Referensi.deactivate') ?></button>
                </form>
            <?php else : ?>
                <form method="post" action="<?= url_to('referensi.sekolah.aktifkan', $school['id']) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-green-300 px-4 py-2.5 text-sm font-medium text-green-700 hover:bg-green-50 focus:outline-none focus:ring-4 focus:ring-green-100 dark:border-green-700 dark:text-green-400 dark:hover:bg-gray-700"><?= icon('check', 'h-4 w-4') ?><?= lang('Referensi.activate') ?></button>
                </form>
            <?php endif ?>
        </aside>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
