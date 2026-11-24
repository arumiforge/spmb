<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Public website extras of the Situs module (docs/modules/situs.md). Real
 * values: setting('Situs.<property>'), edited by Admin Sekolah under
 * Website (permission situs.kelola).
 */
class Situs extends BaseConfig
{
    /**
     * News ticker under the public header ("Berita singkat"). Items live in
     * the site_news table; the bar only shows while this is on and at least
     * one item is active today.
     */
    public bool $newsEnabled = false;

    /**
     * Seconds each news item stays before the next one slides in (3–30).
     */
    public int $newsInterval = 5;

    /**
     * Popup on the home page only. Off by default.
     */
    public bool $popupEnabled = false;

    public string $popupTitle = '';

    /**
     * Light Markdown like the Tanya Jawab answers; single line breaks are kept.
     */
    public string $popupText = '';

    /**
     * Stored file names in writable/uploads/situs/ ('' = none). The attachment
     * keeps its original name for the download.
     */
    public string $popupImage = '';

    public string $popupAttachment     = '';
    public string $popupAttachmentName = '';
    public string $popupButtonLabel    = '';

    /**
     * Internal path ("/daftar") or https:// address.
     */
    public string $popupButtonUrl = '';

    /**
     * Shown from / until these dates, inclusive (Y-m-d, '' = no limit).
     */
    public string $popupStartsOn = '';

    public string $popupEndsOn = '';

    /**
     * once = until the popup is changed, daily = once a day, always = every visit.
     */
    public string $popupFrequency = 'once';

    /**
     * Raised on every save, so "once" shows the changed popup again.
     */
    public int $popupVersion = 0;

    /**
     * The "Tautan" column of the public footer as JSON
     * ([{"label", "url", "active"}, ...]). '' = the default list
     * (Cek Status, Tanya Jawab, Panduan, D-092).
     */
    public string $footerLinks = '';

    /**
     * Optional translations of the popup (empty = the Indonesian text shows).
     */
    public string $popupTitle_en       = '';
    public string $popupTitle_ar       = '';
    public string $popupText_en        = '';
    public string $popupText_ar        = '';
    public string $popupButtonLabel_en = '';
    public string $popupButtonLabel_ar = '';
}
