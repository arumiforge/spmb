<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

/**
 * Staff accounts (Shield users) with profile fields and audit logging.
 */
class UserModel extends ShieldUserModel
{
    use Auditable;

    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [
            ...$this->allowedFields,
            'full_name',
            'phone',
        ];

        $this->registerAuditCallbacks();
    }
}
