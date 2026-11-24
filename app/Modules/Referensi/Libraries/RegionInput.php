<?php

declare(strict_types=1);

namespace Modules\Referensi\Libraries;

use Modules\Referensi\Models\RegionModel;

/**
 * Reads the address of a registration form (D-111). Shared by the public form,
 * the offline form and the applicant's correction page.
 *
 * With JavaScript the region picker posts codes (province_code, regency_code,
 * district_code, village_code) and no text: the codes must exist and belong
 * together (the village lies in the district, and so on); the text columns get
 * the display names of the chosen regions. Without JavaScript only the old text
 * fields are posted: the text is kept, and where it equals a region name
 * exactly (after the usual clean-up) the codes and official names are added.
 */
class RegionInput
{
    public const CODE_FIELDS = ['province' => 'province_code', 'regency' => 'regency_code', 'district' => 'district_code', 'village' => 'village_code'];

    public function __construct(private readonly ?RegionModel $regions = null, private readonly ?RegionMatcher $matcher = null)
    {
    }

    /**
     * Whether the picker can be used: module on and the region list loaded.
     */
    public static function available(): bool
    {
        if (! service('modules')->isEnabled('Referensi')) {
            return false;
        }

        $version = (string) setting('Referensi.regionsVersion');

        return $version !== '' && model(RegionModel::class)->hasData();
    }

    /**
     * @param array<string, mixed>       $posted Raw POST
     * @param array<string, string|null> $data   Cleaned form data (text fields)
     * @param list<string>               $fields Text fields on the form
     *
     * @return array{0: array<string, string|null>, 1: array<string, string>} [data with codes and names, errors by text field]
     */
    public function resolve(array $posted, array $data, array $fields): array
    {
        $onForm = array_values(array_intersect(array_keys(self::CODE_FIELDS), $fields));

        if ($onForm === []) {
            return [$data, []];
        }

        $codes = [];

        foreach (self::CODE_FIELDS as $text => $codeField) {
            $value             = $posted[$codeField] ?? null;
            $codes[$codeField] = is_string($value) ? trim($value) : '';
        }

        $anyCode = array_filter($codes, static fn (string $c): bool => $c !== '') !== [];
        $anyText = array_filter($onForm, static fn (string $f): bool => array_key_exists($f, $data)) !== [];

        if (! $anyCode && ! $anyText) {
            return [$data, []];
        }

        return $anyCode ? $this->fromCodes($codes, $data, $onForm) : $this->fromText($data, $onForm);
    }

    /**
     * @param array<string, string>      $codes
     * @param array<string, string|null> $data
     * @param list<string>               $onForm
     *
     * @return array{0: array<string, string|null>, 1: array<string, string>}
     */
    private function fromCodes(array $codes, array $data, array $onForm): array
    {
        $regions = $this->regions ?? model(RegionModel::class);
        $levels  = array_flip(RegionModel::LEVELS);
        $deepest = null;

        foreach (self::CODE_FIELDS as $text => $codeField) {
            if ($codes[$codeField] !== '') {
                $deepest = $text;
            }
        }

        $field = self::errorField((string) $deepest, $onForm);
        $code  = $codes[self::CODE_FIELDS[$deepest]];

        if (RegionModel::levelOf($code) !== $levels[$deepest] || ($chain = $regions->chain($code)) === null) {
            return [$data, [$field => lang('Referensi.regionUnknown', ['field' => lang('Referensi.regionLevels.' . $deepest)])]];
        }

        foreach (self::CODE_FIELDS as $text => $codeField) {
            $level = $levels[$text];

            if ($level < $levels[$deepest] && $codes[$codeField] !== '' && $codes[$codeField] !== $chain[$level]['code']) {
                return [$data, [self::errorField($text, $onForm) => lang('Referensi.regionMismatch')]];
            }
        }

        foreach (self::CODE_FIELDS as $text => $codeField) {
            $level            = $levels[$text];
            $data[$codeField] = $chain[$level]['code'] ?? null;

            if (in_array($text, $onForm, true)) {
                $data[$text] = isset($chain[$level]) ? RegionModel::displayName($chain[$level]['name'], $level) : ($data[$text] ?? null);
            }
        }

        return [$data, []];
    }

    /**
     * @param array<string, string|null> $data
     * @param list<string>               $onForm
     *
     * @return array{0: array<string, string|null>, 1: array<string, string>}
     */
    private function fromText(array $data, array $onForm): array
    {
        $address = [];

        foreach (array_keys(self::CODE_FIELDS) as $text) {
            $address[$text] = in_array($text, $onForm, true) ? ($data[$text] ?? null) : null;
        }

        $match = ($this->matcher ?? new RegionMatcher())->match($address, false);

        foreach (self::CODE_FIELDS as $text => $codeField) {
            $data[$codeField] = $match['codes'][$text];

            // Matched levels get the official name; a level left empty above a matched one follows from it.
            if ($match['codes'][$text] !== null && in_array($text, $onForm, true)) {
                $data[$text] = $match['names'][$text];
            }
        }

        return [$data, []];
    }

    /**
     * The text field that shows an error for a level: the level itself when on the form, else the deepest one on it.
     *
     * @param list<string> $onForm
     */
    private static function errorField(string $level, array $onForm): string
    {
        return in_array($level, $onForm, true) ? $level : (string) end($onForm);
    }
}
