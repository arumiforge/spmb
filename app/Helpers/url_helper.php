<?php

declare(strict_types=1);

use App\Libraries\Locales;
use CodeIgniter\HTTP\SiteURI;
use Config\App;

/*
 * Extends the framework's url_helper (loaded before it, so this site_url() wins; see "Extending
 * Helpers" in the CodeIgniter guide). site_url() adds the language prefix of the current page (D-109):
 * on /en/… pages site_url('jadwal'), url_to(), redirect()->to()/route() and form_open() point to
 * /en/jadwal. Staff and file addresses (Locales::isUnprefixed) never get a prefix; base_url() never
 * adds one.
 */
if (! function_exists('site_url')) {
    /**
     * @param list<string>|string $relativePath
     */
    function site_url($relativePath = '', ?string $scheme = null, ?App $config = null): string
    {
        $currentURI = service('request')->getUri();

        assert($currentURI instanceof SiteURI);

        if (is_array($relativePath)) {
            $relativePath = implode('/', $relativePath);
        }

        return $currentURI->siteUrl(Locales::prefixPath($relativePath), $scheme, $config);
    }
}
