<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Controllers\Concerns;

use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Referensi\Models\SchoolModel;

/**
 * Validation and view data for the applicant form, shared by the public form,
 * the offline form and the correction pages. Use in a BaseController.
 */
trait HandlesApplicantForm
{
    /**
     * Validates the posted $fields. Returns [cleaned data, errors]; errors is
     * empty when everything is fine.
     *
     * @param list<string> $fields
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    protected function validateApplicant(ApplicantForm $form, array $fields): array
    {
        $data  = ApplicantForm::clean((array) $this->request->getPost(), $fields);
        $rules = $form->rules($fields);

        if (in_array('previous_school_name', $fields, true) && self::schoolPickerAvailable()) {
            $rules['previous_school_id'] = ['label' => lang('Pendaftaran.fields.previous_school_id'), 'rules' => 'permit_empty|is_natural_no_zero|is_not_unique[ref_sekolah.id]'];
        } else {
            unset($data['previous_school_id']);
        }

        $validation = service('validation', null, false)->setRules($rules);
        $validation->run($data);
        $errors = $validation->getErrors() + $form->extraErrors($data, $fields);

        if (! empty($data['previous_school_id'])) {
            // A school from the list wins over the free text.
            $data['previous_school_id']   = (int) $data['previous_school_id'];
            $data['previous_school_name'] = null;
        } elseif (array_key_exists('previous_school_id', $data)) {
            $data['previous_school_id'] = null;
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
