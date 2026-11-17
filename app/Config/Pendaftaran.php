<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Registration defaults (module Pendaftaran). Real values live in the settings
 * table: setting('Pendaftaran.<property>'), edited at /admin/pengaturan/pendaftaran.
 * Nothing school-specific is hard-coded; see docs/phase-2-plan.md.
 */
class Pendaftaran extends BaseConfig
{
    /**
     * Academic year registrations are taken for, "YYYY/YYYY".
     */
    public string $academicYear = '2027/2028';

    /**
     * Registration number format (FR-REG-03). Tokens: {YEAR} first year of the
     * academic year (2027), {YY} its last two digits, {0000} the sequence
     * zero-padded to the number of zeros (at least one).
     */
    public string $numberFormat = 'PMB-{YEAR}-{0000}';

    /**
     * When the sequence starts again from 1: 'year' (each academic year, default)
     * or 'never' (one sequence forever). Pending the school's answer.
     */
    public string $numberScope = 'year';

    /**
     * Master switch for the public online form; waves still decide the dates.
     */
    public bool $onlineOpen = true;

    /**
     * Optional applicant fields the school wants required, and fields hidden
     * from the form altogether (keys = applicants columns). Pending the school's
     * answer (interview round 1, "data fields required on the form").
     *
     * @var list<string>
     */
    public array $requiredFields = ['nisn'];

    /**
     * @var list<string>
     */
    public array $hiddenFields = ['kk_number', 'father_occupation', 'mother_occupation'];

    /**
     * Public status check (D-008): attempts per IP per minute, and minutes an
     * applicant stays signed in to their own data after a successful check.
     */
    public int $statusCheckAttempts = 5;

    public int $applicantSessionMinutes = 30;
}
