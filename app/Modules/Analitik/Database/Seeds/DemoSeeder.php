<?php

declare(strict_types=1);

namespace Modules\Analitik\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * Demo visitor statistics (development only): 90 days of plausible daily totals
 * and per-page views so the Pengunjung page, the dashboard card and the footer
 * have something to show. Idempotent: does nothing when any day is recorded.
 */
class DemoSeeder extends Seeder
{
    private const DAYS = 90;

    /**
     * Share of page views per path.
     */
    private const PAGES = [
        ''                    => 0.34,
        'daftar'              => 0.18,
        'persyaratan'         => 0.12,
        'jadwal'              => 0.10,
        'cek-status'          => 0.09,
        'tanya-jawab'         => 0.07,
        'panduan/calon-murid' => 0.05,
        'cek-status/data'     => 0.03,
        'daftar/selesai'      => 0.02,
    ];

    public function run(): void
    {
        if ($this->db->table('analytics_daily')->countAllResults() > 0) {
            return;
        }

        mt_srand(2027);
        $today = Time::today();
        $daily = [];
        $pages = [];

        for ($i = self::DAYS - 1; $i >= 0; $i--) {
            $day      = $today->subDays($i);
            $weekend  = in_array((int) $day->format('N'), [6, 7], true);
            $growth   = 1 + (self::DAYS - $i) / self::DAYS;
            $visitors = (int) round((18 + mt_rand(0, 14)) * $growth * ($weekend ? 0.6 : 1));
            $views    = (int) round($visitors * (2.2 + mt_rand(0, 12) / 10));
            $daily[]  = ['date' => $day->toDateString(), 'visitors' => $visitors, 'page_views' => $views];

            foreach (self::PAGES as $path => $share) {
                $count = (int) round($views * $share);

                if ($count > 0) {
                    $pages[] = ['date' => $day->toDateString(), 'path' => $path, 'views' => $count];
                }
            }
        }

        $this->db->table('analytics_daily')->insertBatch($daily);
        $this->db->table('analytics_daily_pages')->insertBatch($pages);
        cache()->delete('analitik_footer');
    }
}
