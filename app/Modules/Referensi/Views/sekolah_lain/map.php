<?php
/**
 * Map one typed school name to a list entry, or add it to the list.
 *
 * @var array{name: string, count: int, q: string, provinces: list<string>, candidates: list<array{id: int, npsn: string|null, name: string, level: string, label: string}>} $map
 */

use Modules\Referensi\Models\SchoolModel;

$card   = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$input  = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$btn    = 'rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800';
$levels = array_combine(SchoolModel::LEVELS, SchoolModel::LEVELS);
$owners = ['' => lang('Referensi.chooseOwnership'), 'negeri' => lang('Referensi.ownership.negeri'), 'swasta' => lang('Referensi.ownership.swasta')];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="<?= $card ?> max-w-3xl">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.mapTyped') ?></p>
            <p class="mt-1 break-words text-lg font-semibold text-gray-900 dark:text-white"><?= esc($map['name']) ?></p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.othersCount', [$map['count']]) ?></p>
        </section>

        <section class="<?= $card ?> max-w-3xl space-y-4" aria-labelledby="choose-title">
            <h2 id="choose-title" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Referensi.mapChooseTitle') ?></h2>
            <form method="get" action="<?= url_to('referensi.lain.petakan') ?>" role="search" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="nama" value="<?= esc($map['name'], 'attr') ?>">
                <label for="q" class="sr-only"><?= lang('Referensi.mapSearch') ?></label>
                <input type="search" id="q" name="q" value="<?= esc($map['q'], 'attr') ?>" placeholder="<?= lang('Referensi.mapSearch') ?>" class="<?= $input ?>">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('search', 'h-4 w-4') ?><?= lang('App.search') ?></button>
            </form>

            <?php if ($map['candidates'] === []) : ?>
                <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?= lang('Referensi.mapNoCandidate') ?></p>
            <?php else : ?>
                <form method="post" action="<?= url_to('referensi.lain.petakan') ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="nama" value="<?= esc($map['name'], 'attr') ?>">
                    <fieldset>
                        <legend class="sr-only"><?= lang('Referensi.mapChooseTitle') ?></legend>
                        <ul class="space-y-2">
                            <?php foreach ($map['candidates'] as $i => $school) : ?>
                                <li>
                                    <label for="school-<?= $school['id'] ?>" class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:border-gray-600 dark:has-[:checked]:bg-gray-700">
                                        <input id="school-<?= $school['id'] ?>" type="radio" name="school_id" value="<?= $school['id'] ?>" <?= $i === 0 ? 'required' : '' ?> class="mt-0.5 h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-gray-900 dark:text-white"><?= esc($school['label']) ?></span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400"><?= esc($school['level']) ?> · <?= $school['npsn'] !== null ? 'NPSN ' . esc($school['npsn']) : lang('Referensi.noNpsn') ?></span>
                                        </span>
                                    </label>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </fieldset>
                    <div class="flex justify-end"><button type="submit" class="<?= $btn ?> w-full sm:w-auto"><?= lang('Referensi.mapSubmit') ?></button></div>
                </form>
            <?php endif ?>
        </section>
    </div>

    <form method="post" action="<?= url_to('referensi.lain.tambah') ?>" class="<?= $card ?> h-fit space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="nama" value="<?= esc($map['name'], 'attr') ?>">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Referensi.mapAddTitle') ?></h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.mapAddText') ?></p>
        </div>
        <?= view('partials/form_field', ['field' => ['name' => 'name', 'label' => lang('Referensi.field.name'), 'required' => true, 'value' => SchoolModel::normaliseName($map['name']), 'attrs' => ['maxlength' => '150']]]) ?>
        <div class="grid grid-cols-2 items-end gap-4">
            <?= view('partials/form_field', ['field' => ['name' => 'level', 'label' => lang('Referensi.field.level'), 'type' => 'select', 'required' => true, 'options' => $levels, 'value' => str_starts_with(mb_strtoupper(SchoolModel::normaliseName($map['name'])), 'MI') ? 'MI' : 'SD']]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'ownership', 'label' => lang('Referensi.field.ownership'), 'type' => 'select', 'options' => $owners, 'value' => '']]) ?>
        </div>
        <?= view('partials/form_field', ['field' => ['name' => 'district', 'label' => lang('Referensi.field.district'), 'value' => '', 'attrs' => ['maxlength' => '60']]]) ?>
        <?= view('partials/form_field', ['field' => ['name' => 'regency', 'label' => lang('Referensi.field.regency'), 'value' => '', 'help' => lang('Referensi.regencyHelp'), 'attrs' => ['maxlength' => '60']]]) ?>
        <?= view('partials/form_field', ['field' => ['name' => 'province', 'label' => lang('Referensi.field.province'), 'value' => $map['provinces'][0] ?? '', 'attrs' => ['maxlength' => '60']]]) ?>
        <button type="submit" class="<?= $btn ?> w-full"><?= lang('Referensi.mapAddSubmit') ?></button>
    </form>
</div>
<?= $this->endSection() ?>
