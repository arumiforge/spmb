<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Controllers\Concerns;

use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Referensi\Libraries\RegionInput;
use Modules\Referensi\Models\SchoolModel;

/**
 * Validation and view data for the applicant form, shared by the public form,
 * the offline form and the correction pages. Use in a BaseController.
 */
trait HandlesApplicantForm
{
    /**
     * Validates the posted $fields. Returns [cleaned data, errors]; errors is
     * empty when everything is fine. With "Tidak ada wali" the guardian fields
     * are cleared and not checked. The address codes of the region picker are
     * checked and turned into names (RegionInput, D-111) before the text rules run.
     *
     * @param list<string> $fields
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    protected function validateApplicant(ApplicantForm $form, array $fields): array
    {
        $posted       = (array) $this->request->getPost();
        $data         = ApplicantForm::clean($posted, $fields);
        $fields       = ApplicantForm::fieldsToCheck($fields, $data);
        $regionErrors = [];

        if (self::regionPickerAvailable()) {
            [$data, $regionErrors] = (new RegionInput())->resolve($posted, $data, $fields);
        }

        $rules = $form->rules($fields, $data);

        if (in_array('previous_school_name', $fields, true) && self::schoolPickerAvailable()) {
            $rules['previous_school_id'] = ['label' => lang('Pendaftaran.fields.previous_school_id'), 'rules' => 'permit_empty|is_natural'];
        } else {
            unset($data['previous_school_id']);
        }

        $validation = service('validation', null, false)->setRules($rules);
        $validation->run($data);
        $errors = $regionErrors + $validation->getErrors() + $form->extraErrors($data, $fields);

        if (array_key_exists('previous_school_id', $data)) {
            // The picker posts an id and/or free text; Referensi decides which one counts.
            $data = model(SchoolModel::class)->fromPicker($data['previous_school_id'], $data['previous_school_name'] ?? null) + $data;
        }

        return [$data, $errors];
    }

    /**
     * The Referensi school picker is used when the module is on and its view exists.
     */
    public static function schoolPickerAvailable(): bool
    {
        return service('modules')->isEnabled('Referensi')
            && is_file(APPPATH . 'Modules/Referensi/Views/partials/school_picker.php');
    }

    /**
     * The Referensi region picker is used when the module is on and the region list is loaded (D-111).
     */
    public static function regionPickerAvailable(): bool
    {
        return class_exists(RegionInput::class) && RegionInput::available();
    }

    /**
     * Name of the previous school for display: the list entry or the free text.
     *
     * @param array<string, mixed> $applicant
     */
    public static function schoolName(array $applicant): string
    {
        if (! empty($applicant['previous_school_id']) && class_exists(SchoolModel::class)) {
            $model = model(SchoolModel::class);

            if (method_exists($model, 'label')) {
                $label = $model->label((int) $applicant['previous_school_id']);

                if (is_string($label) && $label !== '') {
                    return $label;
                }
            }

            $row = $model->find((int) $applicant['previous_school_id']);

            if (is_array($row) && ! empty($row['name'])) {
                return (string) $row['name'];
            }
        }

        return (string) ($applicant['previous_school_name'] ?? '') ?: '-';
    }
}
