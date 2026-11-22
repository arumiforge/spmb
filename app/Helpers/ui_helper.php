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
     * Indonesian date, e.g. "17 November 2026" or "17 November 2026, 08.00 WIB".
     */
    function tanggal_indo(DateTimeInterface|string|null $date, bool $withTime = false): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $date   = $date instanceof DateTimeInterface ? $date : new DateTimeImmutable($date);
        $text   = $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');

        if ($withTime) {
            $text .= ', ' . $date->format('H.i') . ' ' . setting('School.timezoneLabel');
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
