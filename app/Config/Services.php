<?php

namespace Config;

use App\Libraries\ApplicantAccess;
use App\Libraries\AuditLogger;
use App\Libraries\Language;
use App\Libraries\Locales;
use App\Libraries\Menu;
use App\Libraries\ModuleRegistry;
use App\Libraries\TwoFactor;
use App\Models\AuditLogModel;
use App\Models\UserTwoFactorModel;
use CodeIgniter\Config\BaseService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
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
     * The incoming request, with the website language taken from the address (D-109): "/en/jadwal" is
     * routed as "jadwal" with the locale "en". The request URI keeps the prefix in its path (so
     * current_url() and redirect()->back() stay in the language) through the index-page slot of SiteURI.
     *
     * @return IncomingRequest
     */
    public static function incomingrequest(?App $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('request', $config);
        }

        $config ??= config(App::class);
        $uri = static::get('uri');

        $locale = null;

        if ($uri instanceof SiteURI && $config->indexPage === '') {
            [$locale, $rest] = Locales::splitPrefix($uri->getRoutePath());

            if ($locale !== null) {
                $prefixed            = clone $config;
                $prefixed->indexPage = $locale;
                $query               = $uri->getQuery();
                $uri                 = new SiteURI($prefixed, $rest . ($query !== '' ? '?' . $query : ''), $uri->getHost(), $uri->getScheme());
            }
        }

        $request = new IncomingRequest($config, $uri, 'php://input', new UserAgent());

        Locales::apply($request, $locale ?? Locales::DEFAULT);

        return $request;
    }

    /**
     * The request if one exists already (a test mock first), without creating one.
     */
    public static function existingRequest(): ?object
    {
        return static::$mocks['request'] ?? static::$instances['request'] ?? null;
    }

    /**
     * Language lines that follow the request's locale and fall back to Indonesian (App\Libraries\Language).
     *
     * @return Language
     */
    public static function language(?string $locale = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('language', $locale)->setLocale($locale);
        }

        return new Language($locale ?? Locales::current());
    }

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
     * Which applicant the current visitor proved to be (public pages, D-008).
     */
    public static function applicantAccess(bool $getShared = true): ApplicantAccess
    {
        if ($getShared) {
            return static::getSharedInstance('applicantAccess');
        }

        return new ApplicantAccess(service('session'), max(1, (int) setting('Pendaftaran.applicantSessionMinutes')));
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
