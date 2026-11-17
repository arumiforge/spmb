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
    public string $tagline   = 'Penerimaan Murid Baru';
    public string $address   = '';
    public string $phone     = '';
    public string $whatsapp  = '';
    public string $email     = '';
    public string $website   = '';

    /**
     * Paths relative to the public uploads area; empty means the default asset.
     */
    public string $logoPath = '';

    public string $faviconPath = '';

    /**
     * Brand colour (hex). The layout derives the primary colour scale from it.
     */
    public string $primaryColor = '#2563EB';

    public string $footerText = '';

    /**
     * Indonesian time zone label shown next to times: WIB, WITA or WIT.
     */
    public string $timezoneLabel = 'WIB';
}
