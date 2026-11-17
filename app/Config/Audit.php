<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Audit extends BaseConfig
{
    /**
     * Columns whose values are never written to the audit log.
     * The change itself is still recorded, with the value replaced by "[disembunyikan]".
     *
     * @var list<string>
     */
    public array $redact = [
        'password',
        'password_hash',
        'secret',
        'token',
        'recovery_codes',
        'remember_token',
        'access_token_hash',
    ];

    /**
     * Columns ignored when deciding whether a row changed.
     *
     * @var list<string>
     */
    public array $ignore = [
        'updated_at',
        'last_active',
    ];
}
