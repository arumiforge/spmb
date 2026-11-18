<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Exceptions;

use RuntimeException;

/**
 * Online registration is not possible right now. The code says why:
 * 'switched_off' (setting), 'no_wave' (no wave open today) or 'full' (quota reached).
 */
class RegistrationClosed extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('Online registration is closed: ' . $reason);
    }
}
