<?php
/**
 * Registration settings.
 *
 * @var array{academicYear: string, onlineOpen: string, numberFormat: string, numberScope: string} $values
 * @var array<string, string>                                                                      $modes field => required|optional|hidden
 */

use Modules\Pendaftaran\Services\RegistrationNumber;

$f = static fn (string $name, array $extra = []): string => view('partials/form_field', ['field' => $extra + [
    'name'  => $name,
    'label' => lang('Pendaftaran.' . $name),
    'value' => $values[$name] ?? '',
]]);
$card    = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2      = 'mb-4 text-lg font-semibold text-gray-900 dark:text-white';
$oldYear = (string) old('academicYear', $values['academicYear']);
$example = RegistrationNumber::isValidFormat((string) old('numberFormat', $values['numberFormat']))
    ? RegistrationNumber::format((string) old('numberFormat', $values['numberFormat']), $oldYear, 12)
    : '–';
$posted  = old('fields');
$labels  = lang('Pendaftaran.fieldModes');
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= url_to('pengaturan.pendaftaran') ?>" class="max-w-3xl space-y-6">
    <?= csrf_field() ?>

    <section class="<?= $card ?>">
        <h2 class="<?= $h2 ?>"><?= lang('Pendaftaran.sectionYear') ?></h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?= $f('academicYear', ['required' => true, 'help' => lang('Pendaftaran.academicYearHelp'), 'placeholder' => '2027/2028', 'attrs' => ['inputmode' => 'numeric', 'maxlength' => '9']]) ?>
            <div class="sm:col-span-2">
                <?= $f('onlineOpen', ['type' => 'checkbox']) ?>
                <p class="ms-7 mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.onlineOpenHelp') ?></p>
            </div>
        </div>
    </section>

    <section class="<?= $card ?>">
        <h2 class="<?= $h2 ?>"><?= lang('Pendaftaran.sectionNumber') ?></h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <?= $f('numberFormat', ['required' => true, 'help' => lang('Pendaftaran.numberFormatHelp'), 'attrs' => ['maxlength' => '40', 'autocomplete' => 'off', 'spellcheck' => 'false', 'data-number-format' => '']]) ?>
                <p class="mt-3 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    <?= lang('Pendaftaran.numberExample') ?> <strong class="font-mono text-base" data-number-example><?= esc($example) ?></strong>
                </p>
            </div>
            <?= $f('numberScope', ['type' => 'select', 'required' => true, 'options' => lang('Pendaftaran.numberScopeOptions')]) ?>
            <p class="text-sm text-gray-500 sm:col-span-2 dark:text-gray-400"><?= lang('Pendaftaran.numberNote') ?></p>
        </div>
    </section>

    <section class="<?= $card ?>">
        <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.sectionFields') ?></h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.sectionFieldsIntro') ?></p>
        <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
            <?php foreach ($modes as $field => $mode) : ?>
                <?php $mode = is_array($posted) ? ($posted[$field] ?? $mode) : $mode; ?>
                <fieldset class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <legend class="float-left text-sm font-medium text-gray-900 dark:text-white"><?= lang('Pendaftaran.fields.' . $field) ?></legend>
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        <?php foreach ($labels as $value => $label) : ?>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="radio" name="fields[<?= esc($field, 'attr') ?>]" value="<?= esc($value, 'attr') ?>" <?= $mode === $value ? 'checked' : '' ?>
                                       class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                <?= esc($label) ?>
                            </label>
                        <?php endforeach ?>
                    </div>
                </fieldset>
            <?php endforeach ?>
        </div>
    </section>

    <div class="flex justify-end">
        <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= lang('Pendaftaran.save') ?></button>
    </div>
</form>

<script<?= csp_script_nonce() !== '' ? ' ' . csp_script_nonce() : '' ?>>
/* Live example of the registration number (same rules as RegistrationNumber::format). */
(() => {
    const input = document.querySelector('[data-number-format]');
    const year = document.getElementById('f-academicYear');
    const out = document.querySelector('[data-number-example]');
    if (!input || !out) return;
    const render = () => {
        const format = input.value.trim();
        const y = String(parseInt((year && year.value) || '', 10) || new Date().getFullYear());
        const valid = /^(?:[A-Za-z0-9./_-]|\{YEAR\}|\{YY\}|\{0+\})+$/.test(format) && (format.match(/\{0+\}/g) || []).length === 1;
        out.textContent = valid
            ? format.replace(/\{(YEAR|YY|0+)\}/g, (m, t) => t === 'YEAR' ? y : (t === 'YY' ? y.slice(-2) : '12'.padStart(t.length, '0')))
            : '–';
    };
    input.addEventListener('input', render);
    if (year) year.addEventListener('input', render);
})();
</script>
<?= $this->endSection() ?>
