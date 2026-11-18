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
     * from the form altogether (keys = applicants columns). KK number,
     * occupations and income are optional by default (D-048).
     *
     * @var list<string>
     */
    public array $requiredFields = ['nisn'];

    /**
     * @var list<string>
     */
    public array $hiddenFields = [];

    /**
     * Fields filled by school staff only: not on the public form, shown on the
     * staff forms (D-048).
     *
     * @var list<string>
     */
    public array $staffOnlyFields = [];

    /**
     * Dropdown list for parent/guardian occupation (Dapodik categories by
     * default; the school may edit the list). Values are stored as written.
     *
     * @var list<string>
     */
    public array $occupationOptions = [
        'Tidak bekerja', 'Nelayan', 'Petani', 'Peternak', 'PNS/TNI/Polri', 'Karyawan Swasta',
        'Pedagang Kecil', 'Pedagang Besar', 'Wiraswasta', 'Wirausaha', 'Buruh', 'Pensiunan',
        'Tenaga Kerja Indonesia', 'Karyawan BUMN', 'Sudah Meninggal', 'Lainnya',
    ];

    /**
     * Dropdown list for parent/guardian monthly income (Dapodik ranges by default).
     *
     * @var list<string>
     */
    public array $incomeOptions = [
        'Tidak berpenghasilan', 'Kurang dari Rp500.000', 'Rp500.000 – Rp999.999', 'Rp1.000.000 – Rp1.999.999',
        'Rp2.000.000 – Rp4.999.999', 'Rp5.000.000 – Rp20.000.000', 'Lebih dari Rp20.000.000',
    ];

    /**
     * Public status check (D-008): attempts per IP per minute, and minutes an
     * applicant stays signed in to their own data after a successful check.
     */
    public int $statusCheckAttempts = 5;

    public int $applicantSessionMinutes = 30;
}
