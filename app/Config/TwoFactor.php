<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Two-step verification policy.
 *
 * Defaults only; Super Admin changes them at /admin/pengaturan/keamanan
 * (stored with setting('TwoFactor.requiredRoles')).
 */
class TwoFactor extends BaseConfig
{
    /**
     * Roles (Shield group keys) whose members must turn on two-step verification.
     *
     * @var list<string>
     */
    public array $requiredRoles = [];

    /**
     * Wrong codes allowed on the verification screen before the sign-in is cancelled.
     */
    public int $maxAttempts = 5;
}
