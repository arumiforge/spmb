<?php
/**
 * Applicant form fields grouped in sections (public form, offline form, edits).
 * Fields hidden by the settings are skipped; required marks follow the settings (D-037).
 *
 * @var array{
 *     form: Modules\Pendaftaran\Libraries\ApplicantForm,
 *     values?: array<string, mixed>,
 *     fields?: list<string>,
 *     waves?: array<string, string>|null,
 *     card?: string
 * } $applicantForm fields: which fields to render (default: all visible); waves: wave options for staff
 */

use Modules\Pendaftaran\Controllers\Concerns\HandlesApplicantForm;
use Modules\Pendaftaran\Libraries\ApplicantForm;

$form   = $applicantForm['form'];
$values = $applicantForm['values'] ?? [];
$card   = $applicantForm['card'] ?? 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$waves  = $applicantForm['waves'] ?? null;
// Staff forms (with a wave choice) also show the fields set to "Hanya diisi petugas".
$only = $applicantForm['fields'] ?? $form->visible($waves !== null);

$sections = [
    'sectionStudent' => ['full_name', 'gender', 'birth_place', 'birth_date', 'nisn', 'nik', 'kk_number', 'religion', 'phone'],
    'sectionAddress' => ['address', 'village', 'district', 'regency', 'province'],
    'sectionParents' => [
        'father_name', 'father_occupation', 'father_income', 'mother_name', 'mother_occupation', 'mother_income',
        'guardian_name', 'guardian_relation', 'guardian_occupation', 'guardian_income', 'parent_phone', 'parent_email',
    ],
    'sectionSchool'  => ['previous_school_name', 'graduation_year'],
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

$field = static function (string $name) use ($form, $values, $types): string {
    $help = lang('Pendaftaran.help.' . $name);

    return view('partials/form_field', ['field' => ($types[$name] ?? []) + [
        'name'     => $name,
        'label'    => lang('Pendaftaran.fields.' . $name),
        'value'    => $values[$name] ?? '',
        'required' => $form->requires($name),
        'help'     => $help === 'Pendaftaran.help.' . $name ? '' : $help,
    ]]);
};
?>
<?php foreach ($sections as $title => $names) : ?>
    <?php $names = array_values(array_intersect($names, $only)); ?>
    <?php if ($names === []) : continue; endif ?>
    <section class="<?= $card ?>">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.' . $title) ?></h2>
        <?php if ($title === 'sectionParents') : ?>
            <p class="-mt-2 mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.parentNameRequired') ?></p>
        <?php endif ?>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?php foreach ($names as $name) : ?>
                <div class="<?= in_array($name, $wide, true) ? 'sm:col-span-2' : '' ?>">
                    <?php if ($name === 'previous_school_name' && HandlesApplicantForm::schoolPickerAvailable()) : ?>
                        <?php $errors = session('errors'); ?>
                        <?= view('Modules\Referensi\Views\partials\school_picker', ['picker' => [
                            'id'       => ($id = old('previous_school_id', $values['previous_school_id'] ?? null)) === null || $id === '' ? null : (int) $id,
                            'name'     => old('previous_school_name', $values['previous_school_name'] ?? null),
                            'error'    => is_array($errors) ? ($errors['previous_school_name'] ?? $errors['previous_school_id'] ?? null) : null,
                            'required' => true,
                        ]], ['saveData' => false]) ?>
                    <?php else : ?>
                        <?= $field($name) ?>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
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
