<?php

declare(strict_types=1);
use App\Libraries\BrandingAssets;

/** View helpers shared by all layouts and modules. */
if (! function_exists('asset_url')) {
    /**
     * URL of a built asset in public/build/, with a version query for cache busting.
     */
    function asset_url(string $file): string
    {
        $file = ltrim($file, '/');
        $path = FCPATH . 'build/' . $file;
        $ver  = is_file($path) ? (string) filemtime($path) : '0';

        return base_url('build/' . $file) . '?v=' . $ver;
    }
}

if (! function_exists('illustration')) {
    /**
     * Painted illustration from public/assets/img (D-027), with its dark-mode variant
     * (<name>-dark.webp) when one exists. $path is relative and without extension,
     * e.g. 'state/kosong-data'. Returns '' when the file is missing. Pass $lazy = false for
     * images visible without scrolling (hero, sign-in, error and guide covers).
     */
    function illustration(string $path, string $alt, string $class = '', bool $lazy = true): string
    {
        $path = trim($path, '/');

        if (preg_match('#\A[a-z0-9-]+/[a-z0-9-]+\z#', $path) !== 1) {
            return '';
        }

        $tag = static function (string $file, string $extra) use ($alt, $class, $lazy): string {
            $full = FCPATH . 'assets/img/' . $file . '.webp';

            if (! is_file($full)) {
                return '';
            }

            [$width, $height] = getimagesize($full) ?: [0, 0];

            // HTML escaping is enough inside double-quoted attributes and keeps the markup readable.
            return '<img src="' . esc(base_url('assets/img/' . $file . '.webp') . '?v=' . filemtime($full)) . '"'
                . ' alt="' . esc($alt) . '" width="' . $width . '" height="' . $height . '"'
                . ($lazy ? ' loading="lazy"' : '') . ' decoding="async" class="' . esc(trim($class . ' ' . $extra)) . '">';
        };

        // Optional accent set per school (setting School.illustrationAccent, e.g. "hijau" → <name>-hijau.webp);
        // falls back to the default painting when the variant does not exist.
        $accent = (string) setting('School.illustrationAccent');
        $accent = preg_match('/\A[a-z]+\z/', $accent) === 1 ? $accent : '';
        $pick   = static fn (string $name): string => $accent !== '' && is_file(FCPATH . 'assets/img/' . $name . '-' . $accent . '.webp') ? $name . '-' . $accent : $name;

        $lightName = $pick($path);
        $light     = $tag($lightName, '');

        if ($light === '') {
            return '';
        }

        $dark = $tag($pick($path . '-dark'), 'hidden dark:block');

        return $dark === '' ? $light : $tag($lightName, 'dark:hidden') . $dark;
    }
}

if (! function_exists('valid_hex_color')) {
    /**
     * Normalises a #RGB or #RRGGBB colour to lowercase #rrggbb, or returns null.
     */
    function valid_hex_color(?string $color): ?string
    {
        $color = trim((string) $color);

        if (preg_match('/\A#?([0-9a-f]{3})\z/i', $color, $m) === 1) {
            $color = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
        }

        if (preg_match('/\A#?([0-9a-f]{6})\z/i', $color, $m) === 1) {
            return '#' . strtolower($m[1]);
        }

        return null;
    }
}

if (! function_exists('brand_color')) {
    /**
     * School brand colour from settings, safe to print inside a <style> block.
     */
    function brand_color(): string
    {
        return valid_hex_color((string) setting('School.primaryColor')) ?? '#2563eb';
    }
}

if (! function_exists('school_logo_url')) {
    /**
     * Public URL of the uploaded school logo, or null when none is set.
     */
    function school_logo_url(): ?string
    {
        return BrandingAssets::url('logo');
    }
}

if (! function_exists('school_favicon_url')) {
    function school_favicon_url(): string
    {
        return BrandingAssets::url('favicon') ?? base_url('favicon.ico');
    }
}

if (! function_exists('initials')) {
    /**
     * Up to two initials for an avatar ("Siti Aminah" → "SA").
     */
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $out   = '';

        foreach (array_slice(array_values(array_filter($parts)), 0, 2) as $part) {
            $out .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $out === '' ? '?' : $out;
    }
}

if (! function_exists('tanggal_indo')) {
    /**
     * Date in the page's language (D-109), e.g. "17 November 2026, 08.00 WIB" in Indonesian,
     * "17 November 2026, 08:00 WIB" in English and "17 نوفمبر 2026، 08:00 WIB" in Arabic (Western digits).
     * The staff dashboard and PDFs are always Indonesian.
     */
    function tanggal_indo(DateTimeInterface|string|null $date, bool $withTime = false): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        helper('locale');
        $locale = locale_current();
        $months = locale_month_names($locale);
        $date   = $date instanceof DateTimeInterface ? $date : new DateTimeImmutable($date);
        $text   = $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');

        if ($withTime) {
            $text .= ($locale === 'ar' ? '، ' : ', ') . $date->format($locale === 'id' ? 'H.i' : 'H:i') . ' ' . setting('School.timezoneLabel');
        }

        return $text;
    }
}

if (! function_exists('icon')) {
    /**
     * Inline Lucide icon (ISC, D-070): stroke icon in currentColor, decorative (aria-hidden).
     * Accepts a Lucide name ('circle-check') or a short alias ('check'); unknown names fall back
     * to 'info'. The icons available are listed in resources/icons.json (`node scripts/icons.mjs`).
     * Place it directly next to text, never on a tinted square or circle.
     */
    function icon(string $name, string $class = 'h-5 w-5'): string
    {
        static $set = null;
        $set ??= require __DIR__ . '/lucide_icons.php';

        $name = $set['aliases'][$name] ?? $name;
        $body = $set['icons'][$name] ?? $set['icons']['info'];

        // Icons that point a way are mirrored on right-to-left pages (Arabic, D-109).
        if (preg_match('/\A(arrow|chevron|chevrons)-(left|right)\z|\A(log-in|log-out|undo-2|redo-2|corner-down-left)\z/', $name) === 1) {
            $class .= ' rtl:-scale-x-100';
        }

        return '<svg class="' . esc($class) . '" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"'
            . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
    }
}

if (! function_exists('icon_exists')) {
    /**
     * Whether icon() knows $name (a Lucide name or an alias).
     */
    function icon_exists(string $name): bool
    {
        static $set = null;
        $set ??= require __DIR__ . '/lucide_icons.php';

        return isset($set['icons'][$set['aliases'][$name] ?? $name]);
    }
}

if (! function_exists('lipatan_buka')) {
    /**
     * Opens a folding card (partials/lipatan_buka, D-100). Use this helper instead of
     * view(): it does not leak `title` and friends into the page's view data.
     *
     * @param array{id: string, title: string, intro?: string|null, icon?: string|null, badge?: string|null, open?: bool, class?: string|null, heading?: string} $card
     */
    function lipatan_buka(array $card): string
    {
        // Every option is passed: the renderer still holds data from earlier views (e.g. a `badge` array).
        $card += ['intro' => null, 'icon' => null, 'badge' => null, 'open' => true, 'class' => null, 'heading' => 'h2'];

        return view('partials/lipatan_buka', $card, ['saveData' => false]);
    }
}

if (! function_exists('lipatan_tutup')) {
    /**
     * Closes a folding card opened with lipatan_buka().
     */
    function lipatan_tutup(): string
    {
        return view('partials/lipatan_tutup', [], ['saveData' => false]);
    }
}

if (! function_exists('module_panels')) {
    /**
     * Renders the panel <name> of every enabled module that provides
     * Views/panels/<name>.php, in module order. Each panel receives $data
     * (e.g. ['applicant' => [...], 'context' => 'admin']) and must render
     * nothing when it has nothing to show. See docs/phase-2-plan.md, "Panels".
     *
     * @param array<string, mixed> $data
     */
    function module_panels(string $name, array $data = []): string
    {
        $html = '';

        foreach (service('modules')->panels($name) as $view) {
            $html .= view($view, $data, ['saveData' => false]);
        }

        return $html;
    }
}

if (! function_exists('status_badge_for')) {
    /**
     * Badge data for a shared status code, for partials/status_badge:
     * view('partials/status_badge', ['badge' => status_badge_for('documents', 'complete')]).
     *
     * @param string $type applicant | documents | document | method | test
     *
     * @return array{label: string, color: string}
     */
    function status_badge_for(string $type, string $code): array
    {
        $colors = [
            'applicant' => ['registered' => 'blue', 'verified' => 'green', 'cancelled' => 'red'],
            'documents' => ['incomplete' => 'red', 'pending' => 'yellow', 'to_follow' => 'yellow', 'complete' => 'green'],
            'document'  => ['pending' => 'yellow', 'valid' => 'green', 'invalid' => 'red', 'to_follow' => 'gray'],
            'method'    => ['online' => 'primary', 'offline' => 'gray'],
            'test'      => ['unscheduled' => 'gray', 'pending' => 'blue', 'present' => 'green', 'absent' => 'red', 'rescheduled' => 'yellow'],
        ];
        $key   = 'Status.' . $type . '.' . $code;
        $label = lang($key);

        return [
            'label' => $label === $key ? $code : $label,
            'color' => $colors[$type][$code] ?? 'gray',
        ];
    }
}

if (! function_exists('brand_asset_url')) {
    /**
     * Public URL of a brand image (logoWide, logoWideDark, partnerLogo, icon,
     * shareImage, ...; see App\Libraries\BrandingAssets), or null when not set.
     */
    function brand_asset_url(string $key): ?string
    {
        return BrandingAssets::url($key);
    }
}

if (! function_exists('social_icon')) {
    /**
     * Brand mark of a social network (instagram, facebook, youtube, tiktok, whatsapp) from Simple Icons
     * (CC0), copied into lucide_icons.php by `npm run icons`. Filled with currentColor.
     */
    function social_icon(string $network, string $class = 'h-5 w-5'): string
    {
        static $set = null;
        $set ??= require __DIR__ . '/lucide_icons.php';
        $path = $set['brands'][$network] ?? null;

        if ($path === null) {
            return icon('external-link', $class);
        }

        return '<svg class="' . esc($class) . '" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">'
            . '<path d="' . esc($path, 'attr') . '"/></svg>';
    }
}

if (! function_exists('whatsapp_url')) {
    /**
     * wa.me link for an Indonesian number as typed in settings ("0812 3456 7890" → https://wa.me/6281234567890).
     */
    function whatsapp_url(string $number, string $text = ''): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits . ($text !== '' ? '?text=' . rawurlencode($text) : '');
    }
}

if (! function_exists('school_social_links')) {
    /**
     * Social media links set in Identitas Sekolah, in display order (D-081).
     *
     * @return list<array{network: string, label: string, url: string}>
     */
    function school_social_links(): array
    {
        $links = [];

        foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $network => $label) {
            $url = trim((string) setting('School.social' . ucfirst($network)));

            if ($url !== '' && preg_match('#\Ahttps?://#i', $url) === 1) {
                $links[] = ['network' => $network, 'label' => $label, 'url' => $url];
            }
        }

        $whatsapp = (string) setting('School.whatsapp');

        if ($whatsapp !== '') {
            $links[] = ['network' => 'whatsapp', 'label' => 'WhatsApp', 'url' => whatsapp_url($whatsapp)];
        }

        return $links;
    }
}

if (! function_exists('map_embed_url')) {
    /**
     * The Google Maps embed URL from what Admin Sekolah pasted (the whole <iframe> code or only its src),
     * or '' when it is not a Google Maps embed (D-085). Only https://www.google.com/maps/embed?… is accepted.
     */
    function map_embed_url(string $input): string
    {
        $input = trim(html_entity_decode($input, ENT_QUOTES | ENT_HTML5));

        if (preg_match('/src\s*=\s*(["\'])(.*?)\1/is', $input, $m) === 1) {
            $input = $m[2];
        }

        // Google puts apostrophes from place names ("Ma'ruf") in the URL as they are.
        $input = str_replace(["'", ' '], ['%27', '%20'], trim($input));

        if (preg_match('#\Ahttps://(www\.)?google\.com/maps/embed\?[A-Za-z0-9!%._~:/?=&*+,;@-]+\z#', $input) !== 1 || strlen($input) > 2000) {
            return '';
        }

        return preg_replace('#\Ahttps://google\.com/#', 'https://www.google.com/', $input) ?? '';
    }
}

if (! function_exists('map_search_embed_url')) {
    /**
     * Map preview for a place when the school has not pasted a Google Maps embed (D-091): the keyless
     * "output=embed" search map, so the address always comes with a map, not only a directions link.
     */
    function map_search_embed_url(string $query): string
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? '');

        return $query === '' ? '' : 'https://maps.google.com/maps?q=' . rawurlencode($query) . '&z=16&output=embed';
    }
}

if (! function_exists('public_nav')) {
    /**
     * Public website menu: Beranda and Panduan plus the links of enabled modules (Config/PublicMenu.php),
     * sorted, each with an icon and whether it is the current page.
     *
     * @return list<array{label: string, url: string, icon: string, active: bool}>
     */
    function public_nav(): array
    {
        $nav = [
            ['label' => lang('App.home'), 'url' => '/', 'order' => 0],
            ['label' => lang('App.guide'), 'url' => 'panduan', 'order' => 90],
            ...service('modules')->collect('Config/PublicMenu.php'),
        ];
        usort($nav, static fn (array $a, array $b): int => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));

        $icons = ['/'    => 'house', 'persyaratan' => 'clipboard-list', 'daftar' => 'user-round-plus', 'jadwal' => 'calendar-days',
            'cek-status' => 'search', 'panduan' => 'book-open', 'tanya-jawab' => 'message-circle-question-mark'];
        $current = trim(uri_string(), '/');

        return array_map(static function (array $item) use ($icons, $current): array {
            $url = trim($item['url'], '/');

            return [
                'label'  => $item['label'],
                'url'    => $item['url'],
                'icon'   => $icons[$item['url']] ?? 'chevron-right',
                'active' => $item['url'] === '/' ? $current === '' : ($current === $url || str_starts_with($current . '/', $url . '/')),
            ];
        }, $nav);
    }
}

if (! function_exists('public_bottom_nav')) {
    /**
     * Mobile bottom bar of the public site (D-081): Beranda · Persyaratan · DAFTAR · Jadwal · Cek Status.
     * A slot whose module is off takes the next unused fallback, so the bar keeps its shape.
     *
     * @param list<array{label: string, url: string, icon: string, active: bool}> $nav public_nav()
     *
     * @return list<array{label: string, url: string, icon: string, active: bool, center: bool}>
     */
    function public_bottom_nav(array $nav): array
    {
        $byUrl  = array_column($nav, null, 'url');
        $used   = [];
        $bottom = [];

        foreach ([['/'], ['persyaratan', 'panduan', 'tanya-jawab'], ['daftar'], ['jadwal', 'tanya-jawab', 'panduan'], ['cek-status', 'panduan', 'tanya-jawab']] as $wanted) {
            foreach ($wanted as $url) {
                if (isset($byUrl[$url]) && ! in_array($url, $used, true)) {
                    $used[]   = $url;
                    $bottom[] = $byUrl[$url] + ['center' => $url === 'daftar', 'art' => $url === 'daftar' ? 'ikon/ikon-daftar' : null];
                    if ($url === 'daftar') {
                        $bottom[array_key_last($bottom)]['label'] = lang('App.navRegister');
                    }

                    break;
                }
            }
        }

        return $bottom;
    }
}

if (! function_exists('art_icon')) {
    /**
     * A Codex painted icon (public/assets/img/<path>.webp, D-082) inside a three.js-ready holder
     * ([data-ikon-3d]: it floats and tilts once ikon3d.js runs). While the painting is not delivered
     * yet, the Lucide icon $fallback (or a brand icon such as "whatsapp") is shown instead, without 3D.
     */
    function art_icon(string $path, string $fallback, string $class = 'h-20 w-20', string $alt = ''): string
    {
        $picture = illustration($path, $alt, 'h-full w-full object-contain');

        if ($picture === '') {
            return '<span class="ikon-cadangan inline-flex ' . esc($class) . ' items-center justify-center text-primary-600 dark:text-primary-400" aria-hidden="true">'
                . (icon_exists($fallback) ? icon($fallback, 'h-3/5 w-3/5') : social_icon($fallback, 'h-1/2 w-1/2')) . '</span>';
        }

        return '<span class="ikon-3d ' . esc($class) . '" data-ikon-3d' . ($alt === '' ? ' aria-hidden="true"' : '') . '>' . $picture . '</span>';
    }
}
