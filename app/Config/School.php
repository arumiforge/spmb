<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * School identity and branding (white label).
 *
 * These are defaults only. Real values are stored in the `settings` table and
 * read with setting('School.<property>'), so each installation is branded
 * from the admin panel without code changes. Never hard-code a school name,
 * logo or colour anywhere else.
 */
class School extends BaseConfig
{
    public string $name      = 'Nama Sekolah';
    public string $shortName = 'Sekolah';
    public string $tagline   = 'Sistem Penerimaan Murid Baru (SPMB)';

    /**
     * School slogan (motto), shown under the school name on the home page and in the footer (D-087).
     */
    public string $motto = '';

    public string $address  = '';
    public string $phone    = '';
    public string $whatsapp = '';
    public string $email    = '';
    public string $website  = '';

    /**
     * Google Maps embed URL (https://www.google.com/maps/embed?...), '' = no map (D-085).
     */
    public string $mapEmbed = '';

    /**
     * Social media links in the small bar above the public header ('' = hidden, D-081).
     */
    public string $socialInstagram = '';

    public string $socialFacebook = '';
    public string $socialYoutube  = '';
    public string $socialTiktok   = '';

    /**
     * Paths relative to the public uploads area; empty means the default asset.
     */
    public string $logoPath = '';

    public string $faviconPath = '';

    /**
     * Landscape logo for light backgrounds, and its version for dark mode
     * (shown in the website and sign-in headers instead of logo + name).
     */
    public string $logoWidePath = '';

    public string $logoWideDarkPath = '';

    /**
     * App icon (square PNG, 512 px): home-screen icon and fallback link preview image.
     */
    public string $iconPath = '';

    /**
     * Image shown when a link to the website is shared (1200 × 630).
     */
    public string $shareImagePath = '';

    /**
     * Parent organisation shown in the website footer, e.g. the foundation or
     * association the school belongs to ('' = none).
     */
    public string $partnerName = '';

    public string $partnerLogoPath = '';

    /**
     * Own pictures for the home hero slideshow (D-084); when all are empty the
     * default Codex paintings are shown.
     */
    public string $heroSlide1Path = '';

    public string $heroSlide2Path = '';
    public string $heroSlide3Path = '';
    public string $heroSlide4Path = '';
    public string $heroSlide5Path = '';
    public string $heroSlide6Path = '';

    /**
     * Accent set of the painted illustrations: '' = default (blue accent) or
     * 'hijau' (green accent, ai/prompts/013). Missing variants fall back to the default.
     */
    public string $illustrationAccent = '';

    /**
     * Brand colour (hex). The layout derives the primary colour scale from it.
     */
    public string $primaryColor = '#2563EB';

    public string $footerText = '';

    /**
     * Indonesian time zone label shown next to times: WIB, WITA or WIT.
     */
    public string $timezoneLabel = 'WIB';

    /**
     * English and Arabic versions of the public website (D-109), switched off by the school in
     * Identitas Sekolah → Bahasa website. Indonesian is always on.
     */
    public bool $languageEn = true;

    public bool $languageAr = true;

    /**
     * Optional English and Arabic versions of the texts above (D-109); empty = the Indonesian text shows.
     * Read them with setting_localized('School.motto').
     */
    public string $tagline_en = '';

    public string $tagline_ar    = '';
    public string $motto_en      = '';
    public string $motto_ar      = '';
    public string $address_en    = '';
    public string $address_ar    = '';
    public string $footerText_en = '';
    public string $footerText_ar = '';
}
