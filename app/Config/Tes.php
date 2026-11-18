<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Placement test defaults (module Tes, docs/phase-3-plan.md). Real values:
 * setting('Tes.<property>'), edited in Pengaturan → Tes (tes.atur). The
 * school sets days, sessions, rooms and quotas itself (interview round 1).
 */
class Tes extends BaseConfig
{
    /**
     * Applicants see their test schedule on their page and on the card.
     * Off while the committee is still arranging sessions.
     */
    public bool $scheduleVisible = true;

    /**
     * Applicant statuses that get a test seat in the automatic allocation.
     *
     * @var list<string>
     */
    public array $eligibleStatuses = ['registered', 'verified'];

    /**
     * Only applicants whose documents are complete get a seat automatically.
     */
    public bool $requireCompleteDocuments = false;

    /**
     * Shown to applicants with their schedule (what to bring, when to come).
     */
    public string $instructions = 'Datang 30 menit sebelum tes dimulai. Bawa kartu pendaftaran (dicetak atau di HP) dan alat tulis.';

    /**
     * Default length of a session in the session generator, in minutes.
     */
    public int $defaultDurationMinutes = 120;
}
