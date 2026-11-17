<?php

namespace Config;

use App\Libraries\AuditLogger;
use App\Libraries\TwoFactor;
use App\Models\AuditLogModel;
use App\Models\UserTwoFactorModel;
use CodeIgniter\Config\BaseService;
use PragmaRX\Google2FA\Google2FA;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Audit trail writer.
     */
    public static function audit(bool $getShared = true): AuditLogger
    {
        if ($getShared) {
            return static::getSharedInstance('audit');
        }

        return new AuditLogger(model(AuditLogModel::class, false), config('Audit'));
    }

    /**
     * Optional TOTP two-factor authentication.
     */
    public static function twoFactor(bool $getShared = true): TwoFactor
    {
        if ($getShared) {
            return static::getSharedInstance('twoFactor');
        }

        $issuer = (string) (setting('School.shortName') ?: 'SPMB');

        return new TwoFactor(
            model(UserTwoFactorModel::class, false),
            new Google2FA(),
            service('encrypter'),
            $issuer,
        );
    }
}
