<?php

declare(strict_types=1);

namespace Modules\Situs\Libraries;

use CodeIgniter\Router\RouteCollection;
use Config\Modules;
use Config\Routing;
use Modules\Situs\Models\SiteQuestionModel;

/**
 * The "Tautan" column of the public footer (changes D-092: the list is editable).
 *
 * Stored as JSON in setting('Situs.footerLinks'); '' means the default list
 * (Cek Status, Tanya Jawab, Panduan). Internal links only show while their page
 * exists: a link into a switched-off module or to an unknown address is hidden,
 * and so is Tanya Jawab while no question is published.
 */
final class FooterLinks
{
    public const MAX = 12;

    /**
     * @return list<array{label: string, url: string, active: bool}>
     */
    public static function defaults(): array
    {
        return [
            ['label' => lang('Situs.checkStatus'), 'url' => '/cek-status', 'active' => true],
            ['label' => lang('Situs.qaTitle'), 'url' => '/tanya-jawab', 'active' => true],
            ['label' => lang('App.guide'), 'url' => '/panduan', 'active' => true],
        ];
    }

    /**
     * Whether the school changed the list (false = default list).
     */
    public static function isCustom(): bool
    {
        return self::decode() !== null;
    }

    /**
     * The list as edited (or the default list), in order, hidden ones included.
     *
     * @return list<array{label: string, url: string, active: bool}>
     */
    public static function all(): array
    {
        return self::decode() ?? self::defaults();
    }

    /**
     * Saves the list and logs the change. Returns false when nothing changed.
     *
     * @param list<array{label: string, url: string, active: bool}> $links
     */
    public static function save(array $links, string $description): bool
    {
        $old = self::all();
        $new = array_values(array_map(static fn (array $l): array => ['label' => (string) $l['label'], 'url' => (string) $l['url'], 'active' => (bool) $l['active']], $links));

        if ($old === $new && self::isCustom()) {
            return false;
        }

        setting('Situs.footerLinks', json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        service('audit')->log('settings_updated', 'settings', 'FooterLinks', ['links' => self::describe($old)], ['links' => self::describe($new)], 'Tautan bawah website diubah');

        return true;
    }

    /**
     * Back to the default list. Returns false when it already was the default.
     */
    public static function reset(): bool
    {
        if (! self::isCustom()) {
            return false;
        }

        $old = self::all();
        setting('Situs.footerLinks', '');
        service('audit')->log('settings_updated', 'settings', 'FooterLinks', ['links' => self::describe($old)], ['links' => self::describe(self::defaults())], 'Tautan bawah website dikembalikan ke bawaan');

        return true;
    }

    /**
     * Links to print in the footer: active ones whose page exists right now.
     *
     * @param list<array{url: string}> $nav public_nav(), pages in the menu right now
     *
     * @return list<array{label: string, href: string, external: bool}>
     */
    public static function visible(array $nav): array
    {
        $links = [];

        foreach (self::all() as $link) {
            if ($link['active'] && $link['label'] !== '' && self::available($link['url'], $nav)) {
                $links[] = ['label' => $link['label'], 'href' => SiteLink::href($link['url']), 'external' => SiteLink::isExternal($link['url'])];
            }
        }

        return $links;
    }

    /**
     * Whether the page a link points to can be shown right now (external links always).
     *
     * @param list<array{url: string}> $nav public_nav()
     */
    public static function available(string $url, array $nav): bool
    {
        if ($url === '') {
            return false;
        }

        if (SiteLink::isExternal($url)) {
            return true;
        }

        return self::pageExists(SiteLink::path($url), array_map(static fn (array $item): string => trim($item['url'], '/'), $nav));
    }

    /**
     * An internal page exists when it is in the website menu, or when a route
     * answers it that does not belong to a switched-off module, and it is not a
     * menu page hidden for lack of content.
     *
     * @param list<string> $menu
     */
    private static function pageExists(string $path, array $menu): bool
    {
        if (in_array($path, $menu, true)) {
            return true;
        }

        if ($path === 'tanya-jawab' && ! model(SiteQuestionModel::class)->hasPublished()) {
            return false;
        }

        return self::matches($path, array_keys(service('routes')->getRoutes('GET')))
            && ! self::matches($path, self::switchedOffRoutes());
    }

    /**
     * @param list<int|string> $patterns Route keys (placeholders already turned into regex)
     */
    private static function matches(string $path, array $patterns): bool
    {
        foreach ($patterns as $from) {
            $from = (string) $from;

            if ($from === '/' ? $path === '' : preg_match('#\A' . str_replace('#', '\#', trim($from, '/')) . '\z#u', $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * GET routes of switched-off modules. The framework's route discovery also
     * loads their Config/Routes.php, so the main route list alone cannot tell.
     *
     * @return list<int|string>
     */
    private static function switchedOffRoutes(): array
    {
        $off = service('modules')->disabled();

        if ($off === []) {
            return [];
        }

        $config          = clone config(Modules::class);
        $config->enabled = false; // No discovery: only the files included below.
        $routes          = new RouteCollection(service('locator'), $config, config(Routing::class));

        foreach ($off as $module) {
            $file = APPPATH . 'Modules/' . $module . '/Config/Routes.php';

            if (is_file($file)) {
                (static function (RouteCollection $routes, string $file): void {
                    require $file;
                })($routes, $file);
            }
        }

        return array_keys($routes->getRoutes('GET'));
    }

    /**
     * @return list<array{label: string, url: string, active: bool}>|null
     */
    private static function decode(): ?array
    {
        $json = trim((string) setting('Situs.footerLinks'));

        if ($json === '') {
            return null;
        }

        $data = json_decode($json, true);

        if (! is_array($data)) {
            return null;
        }

        $links = [];

        foreach ($data as $row) {
            if (is_array($row) && isset($row['label'], $row['url'])) {
                $links[] = ['label' => (string) $row['label'], 'url' => (string) $row['url'], 'active' => (bool) ($row['active'] ?? true)];
            }
        }

        return $links;
    }

    /**
     * One readable line per link for the audit log ("Cek Status → /cek-status (disembunyikan)").
     *
     * @param list<array{label: string, url: string, active: bool}> $links
     */
    private static function describe(array $links): string
    {
        return implode('; ', array_map(static fn (array $l): string => $l['label'] . ' → ' . $l['url'] . ($l['active'] ? '' : ' (disembunyikan)'), $links));
    }
}
