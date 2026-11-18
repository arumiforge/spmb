<?php

declare(strict_types=1);

namespace Modules\Tes\Services;

use RuntimeException;

/**
 * A schedule change that Scheduler refuses. The message is friendly
 * Indonesian, safe to show to staff as it is (flash message or JSON).
 */
class SchedulerException extends RuntimeException
{
}
