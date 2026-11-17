<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Previous-school reference data defaults (module Referensi, D-012).
 * Real values: setting('Referensi.<property>').
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
}
