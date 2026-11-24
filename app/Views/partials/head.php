<?php
/**
 * Shared <head> content: meta, title, favicon, stylesheet and white-label colour.
 *
 * On public pages ($alternates = true) it adds the hreflang links of the website languages and the
 * Arabic font (D-109); the slogan and description follow the page's language.
 *
 * @var string|null $title
 * @var bool|null   $alternates
 */
use App\Libraries\Locales;

$schoolName = (string) setting('School.name');
$pageTitle  = isset($title) && $title !== '' ? $title . ' · ' . $schoolName : $schoolName;
$tagline    = setting_localized('School.tagline');
$locale     = locale_current();
$languages  = ! empty($alternates) ? Locales::enabled() : [];
// The school's own share image first, then the generic Codex painting (brief 035) in the school's accent.
$accent       = (string) setting('School.illustrationAccent');
$genericShare = 'assets/img/share/bagikan-umum' . (preg_match('/\A[a-z]+\z/', $accent) === 1 && is_file(FCPATH . 'assets/img/share/bagikan-umum-' . $accent . '.jpg') ? '-' . $accent : '') . '.jpg';
$largeShare   = brand_asset_url('shareImage') !== null || is_file(FCPATH . $genericShare);
$shareImage   = brand_asset_url('shareImage') ?? (is_file(FCPATH . $genericShare) ? base_url($genericShare) : null) ?? brand_asset_url('icon') ?? school_logo_url();
$appIcon    = brand_asset_url('icon');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="<?= isset($robots) ? esc($robots, 'attr') : 'noindex' ?>">
<title><?= esc($pageTitle) ?></title>
<meta name="description" content="<?= esc(trim($tagline . ' ' . $schoolName), 'attr') ?>">
<meta name="theme-color" content="<?= esc(brand_color(), 'attr') ?>">
<link rel="icon" href="<?= esc(school_favicon_url(), 'attr') ?>">
<?php if ($appIcon !== null) : ?>
<link rel="apple-touch-icon" href="<?= esc($appIcon, 'attr') ?>">
<?php endif ?>
<?php /* Link previews (WhatsApp, Facebook, X, Telegram). */ ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= esc($schoolName, 'attr') ?>">
<meta property="og:title" content="<?= esc($pageTitle, 'attr') ?>">
<meta property="og:description" content="<?= esc(trim($tagline . ' ' . $schoolName), 'attr') ?>">
<meta property="og:url" content="<?= esc(current_url(), 'attr') ?>">
<meta property="og:locale" content="<?= Locales::OG[$locale] ?>">
<?php if (count($languages) > 1) : ?>
<?php foreach ($languages as $language) : ?>
<?php if ($language !== $locale) : ?>
<meta property="og:locale:alternate" content="<?= Locales::OG[$language] ?>">
<?php endif ?>
<?php endforeach ?>
<?php foreach ($languages as $language) : ?>
<link rel="alternate" hreflang="<?= $language ?>" href="<?= esc(locale_url($language), 'attr') ?>">
<?php endforeach ?>
<link rel="alternate" hreflang="x-default" href="<?= esc(locale_url(Locales::DEFAULT), 'attr') ?>">
<?php endif ?>
<?php if ($shareImage !== null) : ?>
<meta property="og:image" content="<?= esc($shareImage, 'attr') ?>">
<meta name="twitter:card" content="<?= $largeShare ? 'summary_large_image' : 'summary' ?>">
<?php endif ?>
<link rel="stylesheet" href="<?= esc(asset_url('app.css'), 'attr') ?>">
<?php if ($locale === 'ar' && is_file(FCPATH . 'build/fonts/noto-sans-arabic-400.woff2')) : ?>
<link rel="preload" href="<?= esc(base_url('build/fonts/noto-sans-arabic-400.woff2'), 'attr') ?>" as="font" type="font/woff2" crossorigin>
<?php endif ?>
<style>:root{--brand:<?= brand_color() ?>}</style>
<script>
    document.documentElement.classList.add('js');
    try {
        var t = localStorage.getItem('spmb-theme');
        // Light by default whatever the device theme (D-078); dark only when chosen with the toggle.
        if (t === 'dark') {
            document.documentElement.classList.add('dark');
        }
        if (localStorage.getItem('spmb-sidebar') === 'collapsed') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    } catch (e) {}
</script>
