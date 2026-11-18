<?php

declare(strict_types=1);

namespace Modules\Berkas\Services;

use RuntimeException;

/**
 * An upload or document action that cannot be done. The message is friendly
 * Indonesian and safe to show to the applicant or staff member.
 */
class UploadRejected extends RuntimeException
{
}
