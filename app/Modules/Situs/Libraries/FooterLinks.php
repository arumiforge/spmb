<?php

declare(strict_types=1);

namespace Modules\Situs\Libraries;

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
        $new = array_values(array_map(static fn (array $l): array => ['label' => (string) $l['label'], 'url' => (string) $l['url'], 'active' => (bool) $l['active']]
            + array_filter(['label_en' => trim((string) ($l['label_en'] ?? '')), 'label_ar' => trim((string) ($l['label_ar'] ?? ''))], static fn (string $v): bool => $v !== ''), $links));

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
                $links[] = ['label' => self::label($link), 'href' => SiteLink::href($link['url']), 'external' => SiteLink::isExternal($link['url'])];
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
     * answers it, and it is not a menu page hidden for lack of content. Routes of
     * switched-off modules are never loaded (D-112), so the route list is exact.
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

        return self::matches($path, array_keys(service('routes')->getRoutes('GET')));
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
                $links[] = ['label' => (string) $row['label'], 'url' => (string) $row['url'], 'active' => (bool) ($row['active'] ?? true)]
                    + array_filter(['label_en' => (string) ($row['label_en'] ?? ''), 'label_ar' => (string) ($row['label_ar'] ?? '')], static fn (string $v): bool => $v !== '');
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
        return implode('; ', array_map(static fn (array $l): string => $l['label']
            . (! empty($l['label_en']) ? ' / EN: ' . $l['label_en'] : '') . (! empty($l['label_ar']) ? ' / AR: ' . $l['label_ar'] : '')
            . ' → ' . $l['url'] . ($l['active'] ? '' : ' (disembunyikan)'), $links));
    }

    /**
     * The link text in the page's language (D-109): the school's translation when filled in; for a default
     * link whose Indonesian text was kept, the built-in translation; otherwise the Indonesian text.
     *
     * @param array{label: string, url: string, active: bool, label_en?: string, label_ar?: string} $link
     */
    public static function label(array $link): string
    {
        $locale = locale_current();
        $label  = localized($link, 'label');

        if ($locale === 'id' || $label !== $link['label']) {
            return $label;
        }

        foreach (['/cek-status' => 'Situs.checkStatus', '/tanya-jawab' => 'Situs.qaTitle', '/panduan' => 'App.guide'] as $url => $key) {
            if ($link['url'] === $url && $link['label'] === lang($key, [], 'id')) {
                return lang($key);
            }
        }

        return $label;
    }
}
