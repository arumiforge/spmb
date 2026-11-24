<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Visitor statistics defaults (module Analitik, docs/modules/analitik.md). Real
 * values: setting('Analitik.<property>'), edited on Website → Pengunjung.
 */
class Analitik extends BaseConfig
{
    /**
     * Show "Pengunjung hari ini · bulan ini · total" in the public footer.
     */
    public bool $showInFooter = true;

    /**
     * Days the per-page rows (analytics_daily_pages) are kept. Daily totals are kept for good.
     */
    public int $retentionDays = 400;
}
