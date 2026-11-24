<?php

declare(strict_types=1);

namespace Modules\Situs\Libraries;

/**
 * Links typed by Admin Sekolah (news items, popup button, footer links): an
 * internal path such as "/daftar" or a full http(s):// address. Anything else
 * (javascript:, data:, "//host") is refused.
 */
final class SiteLink
{
    /**
     * Cleaned link ('' for empty input), or null when it is not allowed.
     * "daftar" becomes "/daftar".
     */
    public static function normalise(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (mb_strlen($url) > 255 || preg_match('/\s/u', $url) === 1) {
            return null;
        }

        if (preg_match('#\Ahttps?://#i', $url) === 1) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false && (string) parse_url($url, PHP_URL_HOST) !== '' ? $url : null;
        }

        $path = '/' . ltrim($url, '/');

        if (str_starts_with($url, '//') || str_contains($path, '..') || preg_match('#\A/[A-Za-z0-9\-._~/?\#=&%+]*\z#', $path) !== 1) {
            return null;
        }

        return $path;
    }

    public static function isExternal(string $url): bool
    {
        return preg_match('#\Ahttps?://#i', $url) === 1;
    }

    /**
     * Address for an href: internal paths go through site_url() so the app
     * also works in a sub-folder.
     */
    public static function href(string $url): string
    {
        return self::isExternal($url) ? $url : site_url(ltrim($url, '/'));
    }

    /**
     * Path of an internal link without slashes, query or fragment ("/cek-status?x=1" → "cek-status").
     */
    public static function path(string $url): string
    {
        return trim((string) parse_url('/' . ltrim($url, '/'), PHP_URL_PATH), '/');
    }
}
