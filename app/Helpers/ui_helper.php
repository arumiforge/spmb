<?php

declare(strict_types=1);

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
        $path = (string) setting('School.logoPath');

        return $path === '' ? null : url_to('aset.logo') . '?v=' . substr(md5($path), 0, 8);
    }
}

if (! function_exists('school_favicon_url')) {
    function school_favicon_url(): string
    {
        $path = (string) setting('School.faviconPath');

        return $path === '' ? base_url('favicon.ico') : url_to('aset.favicon') . '?v=' . substr(md5($path), 0, 8);
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
     * Inline SVG icon (Flowbite Icons, MIT). Decorative by default (aria-hidden).
     */
    function icon(string $name, string $class = 'h-5 w-5'): string
    {
        static $paths = [
            'home'         => '<path fill-rule="evenodd" d="M11.293 3.293a1 1 0 0 1 1.414 0l6 6 2 2a1 1 0 0 1-1.414 1.414L19 12.414V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2v-6.586l-.293.293a1 1 0 0 1-1.414-1.414l2-2 6-6Z" clip-rule="evenodd"/>',
            'chart'        => '<path d="M13.5 2c-.178 0-.356.013-.492.022l-.074.005a1 1 0 0 0-.934.998V11a1 1 0 0 0 1 1h7.975a1 1 0 0 0 .998-.934l.005-.074A7.04 7.04 0 0 0 22 10.5 8.5 8.5 0 0 0 13.5 2Z"/><path d="M11 6.025a1 1 0 0 0-1.065-.998 8.5 8.5 0 1 0 9.038 9.039A1 1 0 0 0 17.975 13H11V6.025Z"/>',
            'cog'          => '<path fill-rule="evenodd" d="M9.586 2.586A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2v.089l.473.196.063-.063a2.002 2.002 0 0 1 2.828 0l1.414 1.414a2 2 0 0 1 0 2.827l-.063.064.196.473H20a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-.089l-.196.473.063.063a2.002 2.002 0 0 1 0 2.828l-1.414 1.414a2 2 0 0 1-2.828 0l-.063-.063-.473.196V20a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-.089l-.473-.196-.063.063a2.002 2.002 0 0 1-2.828 0l-1.414-1.414a2 2 0 0 1 0-2.827l.063-.064L4.089 15H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2h.09l.195-.473-.063-.063a2 2 0 0 1 0-2.828l1.414-1.414a2 2 0 0 1 2.827 0l.064.063L9 4.089V4a2 2 0 0 1 .586-1.414ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd"/>',
            'users'        => '<path fill-rule="evenodd" d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z" clip-rule="evenodd"/>',
            'user'         => '<path fill-rule="evenodd" d="M12 20a7.966 7.966 0 0 1-5.002-1.756l.002.001v-.683c0-1.794 1.492-3.25 3.333-3.25h3.334c1.84 0 3.333 1.456 3.333 3.25v.683A7.966 7.966 0 0 1 12 20ZM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10c0 5.5-4.44 9.963-9.932 10h-.138C6.438 21.962 2 17.5 2 12Zm10-5c-1.84 0-3.333 1.455-3.333 3.25S10.159 13.5 12 13.5c1.84 0 3.333-1.455 3.333-3.25S13.841 7 12 7Z" clip-rule="evenodd"/>',
            'book'         => '<path fill-rule="evenodd" d="M11 4.717c-2.286-.58-4.16-.756-7.045-.71A1.99 1.99 0 0 0 2 6v11c0 1.133.934 2.022 2.044 2.007 2.759-.038 4.5.16 6.956.791V4.717Zm2 15.081c2.456-.631 4.198-.829 6.956-.791A2.013 2.013 0 0 0 22 16.999V6a1.99 1.99 0 0 0-1.955-1.993c-2.885-.046-4.76.13-7.045.71v15.081Z" clip-rule="evenodd"/>',
            'lock'         => '<path fill-rule="evenodd" d="M8 10V7a4 4 0 1 1 8 0v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1Zm2-3a2 2 0 1 1 4 0v3h-4V7Zm2 6a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0v-3a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>',
            'shield'       => '<path fill-rule="evenodd" d="M11.644 3.066a1 1 0 0 1 .712 0l7 2.666A1 1 0 0 1 20 6.68a17.694 17.694 0 0 1-2.023 7.98 17.406 17.406 0 0 1-5.402 6.158 1 1 0 0 1-1.15 0 17.405 17.405 0 0 1-5.403-6.157A17.692 17.692 0 0 1 4 6.68a1 1 0 0 1 .644-.949l7-2.666Zm4.014 7.187a1 1 0 0 0-1.316-1.506l-3.296 2.884-.839-.838a1 1 0 0 0-1.414 1.414l1.5 1.5a1 1 0 0 0 1.366.046l4-3.5Z" clip-rule="evenodd"/>',
            'school'       => '<path d="M12.4 3.2a1 1 0 0 0-.8 0l-9 4a1 1 0 0 0 0 1.8l9 4a1 1 0 0 0 .8 0L20 9.62V15a1 1 0 1 0 2 0V8.1a1 1 0 0 0-.6-.9l-9-4Z"/><path d="M6 12.2v3.3c0 1.16.84 2.05 1.86 2.6C8.9 18.66 10.35 19 12 19s3.1-.34 4.14-.9C17.16 17.55 18 16.66 18 15.5v-3.3l-5.19 2.3a2 2 0 0 1-1.62 0L6 12.2Z"/>',
            'logout'       => '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2"/>',
            'menu'         => '<path stroke="currentColor" stroke-linecap="round" stroke-width="2" fill="none" d="M5 7h14M5 12h14M5 17h10"/>',
            'chevron'      => '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" d="m9 5 7 7-7 7"/>',
            'chevron-down' => '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" d="m19 9-7 7-7-7"/>',
            'sun'          => '<path fill-rule="evenodd" d="M13 3a1 1 0 1 0-2 0v2a1 1 0 1 0 2 0V3ZM6.343 4.929A1 1 0 0 0 4.93 6.343l1.414 1.414a1 1 0 0 0 1.414-1.414L6.343 4.929Zm12.728 1.414a1 1 0 0 0-1.414-1.414l-1.414 1.414a1 1 0 0 0 1.414 1.414l1.414-1.414ZM12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm-9 4a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2H3Zm16 0a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2h-2ZM7.757 17.657a1 1 0 1 0-1.414-1.414l-1.414 1.414a1 1 0 1 0 1.414 1.414l1.414-1.414Zm9.9-1.414a1 1 0 0 0-1.414 1.414l1.414 1.414a1 1 0 0 0 1.414-1.414l-1.414-1.414ZM13 19a1 1 0 1 0-2 0v2a1 1 0 1 0 2 0v-2Z" clip-rule="evenodd"/>',
            'moon'         => '<path fill-rule="evenodd" d="M11.675 2.015a.998.998 0 0 0-.403.011C6.09 2.4 2 6.722 2 12c0 5.523 4.477 10 10 10 4.356 0 8.058-2.784 9.43-6.667a1 1 0 0 0-1.02-1.33c-.08.006-.105.005-.127.005h-.001l-.028-.002A5.227 5.227 0 0 0 20 14a8 8 0 0 1-8-8c0-.952.121-1.752.404-2.558a.996.996 0 0 0 .096-.428V3a1 1 0 0 0-.825-.985Z" clip-rule="evenodd"/>',
            'check'        => '<path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z" clip-rule="evenodd"/>',
            'x'            => '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" d="M6 18 18 6M6 6l12 12"/>',
            'info'         => '<path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.408-5.5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01ZM10 10a1 1 0 1 0 0 2h1v3h-1a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2h-1v-4a1 1 0 0 0-1-1h-2Z" clip-rule="evenodd"/>',
            'warning'      => '<path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd"/>',
            'plus'         => '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" d="M5 12h14m-7 7V5"/>',
            'search'       => '<path stroke="currentColor" stroke-linecap="round" stroke-width="2" fill="none" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>',
            'pencil'       => '<path fill-rule="evenodd" d="M14 4.182A4.136 4.136 0 0 1 16.9 3c1.087 0 2.13.425 2.899 1.182A4.01 4.01 0 0 1 21 7.037c0 1.068-.43 2.092-1.194 2.849L18.5 11.214l-5.8-5.71 1.287-1.31.012-.012Zm-2.717 2.763L6.186 12.13l2.175 2.141 5.063-5.218-2.141-2.108Zm-6.25 6.886-1.98 5.849a.992.992 0 0 0 .245 1.026 1.03 1.03 0 0 0 1.043.242L10.282 19l-5.25-5.168Zm6.954 4.01 5.096-5.186-2.218-2.183-5.063 5.218 2.185 2.15Z" clip-rule="evenodd"/>',
            'key'          => '<path fill-rule="evenodd" d="M15.75 2a6.25 6.25 0 0 0-5.981 8.08l-6.476 6.477A1 1 0 0 0 3 17.264V20a1 1 0 0 0 1 1h2.736a1 1 0 0 0 .707-.293L8 20.15V19a1 1 0 0 1 1-1h1.15l1.003-1.003A.998.998 0 0 1 11 16.15V15h1.15l1.77-1.77A6.25 6.25 0 1 0 15.75 2ZM17 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd"/>',
            'ban'          => '<path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm5.757-5.657a1 1 0 0 0-.07 1.344l8.626 8.626a1 1 0 0 0 1.344-.07A8 8 0 0 0 7.757 6.343Zm-1.414 1.414A8 8 0 0 0 16.243 17.657a1 1 0 0 0-.07-1.344L7.687 7.687a1 1 0 0 0-1.344.07Z" clip-rule="evenodd"/>',
            'copy'         => '<path fill-rule="evenodd" d="M18 3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1V9a4 4 0 0 0-4-4h-3a1.99 1.99 0 0 0-1 .267V5a2 2 0 0 1 2-2h7Z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M8 7.054V11H4.2a2 2 0 0 1 .281-.432l2.46-2.87A2 2 0 0 1 8 7.054ZM10 7v4a2 2 0 0 1-2 2H4v6a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z" clip-rule="evenodd"/>',
            'eye'          => '<path stroke="currentColor" stroke-width="2" fill="none" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/><path stroke="currentColor" stroke-width="2" fill="none" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
            'inbox'        => '<path fill-rule="evenodd" d="M5.024 3.783A1 1 0 0 1 6 3h12a1 1 0 0 1 .976.783L20.802 12h-4.244a1.99 1.99 0 0 0-1.824 1.205 2.978 2.978 0 0 1-5.468 0A1.991 1.991 0 0 0 7.442 12H3.198l1.826-8.217ZM3 14v5a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5h-4.43a4.978 4.978 0 0 1-9.14 0H3Z" clip-rule="evenodd"/>',
            'grid'         => '<path fill-rule="evenodd" d="M4.857 3A1.857 1.857 0 0 0 3 4.857v4.286C3 10.169 3.831 11 4.857 11h4.286A1.857 1.857 0 0 0 11 9.143V4.857A1.857 1.857 0 0 0 9.143 3H4.857Zm10 0A1.857 1.857 0 0 0 13 4.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 9.143V4.857A1.857 1.857 0 0 0 19.143 3h-4.286Zm-10 10A1.857 1.857 0 0 0 3 14.857v4.286C3 20.169 3.831 21 4.857 21h4.286A1.857 1.857 0 0 0 11 19.143v-4.286A1.857 1.857 0 0 0 9.143 13H4.857Zm10 0A1.857 1.857 0 0 0 13 14.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 19.143v-4.286A1.857 1.857 0 0 0 19.143 13h-4.286Z" clip-rule="evenodd"/>',
            'clock'        => '<path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd"/>',
            'arrow-right'  => '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" d="M19 12H5m14 0-4 4m4-4-4-4"/>',
            'compass'      => '<path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-3.707a1 1 0 0 0-1.022-.242l-5 1.667a1 1 0 0 0-.633.633l-1.667 5a1 1 0 0 0 1.265 1.265l5-1.667a1 1 0 0 0 .633-.633l1.667-5a1 1 0 0 0-.243-1.023Z" clip-rule="evenodd"/>',
            'clipboard'    => '<path fill-rule="evenodd" d="M8 3a1 1 0 0 0-1 1v.5H6a2 2 0 0 0-2 2V20a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6.5a2 2 0 0 0-2-2h-1V4a1 1 0 0 0-1-1H8Zm1 3.5V5h6v1.5H9Zm-1 5a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2H9Z" clip-rule="evenodd"/>',
            'calendar'     => '<path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" clip-rule="evenodd"/>',
        ];

        $body = $paths[$name] ?? $paths['info'];

        return '<svg class="' . esc($class, 'attr') . '" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">' . $body . '</svg>';
    }
}
