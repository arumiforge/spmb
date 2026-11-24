<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use Config\Services;
use Locale;
use Throwable;

/**
 * Languages of the public website (D-109): Indonesian (default, no prefix), English (/en/…)
 * and Arabic (/ar/…, right to left). Slugs stay Indonesian in every language.
 *
 * The prefix is taken off the request before routing (Config\Services::incomingrequest), so
 * routes are registered once. site_url()/url_to() add the prefix of the current language back
 * (app/Helpers/url_helper.php) except for staff and file addresses, which never carry one.
 * See docs/i18n.md.
 */
final class Locales
{
    public const DEFAULT = 'id';

    /**
     * Every language the code knows, in switcher order.
     */
    public const ALL = ['id', 'en', 'ar'];

    public const RTL = ['ar'];

    /**
     * Cookie that remembers the language the visitor last chose.
     */
    public const COOKIE = 'spmb_bahasa';

    /**
     * Staff addresses (dashboard, sign-in): always Indonesian; under a prefix they redirect.
     */
    public const STAFF_PATHS = ['admin', 'masuk', 'keluar', 'verifikasi', 'login', 'logout', 'register', 'auth'];

    /**
     * Files and data served by controllers or Nginx: one address for every language.
     */
    public const FILE_PATHS = ['aset', 'build', 'assets', 'berkas/contoh', 'popup/gambar', 'popup/lampiran', 'favicon.ico', 'robots.txt'];

    /**
     * Short labels in the switcher, each in its own language.
     */
    public const LABELS = ['id' => 'ID', 'en' => 'EN', 'ar' => 'العربية'];

    /**
     * Full names, each in its own language (aria-label, hreflang title).
     */
    public const NAMES = ['id' => 'Bahasa Indonesia', 'en' => 'English', 'ar' => 'العربية'];

    /**
     * Open Graph locale names.
     */
    public const OG = ['id' => 'id_ID', 'en' => 'en_US', 'ar' => 'ar_AR'];

    /**
     * Splits a route path into [language prefix or null, rest]: "en/persyaratan" → ['en', 'persyaratan'],
     * "en" → ['en', ''], "persyaratan" → [null, 'persyaratan']. The default language never has a prefix.
     *
     * @return array{0: string|null, 1: string}
     */
    public static function splitPrefix(string $routePath): array
    {
        $path = ltrim($routePath, '/');

        foreach (self::ALL as $locale) {
            if ($locale === self::DEFAULT) {
                continue;
            }

            if ($path === $locale || str_starts_with($path, $locale . '/')) {
                return [$locale, ltrim(substr($path, strlen($locale)), '/')];
            }
        }

        return [null, $routePath];
    }

    /**
     * Language of the current request ('id' outside a web request).
     */
    public static function current(): string
    {
        $request = Services::existingRequest();
        $locale  = $request instanceof IncomingRequest ? $request->getLocale() : self::DEFAULT;

        return in_array($locale, self::ALL, true) ? $locale : self::DEFAULT;
    }

    /**
     * Runs $callback with the request (and so lang(), tanggal_indo()) in another language, e.g. Indonesian
     * for PDFs downloaded from an English page. The language is restored afterwards.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public static function withLocale(string $locale, callable $callback): mixed
    {
        $request = Services::existingRequest();

        if (! $request instanceof IncomingRequest || $request->getLocale() === $locale) {
            return $callback();
        }

        $previous = $request->getLocale();
        self::apply($request, $locale);

        try {
            return $callback();
        } finally {
            self::apply($request, $previous);
        }
    }

    /**
     * Sets the request's language and the PHP intl default with Western digits: in plain "ar", ICU writes
     * Arabic-Indic digits, also in Time::toDateString(), which would break date comparisons (D-109).
     */
    public static function apply(IncomingRequest $request, string $locale): void
    {
        $request->setLocale($locale);
        Locale::setDefault($request->getLocale() === 'ar' ? 'ar@numbers=latn' : $request->getLocale());
    }

    public static function isDefault(?string $locale = null): bool
    {
        return ($locale ?? self::current()) === self::DEFAULT;
    }

    public static function isRtl(?string $locale = null): bool
    {
        return in_array($locale ?? self::current(), self::RTL, true);
    }

    public static function direction(?string $locale = null): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    /**
     * Languages switched on by the school (School.languageEn / School.languageAr, default on), Indonesian first.
     *
     * @return list<string>
     */
    public static function enabled(): array
    {
        $enabled = [self::DEFAULT];

        foreach (['en' => 'School.languageEn', 'ar' => 'School.languageAr'] as $locale => $key) {
            try {
                $on = setting($key);
            } catch (Throwable) {
                $on = true;
            }

            if ($on === null || (bool) $on) {
                $enabled[] = $locale;
            }
        }

        return $enabled;
    }

    public static function isEnabled(string $locale): bool
    {
        return in_array($locale, self::enabled(), true);
    }

    /**
     * True for staff addresses (admin, masuk, …).
     */
    public static function isStaffPath(string $path): bool
    {
        return self::startsWithAny($path, self::STAFF_PATHS);
    }

    /**
     * True for addresses that never carry a language prefix (staff and files).
     */
    public static function isUnprefixed(string $path): bool
    {
        return self::isStaffPath($path) || self::startsWithAny($path, self::FILE_PATHS);
    }

    /**
     * The route path with the language prefix in front ("persyaratan" → "en/persyaratan"), or as it is for
     * Indonesian, staff and file addresses. Query and fragment are kept.
     */
    public static function prefixPath(string $path, ?string $locale = null): string
    {
        $locale ??= self::current();

        if ($locale === self::DEFAULT || preg_match('#\A[a-z][a-z0-9+.-]*:#i', $path) === 1 || str_starts_with($path, '//')) {
            return $path;
        }

        $clean = ltrim($path, '/');
        $route = (string) strtok($clean, '?#');

        if (self::splitPrefix($route)[0] !== null || self::isUnprefixed($route)) {
            return $path;
        }

        return $locale . '/' . $clean;
    }

    /**
     * Full address of a route path in a language, e.g. url('en', 'jadwal') → https://…/en/jadwal.
     */
    public static function url(string $locale, string $path = '', string $query = ''): string
    {
        $path = trim($path, '/');
        $full = match (true) {
            $locale === self::DEFAULT => $path === '' ? '/' : $path,
            $path === ''              => $locale . '/',
            default                   => $locale . '/' . $path,
        };

        return base_url($full) . ($query !== '' ? '?' . $query : '');
    }

    /**
     * The current page in another language (same path and query, without the preview parameter).
     */
    public static function currentUrlIn(string $locale): string
    {
        $path  = '';
        $query = '';

        try {
            $request = service('request');

            if ($request instanceof IncomingRequest) {
                $path  = $request->getPath();
                $get   = $request->getGet();
                $query = is_array($get) && $get !== [] ? http_build_query(array_diff_key($get, ['popup' => true])) : '';
            }
        } catch (Throwable) {
            $path = '';
        }

        return self::url($locale, $path, $query);
    }

    /**
     * Locale-aware value of a translatable column: "<field>_<locale>" when filled, else the Indonesian "<field>".
     *
     * @param array<string, mixed>|object $row
     */
    public static function field(array|object $row, string $field, ?string $locale = null): string
    {
        $row = is_object($row) ? (method_exists($row, 'toArray') ? $row->toArray() : get_object_vars($row)) : $row;
        $locale ??= self::current();

        if ($locale !== self::DEFAULT) {
            $value = trim((string) ($row[$field . '_' . $locale] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return (string) ($row[$field] ?? '');
    }

    /**
     * Locale-aware setting: "Group.key_<locale>" (e.g. School.motto_en) when filled, else "Group.key".
     */
    public static function setting(string $key, ?string $locale = null): string
    {
        $locale ??= self::current();

        if ($locale !== self::DEFAULT) {
            $value = trim((string) setting($key . '_' . $locale));

            if ($value !== '') {
                return $value;
            }
        }

        return (string) setting($key);
    }

    /**
     * @param list<string> $prefixes
     */
    private static function startsWithAny(string $path, array $prefixes): bool
    {
        $path = trim((string) strtok(ltrim($path, '/'), '?#'), '/');

        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
