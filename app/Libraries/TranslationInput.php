<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Reads the optional English/Arabic fields of an admin form (partials/terjemahan, D-109).
 *
 * Only languages that are switched on are read: the fields of a switched-off language are not on the
 * form, so their saved text stays as it is. Empty fields become null for table columns (the website
 * then shows the Indonesian text) or '' for settings.
 */
final class TranslationInput
{
    /**
     * @param array<string, array{max?: int, multiline?: bool}> $fields base name => limits, e.g. ['name' => ['max' => 60]]
     *
     * @return array{0: array<string, string|null>, 1: array<string, array<string, mixed>>} [data keyed "<name>_<locale>", validation rules]
     */
    public static function read(IncomingRequest $request, array $fields, bool $nullWhenEmpty = true): array
    {
        $data  = [];
        $rules = [];

        foreach (array_diff(Locales::enabled(), [Locales::DEFAULT]) as $locale) {
            foreach ($fields as $name => $options) {
                $key   = $name . '_' . $locale;
                $value = (string) $request->getPost($key);
                $value = ! empty($options['multiline'])
                    ? trim(str_replace("\r\n", "\n", $value))
                    : trim((string) preg_replace('/\s+/u', ' ', $value));

                $data[$key] = $value === '' && $nullWhenEmpty ? null : $value;

                if (! empty($options['max'])) {
                    $rules[$key] = [
                        'label'  => $key,
                        'rules'  => 'permit_empty|max_length[' . $options['max'] . ']',
                        'errors' => ['max_length' => lang('App.translationTooLong', [number_format($options['max'], 0, ',', '.')])],
                    ];
                }
            }
        }

        return [$data, $rules];
    }
}
