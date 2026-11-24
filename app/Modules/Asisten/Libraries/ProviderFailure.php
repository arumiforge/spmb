<?php

declare(strict_types=1);

namespace Modules\Asisten\Libraries;

use RuntimeException;

/**
 * One AI connection did not answer usably (quota, server error, timeout, network, refused key,
 * unreadable reply). The message is shown to Super Admin and never contains the API key.
 */
final class ProviderFailure extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null)
    {
        parent::__construct($message);
    }
}
