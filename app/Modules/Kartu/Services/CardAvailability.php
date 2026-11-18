<?php

declare(strict_types=1);

namespace Modules\Kartu\Services;

/**
 * When an applicant may download their own card (FR-CARD-04, D-035).
 * setting('Kartu.availableWhen'): 'immediately' (default) or 'verified'
 * (after the committee set the applicant to "Sudah diperiksa").
 * Cancelled applicants never get a card; staff with kartu.cetak always can print.
 */
class CardAvailability
{
    public const OPTIONS = ['immediately', 'verified'];

    /**
     * @param array<string, mixed> $applicant applicants row
     */
    public static function allows(array $applicant): bool
    {
        $status = (string) ($applicant['status'] ?? '');

        if ($status === 'cancelled' || ! empty($applicant['deleted_at'])) {
            return false;
        }

        return match (self::rule()) {
            'verified' => $status === 'verified',
            default    => true,
        };
    }

    public static function rule(): string
    {
        $rule = (string) setting('Kartu.availableWhen');

        return in_array($rule, self::OPTIONS, true) ? $rule : 'immediately';
    }
}
