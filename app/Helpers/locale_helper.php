<?php

declare(strict_types=1);

use App\Libraries\Locales;
use Config\School;

/*
 * Website language helpers (D-109, docs/i18n.md). The public site is Indonesian (default), English
 * (/en/…) and Arabic (/ar/…, right to left); the staff dashboard and PDFs stay Indonesian.
 */

if (! function_exists('locale_current')) {
    /**
     * Language of the current page: 'id', 'en' or 'ar'.
     */
    function locale_current(): string
    {
        return Locales::current();
    }
}

if (! function_exists('locale_is_rtl')) {
    function locale_is_rtl(): bool
    {
        return Locales::isRtl();
    }
}

if (! function_exists('localized')) {
    /**
     * A translatable column in the current language with the Indonesian fallback:
     * localized($wave, 'name') gives name_en on English pages when filled, else name.
     *
     * @param array<string, mixed>|object $row
     */
    function localized(array|object $row, string $field): string
    {
        return Locales::field($row, $field);
    }
}

if (! function_exists('setting_localized')) {
    /**
     * A school setting in the current language with the Indonesian fallback:
     * setting_localized('School.motto') reads School.motto_en on English pages when filled.
     * The built-in default slogan (School.tagline) is translated by the language files.
     */
    function setting_localized(string $key): string
    {
        $value = Locales::setting($key);

        if ($key === 'School.tagline' && ! Locales::isDefault() && $value === (new School())->tagline) {
            return lang('App.defaultTagline');
        }

        return $value;
    }
}

if (! function_exists('locale_url')) {
    /**
     * The current page in another language (switcher and hreflang links).
     */
    function locale_url(string $locale): string
    {
        return Locales::currentUrlIn($locale);
    }
}

if (! function_exists('locale_month_names')) {
    /**
     * Month names 1–12 in a language (Gregorian calendar).
     *
     * @return array<int, string>
     */
    function locale_month_names(?string $locale = null): array
    {
        $names = match ($locale ?? Locales::current()) {
            'en'    => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'ar'    => ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
            default => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        };

        return array_combine(range(1, 12), $names);
    }
}

if (! function_exists('locale_day_names')) {
    /**
     * Day names in a language, 0 = Sunday … 6 = Saturday (as date('w')).
     *
     * @return list<string>
     */
    function locale_day_names(?string $locale = null): array
    {
        return match ($locale ?? Locales::current()) {
            'en'    => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'ar'    => ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
            default => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        };
    }
}
