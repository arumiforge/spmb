<?php

namespace Config;

use App\Libraries\AuditLogger;
use App\Libraries\Menu;
use App\Libraries\ModuleRegistry;
use App\Libraries\TwoFactor;
use App\Models\AuditLogModel;
use App\Models\UserTwoFactorModel;
use CodeIgniter\Config\BaseService;
use PragmaRX\Google2FA\Google2FA;
use Throwable;

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

    /**
     * Feature modules in app/Modules and their on/off state.
     */
    public static function modules(bool $getShared = true): ModuleRegistry
    {
        if ($getShared) {
            return static::getSharedInstance('modules');
        }

        $config   = config('Features');
        $disabled = null;

        // Routes are loaded before migrations on a fresh install (and in the test
        // bootstrap), when the settings table does not exist yet: use the defaults.
        try {
            $disabled = function_exists('setting') ? setting('Features.disabled') : null;
        } catch (Throwable) {
            $disabled = null;
        }

        return new ModuleRegistry(APPPATH . 'Modules', $config->core, is_array($disabled) ? $disabled : $config->disabled);
    }

    /**
     * Admin sidebar built from module menus and the user's permissions.
     */
    public static function menu(bool $getShared = true): Menu
    {
        if ($getShared) {
            return static::getSharedInstance('menu');
        }

        return new Menu(service('modules'), [
            ['label' => 'Dasbor', 'url' => 'admin', 'icon' => 'chart', 'permission' => 'dasbor.view', 'order' => 0],
        ]);
    }
}
