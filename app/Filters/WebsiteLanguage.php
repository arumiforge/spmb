<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\Locales;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Website language (D-109), a required filter so it also runs before routing and on 404 pages.
 *
 * Before: a language prefix on a staff or file address (/en/admin, /en/masuk, /ar/aset/logo) redirects to
 * the address without it (those are Indonesian only / the same for everyone); a language the school
 * switched off redirects to the same page in Indonesian.
 *
 * After: remembers the visitor's language in a cookie (Locales::COOKIE) when they view a page in English or
 * Arabic, or move between pages of this website (e.g. press "ID" in the switcher). Arriving from outside on
 * an Indonesian address keeps the remembered language, so the layout can offer it (no automatic redirect:
 * search engines and shared links always get the language of the address).
 */
class WebsiteLanguage implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        $locale = $request->getLocale();

        if ($locale === Locales::DEFAULT) {
            return null;
        }

        $path = $request->getPath();

        if (Locales::isUnprefixed($path)) {
            return redirect()->to(Locales::url(Locales::DEFAULT, $path, self::query($request)), 301);
        }

        if (! Locales::isEnabled($locale)) {
            return redirect()->to(Locales::url(Locales::DEFAULT, $path, self::query($request)));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! $request instanceof IncomingRequest || $request->getMethod() !== 'GET' || $response->getStatusCode() !== 200
            || ! str_contains($response->getHeaderLine('Content-Type'), 'text/html') || Locales::isUnprefixed($request->getPath())) {
            return null;
        }

        $locale = $request->getLocale();

        if ($locale === Locales::DEFAULT && ! self::fromThisSite($request)) {
            return null;
        }

        if ($request->getCookie(Locales::COOKIE) !== $locale) {
            $response->setCookie(Locales::COOKIE, $locale, YEAR, '', '/', '', null, true, 'Lax');
        }

        return $response;
    }

    /**
     * True when the visitor came from another page of this website (same host in the Referer).
     */
    public static function fromThisSite(IncomingRequest $request): bool
    {
        $referer = (string) $request->getServer('HTTP_REFERER');

        return $referer !== '' && strcasecmp((string) parse_url($referer, PHP_URL_HOST), $request->getUri()->getHost()) === 0;
    }

    private static function query(IncomingRequest $request): string
    {
        return $request->getUri()->getQuery();
    }
}
