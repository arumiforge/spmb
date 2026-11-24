<?php

declare(strict_types=1);

namespace Tests\Support\Referensi;

use Modules\Referensi\Libraries\BundledRegions;

/**
 * Loads a small part of the national region list (fixtures/wilayah: all provinces,
 * every regency and district of Jawa Tengah, the villages of Kudus, Pati, Jepara,
 * Demak and a few other districts) for tests of the address picker (D-111).
 */
trait RegionFixtures
{
    protected static function regionBundle(): BundledRegions
    {
        return new BundledRegions(__DIR__ . '/fixtures/wilayah');
    }

    /**
     * Imports the fixture list and records its version, so the picker is available.
     */
    protected function loadRegions(): void
    {
        self::regionBundle()->import();
    }

    protected function forgetRegionSettings(): void
    {
        setting()->forget('Referensi.regionsVersion');
        setting()->forget('School.regionCode');
    }
}
