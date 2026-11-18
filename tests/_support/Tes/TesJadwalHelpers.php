<?php

declare(strict_types=1);

namespace Tests\Support\Tes;

use Config\Services;

/**
 * Helpers for package A (Jadwal) tests: a fresh visitor between requests and
 * clean Tes settings. Use next to TesFixtures.
 */
trait TesJadwalHelpers
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetTesVisitor();
    }

    protected function tearDown(): void
    {
        foreach (['scheduleVisible', 'eligibleStatuses', 'requireCompleteDocuments', 'instructions', 'defaultDurationMinutes'] as $key) {
            setting()->forget('Tes.' . $key);
        }

        $this->resetTesVisitor();
        parent::tearDown();
    }

    protected function resetTesVisitor(): void
    {
        $_SESSION = [];
        Services::resetSingle('auth');
    }
}
