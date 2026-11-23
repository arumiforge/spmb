<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Validation rules for school settings.
 */
class SchoolRules
{
    /**
     * Accepts the Google Maps embed code (<iframe …>) or its src URL (D-085).
     */
    public function map_embed(?string $value): bool
    {
        helper('ui');

        return trim((string) $value) === '' || map_embed_url((string) $value) !== '';
    }
}
