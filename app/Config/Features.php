<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Feature modules (app/Modules/<Name>) that can be switched off.
 *
 * Defaults only; the real list is stored in the settings table and read with
 * setting('Features.disabled'). Core modules can never be disabled.
 */
class Features extends BaseConfig
{
    /**
     * Modules that are always on (the platform does not work without them).
     *
     * @var list<string>
     */
    public array $core = ['Pengaturan', 'Pengguna', 'Profil', 'Panduan'];

    /**
     * Names of disabled modules, e.g. ['Chat'].
     *
     * @var list<string>
     */
    public array $disabled = [];
}
