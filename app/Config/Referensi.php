<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Previous-school reference data defaults (module Referensi, D-012).
 * Real values: setting('Referensi.<property>'). Connection values for the
 * Dapodik importer come from .env (referensi.dapodikUrl, referensi.dapodikToken).
 */
class Referensi extends BaseConfig
{
    /**
     * Provinces to import and to suggest first in the school search.
     *
     * @var list<string>
     */
    public array $provinces = ['Jawa Tengah'];

    /**
     * School levels applicants come from.
     *
     * @var list<string>
     */
    public array $levels = ['SD', 'MI'];

    /**
     * Base URL of the public Dapodik portal (docs/modules/referensi.md).
     */
    public string $dapodikUrl = 'https://dapo.kemendikdasmen.go.id';

    /**
     * Access key for the Dapodik data service, if the operator has one.
     * Set it in .env as referensi.dapodikToken; never commit it.
     */
    public string $dapodikToken = '';

    /**
     * Pause between two requests to the Dapodik portal, in milliseconds.
     */
    public int $importDelayMs = 500;

    /**
     * School searches allowed per visitor (IP address) per minute.
     */
    public int $searchPerMinute = 30;

    /**
     * Version of the bundled national SD/MI list already loaded into
     * ref_sekolah (written by BundledSchools, D-058). Empty = not loaded yet.
     */
    public string $bundledVersion = '';
}
