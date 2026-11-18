<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Libraries;

use DateTimeImmutable;

/**
 * Which applicant fields the form asks, which are required, how input is
 * cleaned and validated (D-037). Shared by the public form, the offline form
 * and the applicant's own correction page.
 */
class ApplicantForm
{
    /**
     * Always asked and always required (plus one parent/guardian name).
     *
     * @var list<string>
     */
    public const CORE = ['full_name', 'gender', 'birth_place', 'birth_date', 'address', 'parent_phone', 'previous_school_name'];

    /**
     * Fields whose mode (required / optional / hidden) comes from settings.
     *
     * @var list<string>
     */
    public const CONFIGURABLE = [
        'nisn', 'nik', 'kk_number', 'religion', 'village', 'district', 'regency', 'province', 'phone',
        'father_name', 'father_occupation', 'mother_name', 'mother_occupation', 'guardian_name', 'guardian_relation',
        'parent_email', 'graduation_year',
    ];

    /**
     * Fields an applicant may correct on their own page; identity fields are changed by staff only.
     *
     * @var list<string>
     */
    public const SELF_EDITABLE = ['address', 'village', 'district', 'regency', 'province', 'phone', 'parent_phone', 'parent_email'];

    /**
     * Fields staff may set besides the form fields.
     *
     * @var list<string>
     */
    public const STAFF_ONLY = ['wave_id', 'notes'];

    public const PARENT_NAMES = ['father_name', 'mother_name', 'guardian_name'];

    /**
     * @var list<string>
     */
    private array $required;

    /**
     * @var list<string>
     */
    private array $hidden;

    /**
     * @param list<string>|null $required Defaults to setting('Pendaftaran.requiredFields')
     * @param list<string>|null $hidden   Defaults to setting('Pendaftaran.hiddenFields')
     */
    public function __construct(?array $required = null, ?array $hidden = null)
    {
        $this->required = array_values(array_intersect($required ?? (array) setting('Pendaftaran.requiredFields'), self::CONFIGURABLE));
        $this->hidden   = array_values(array_diff(array_intersect($hidden ?? (array) setting('Pendaftaran.hiddenFields'), self::CONFIGURABLE), $this->required));
    }

    /**
     * required | optional | hidden
     */
    public function mode(string $field): string
    {
        if (in_array($field, self::CORE, true)) {
            return 'required';
        }

        if (in_array($field, $this->hidden, true)) {
            return 'hidden';
        }

        return in_array($field, $this->required, true) ? 'required' : 'optional';
    }

    public function shows(string $field): bool
    {
        return $this->mode($field) !== 'hidden';
    }

    public function requires(string $field): bool
    {
        return $this->mode($field) === 'required';
    }

    /**
     * Form fields shown with the current settings.
     *
     * @return list<string>
     */
    public function visible(): array
    {
        return array_values(array_filter([...self::CORE, ...self::CONFIGURABLE], $this->shows(...)));
    }

    /**
     * Trims and normalises posted values; keeps only $fields (+ previous_school_id).
     *
     * @param array<string, mixed> $input
     * @param list<string>         $fields
     *
     * @return array<string, string|null>
     */
    public static function clean(array $input, array $fields): array
    {
        $out = [];

        foreach ([...$fields, 'previous_school_id'] as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = is_scalar($input[$field]) ? trim((string) $input[$field]) : '';
            $value = preg_replace('/\s+/u', ' ', $value) ?? '';

            if (in_array($field, ['nik', 'nisn', 'kk_number'], true)) {
                $value = preg_replace('/[\s.-]/', '', $value) ?? '';
            } elseif (in_array($field, ['phone', 'parent_phone'], true)) {
                $value = self::normalisePhone($value);
            } elseif ($field === 'parent_email') {
                $value = strtolower($value);
            } elseif ($field === 'gender') {
                $value = strtoupper($value);
            }

            $out[$field] = $value === '' ? null : $value;
        }

        return $out;
    }

    /**
     * "0812-3456 7890" → "081234567890"; "+62 812…" → "+62812…".
     */
    public static function normalisePhone(string $phone): string
    {
        return preg_replace('/[\s().-]/', '', $phone) ?? '';
    }

    /**
     * Validation rules for $fields (CodeIgniter format, with labels and friendly messages).
     *
     * @param list<string> $fields
     *
     * @return array<string, array{label: string, rules: string, errors?: array<string, string>}>
     */
    public function rules(array $fields): array
    {
        $base = [
            'full_name'            => "max_length[100]|regex_match[/\\A[\\p{L} .,'-]+\\z/u]",
            'gender'               => 'in_list[L,P]',
            'birth_place'          => 'max_length[60]',
            'birth_date'           => 'valid_date[Y-m-d]',
            'nik'                  => 'exact_length[16]|is_natural',
            'nisn'                 => 'exact_length[10]|is_natural',
            'kk_number'            => 'exact_length[16]|is_natural',
            'religion'             => 'max_length[20]',
            'address'              => 'max_length[255]',
            'village'              => 'max_length[60]',
            'district'             => 'max_length[60]',
            'regency'              => 'max_length[60]',
            'province'             => 'max_length[60]',
            'phone'                => 'regex_match[/\A(\+62|62|0)8[0-9]{7,12}\z/]',
            'father_name'          => 'max_length[100]',
            'father_occupation'    => 'max_length[60]',
            'mother_name'          => 'max_length[100]',
            'mother_occupation'    => 'max_length[60]',
            'guardian_name'        => 'max_length[100]',
            'guardian_relation'    => 'max_length[30]',
            'parent_phone'         => 'regex_match[/\A(\+62|62|0)8[0-9]{7,12}\z/]',
            'parent_email'         => 'valid_email|max_length[100]',
            'previous_school_name' => 'max_length[150]',
            'graduation_year'      => 'is_natural|exact_length[4]',
            'wave_id'              => 'is_natural_no_zero',
            'notes'                => 'max_length[2000]',
        ];

        $rules = [];

        foreach ($fields as $field) {
            if (! isset($base[$field])) {
                continue;
            }

            // The previous school may come as an id from the school picker; extraErrors() checks it.
            $required = $this->requires($field) && $field !== 'previous_school_name' ? 'required' : 'permit_empty';

            $rules[$field] = [
                'label' => lang('Pendaftaran.fields.' . $field),
                'rules' => $required . '|' . $base[$field],
            ];

            if (in_array($field, ['nik', 'nisn', 'kk_number'], true)) {
                $digits                  = $field === 'nisn' ? '10' : '16';
                $message                 = lang('Pendaftaran.digitsExact', ['field' => lang('Pendaftaran.fields.' . $field), 'param' => $digits]);
                $rules[$field]['errors'] = ['exact_length' => $message, 'is_natural' => $message];
            } elseif (in_array($field, ['phone', 'parent_phone'], true)) {
                $rules[$field]['errors'] = ['regex_match' => lang('Pendaftaran.phoneInvalid', ['field' => lang('Pendaftaran.fields.' . $field)])];
            } elseif ($field === 'birth_date') {
                $rules[$field]['errors'] = ['valid_date' => lang('Pendaftaran.birthDateInvalid')];
            } elseif ($field === 'graduation_year') {
                $message                 = lang('Pendaftaran.graduationYearInvalid');
                $rules[$field]['errors'] = ['is_natural' => $message, 'exact_length' => $message];
            } elseif ($field === 'full_name') {
                $rules[$field]['errors'] = ['regex_match' => '{field} hanya boleh berisi huruf, spasi, titik, koma, tanda petik, atau tanda minus.'];
            }
        }

        return $rules;
    }

    /**
     * Checks that rules() cannot express. Returns field => message.
     *
     * @param array<string, string|null> $data   Cleaned input
     * @param list<string>               $fields Fields being validated
     *
     * @return array<string, string>
     */
    public function extraErrors(array $data, array $fields): array
    {
        $errors = [];

        if (in_array('birth_date', $fields, true) && ! empty($data['birth_date'])) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['birth_date']);
            $now  = new DateTimeImmutable('today');

            if ($date === false || $date > $now->modify('-5 years') || $date < $now->modify('-25 years')) {
                $errors['birth_date'] = lang('Pendaftaran.birthDateInvalid');
            }
        }

        if (in_array('graduation_year', $fields, true) && ! empty($data['graduation_year'])) {
            $year = (int) $data['graduation_year'];
            $now  = (int) date('Y');

            if ($year < $now - 10 || $year > $now + 2) {
                $errors['graduation_year'] = lang('Pendaftaran.graduationYearInvalid');
            }
        }

        $parentFields = array_values(array_filter(self::PARENT_NAMES, fn (string $f): bool => $this->shows($f) && in_array($f, $fields, true)));

        if ($parentFields !== []) {
            $filled = array_filter($parentFields, static fn (string $f): bool => ! empty($data[$f]));

            if ($filled === []) {
                $errors[$parentFields[0]] ??= lang('Pendaftaran.parentNameRequired');
            }
        }

        if (in_array('previous_school_name', $fields, true) && empty($data['previous_school_name']) && empty($data['previous_school_id'])) {
            $errors['previous_school_name'] = lang('Validation.required', ['field' => lang('Pendaftaran.fields.previous_school_name')]);
        }

        return $errors;
    }
}
