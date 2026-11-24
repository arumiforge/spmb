<?php
/**
 * Applicant form fields grouped in sections (public form, offline form, edits).
 * Fields hidden by the settings are skipped; required marks follow the settings (D-037).
 * The parents section asks "Ada wali?" first; the guardian fields follow the answer
 * (all fields show without JavaScript, the server clears them for "Tidak ada wali").
 *
 * @var array{
 *     form: Modules\Pendaftaran\Libraries\ApplicantForm,
 *     values?: array<string, mixed>,
 *     fields?: list<string>,
 *     waves?: array<string, string>|null,
 *     card?: string,
 *     wizard?: bool
 * } $applicantForm fields: which fields to render (default: all visible); waves: wave options for staff;
 *                  wizard: each section becomes a step of the public form wizard (resources/js/wizard.js)
 */

use Modules\Pendaftaran\Controllers\Concerns\HandlesApplicantForm;
use Modules\Pendaftaran\Libraries\ApplicantForm;

$form   = $applicantForm['form'];
$values = $applicantForm['values'] ?? [];
$card   = $applicantForm['card'] ?? 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$waves  = $applicantForm['waves'] ?? null;
$wizard = ! empty($applicantForm['wizard']);
// Staff forms (with a wave choice) also show the fields set to "Hanya diisi petugas".
$only   = $applicantForm['fields'] ?? $form->visible($waves !== null);
$errors = session('errors');
$errors = is_array($errors) ? $errors : [];

// Sub-groups of the parents section, each with a small heading.
$groups = [
    'groupFather'   => ['father_name', 'father_occupation', 'father_income'],
    'groupMother'   => ['mother_name', 'mother_occupation', 'mother_income'],
    'groupGuardian' => ApplicantForm::GUARDIAN,
    'groupContact'  => ['parent_phone', 'parent_email'],
];
$types = [
    'gender'          => ['type' => 'select', 'options' => lang('Pendaftaran.genderOptions')],
    'religion'        => ['type' => 'select', 'options' => lang('Pendaftaran.religionOptions')],
    'birth_date'      => ['type' => 'date'],
    'nik'             => ['attrs' => ['inputmode' => 'numeric', 'maxlength' => '20', 'autocomplete' => 'off']],
    'nisn'            => ['attrs' => ['inputmode' => 'numeric', 'maxlength' => '12', 'autocomplete' => 'off']],
    'kk_number'       => ['attrs' => ['inputmode' => 'numeric', 'maxlength' => '20', 'autocomplete' => 'off']],
    'phone'           => ['type' => 'tel', 'placeholder' => '08…', 'attrs' => ['autocomplete' => 'tel']],
    'parent_phone'    => ['type' => 'tel', 'placeholder' => '08…', 'attrs' => ['autocomplete' => 'tel']],
    'parent_email'    => ['type' => 'email', 'attrs' => ['autocomplete' => 'email']],
    'address'         => ['type' => 'textarea', 'rows' => 2, 'attrs' => ['autocomplete' => 'street-address']],
    'full_name'       => ['attrs' => ['autocomplete' => 'name']],
    'graduation_year' => ['type' => 'number', 'attrs' => ['inputmode' => 'numeric', 'min' => '2000', 'max' => '2100']],
];
foreach (array_keys(ApplicantForm::LISTS) as $listField) {
    $options = ApplicantForm::options($listField) ?? [];
    // Keep a value saved earlier even when it was removed from the list since.
    $saved = (string) ($values[$listField] ?? '');

    if ($saved !== '' && ! in_array($saved, $options, true)) {
        $options[] = $saved;
    }

    $types[$listField] = ['type' => 'select', 'options' => ['' => lang('Pendaftaran.chooseOption')] + array_combine($options, $options)];
}
$wide = ['full_name', 'address', 'previous_school_name'];

// "Ada wali?": the posted answer, the saved one, or "Ada wali" when guardian data exists.
$hasGuardian = old('has_guardian', $values['has_guardian'] ?? null);

if ($hasGuardian === null || $hasGuardian === '') {
    $hasGuardian = array_filter(ApplicantForm::GUARDIAN, static fn (string $f): bool => ! empty($values[$f])) === [] ? '0' : '1';
}
$hasGuardian = (string) $hasGuardian === '1' ? '1' : '0';

$field = static function (string $name) use ($form, $values, $types, $errors): string {
    $help = lang('Pendaftaran.help.' . $name);

    if ($name === 'previous_school_name' && HandlesApplicantForm::schoolPickerAvailable()) {
        return view('Modules\Referensi\Views\partials\school_picker', ['picker' => [
            'id'       => ($id = old('previous_school_id', $values['previous_school_id'] ?? null)) === null || $id === '' ? null : (int) $id,
            'name'     => old('previous_school_name', $values['previous_school_name'] ?? null),
            'error'    => $errors['previous_school_name'] ?? $errors['previous_school_id'] ?? null,
            'required' => true,
        ]], ['saveData' => false]);
    }

    return view('partials/form_field', ['field' => ($types[$name] ?? []) + [
        'name'  => $name,
        'label' => lang('Pendaftaran.fields.' . $name),
        'value' => $values[$name] ?? '',
        // The guardian's name is needed once "Ada wali" is chosen (the fields are off otherwise).
        'required' => $form->requires($name) || $name === 'guardian_name',
        'help'     => $help === 'Pendaftaran.help.' . $name ? '' : $help,
    ]]);
};
$grid = static function (array $names) use ($field, $wide): string {
    $html = '';

    foreach ($names as $name) {
        $html .= '<div class="' . (in_array($name, $wide, true) ? 'sm:col-span-2' : '') . '">' . $field($name) . '</div>';
    }

    return $html;
};
$h3   = 'mb-4 text-base font-semibold text-gray-900 dark:text-white';
$step = 0;
?>
<?php foreach (ApplicantForm::sections($only) as $title => $names) : ?>
    <?php
    $step++;
    $sectionErrors = array_filter(array_keys($errors), static fn ($key): bool => ApplicantForm::sectionOf((string) $key) === $title);
    ?>
    <section class="<?= $card ?><?= $wizard ? ' wizard-langkah' : '' ?>"
        <?php if ($wizard) : ?>id="langkah-<?= $step ?>" data-wizard-langkah="<?= $step ?>" data-judul="<?= esc(lang('Pendaftaran.' . $title), 'attr') ?>" aria-labelledby="judul-langkah-<?= $step ?>"<?= $sectionErrors !== [] ? ' data-ada-salah' : '' ?><?php endif ?>>
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white"<?php if ($wizard) : ?> id="judul-langkah-<?= $step ?>" tabindex="-1"<?php endif ?>>
            <?= lang('Pendaftaran.' . $title) ?>
        </h2>
        <?php if ($wizard && $sectionErrors !== []) : ?>
            <p class="mb-4 flex items-start gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-gray-900 dark:text-red-400" role="alert"><?= icon('circle-alert', 'mt-0.5 h-4 w-4 shrink-0') ?><?= lang('Pendaftaran.wizardStepErrors') ?></p>
        <?php endif ?>

        <?php if ($title !== 'sectionParents') : ?>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2"><?= $grid($names) ?></div>
        <?php else : ?>
            <?php if (array_intersect(ApplicantForm::PARENT_NAMES, $names) !== []) : ?>
                <p class="-mt-2 mb-5 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.parentNameRequired') ?></p>
            <?php endif ?>
            <div class="kelompok-isian">
                <?php foreach ($groups as $group => $groupFields) : ?>
                    <?php $groupFields = array_values(array_intersect($groupFields, $names)); ?>
                    <?php if ($groupFields === []) : continue; endif ?>
                    <div class="kelompok-isian-bagian">
                        <h3 class="<?= $h3 ?>"><?= lang('Pendaftaran.' . $group) ?></h3>
                        <?php if ($group !== 'groupGuardian') : ?>
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2"><?= $grid($groupFields) ?></div>
                        <?php else : ?>
                            <div data-wali>
                                <fieldset data-ringkas-pilihan data-ringkas-label="<?= esc(lang('Pendaftaran.fields.has_guardian'), 'attr') ?>">
                                    <legend class="mb-1 text-sm font-medium text-gray-900 dark:text-white"><?= lang('Pendaftaran.guardianQuestion') ?></legend>
                                    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.guardianQuestionHelp') ?></p>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <?php foreach (lang('Pendaftaran.guardianOptions') as $value => $label) : ?>
                                            <label class="pilihan-kartu">
                                                <input type="radio" name="has_guardian" value="<?= esc((string) $value, 'attr') ?>" class="pilihan-kartu-input" data-wali-pilihan
                                                       aria-describedby="wali-pilihan-<?= esc((string) $value, 'attr') ?>-help" <?= (string) $value === $hasGuardian ? 'checked' : '' ?>>
                                                <span class="pilihan-kartu-ikon" aria-hidden="true"><?= icon((string) $value === '1' ? 'user-round-plus' : 'users', 'h-6 w-6') ?></span>
                                                <span class="min-w-0">
                                                    <span class="pilihan-kartu-judul"><?= esc($label) ?></span>
                                                    <span id="wali-pilihan-<?= esc((string) $value, 'attr') ?>-help" class="pilihan-kartu-teks"><?= esc(lang('Pendaftaran.guardianOptionHelp')[$value]) ?></span>
                                                </span>
                                            </label>
                                        <?php endforeach ?>
                                    </div>
                                </fieldset>
                                <div class="mt-5" data-wali-isian>
                                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400" data-wali-catatan><?= lang('Pendaftaran.guardianFieldsNote') ?></p>
                                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2"><?= $grid($groupFields) ?></div>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
<?php endforeach ?>

<?php if ($waves !== null) : ?>
    <section class="<?= $card ?>">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.sectionStaff') ?></h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?= view('partials/form_field', ['field' => ['name' => 'wave_id', 'type' => 'select', 'label' => lang('Pendaftaran.fields.wave_id'), 'options' => $waves, 'value' => $values['wave_id'] ?? '', 'help' => lang('Pendaftaran.waveHelpStaff')]]) ?>
            <div class="sm:col-span-2"><?= view('partials/form_field', ['field' => ['name' => 'notes', 'type' => 'textarea', 'label' => lang('Pendaftaran.fields.notes'), 'value' => $values['notes'] ?? '', 'help' => lang('Pendaftaran.help.notes')]]) ?></div>
        </div>
    </section>
<?php endif ?>
