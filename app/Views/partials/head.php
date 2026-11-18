<?php
/**
 * Shared <head> content: meta, title, favicon, stylesheet and white-label colour.
 *
 * @var string|null $title
 */
$schoolName = (string) setting('School.name');
$pageTitle  = isset($title) && $title !== '' ? $title . ' · ' . $schoolName : $schoolName;
$tagline    = (string) setting('School.tagline');
$shareImage = brand_asset_url('shareImage') ?? brand_asset_url('icon') ?? school_logo_url();
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
<meta property="og:locale" content="id_ID">
<?php if ($shareImage !== null) : ?>
<meta property="og:image" content="<?= esc($shareImage, 'attr') ?>">
<meta name="twitter:card" content="<?= brand_asset_url('shareImage') !== null ? 'summary_large_image' : 'summary' ?>">
<?php endif ?>
<link rel="stylesheet" href="<?= esc(asset_url('app.css'), 'attr') ?>">
<style>:root{--brand:<?= brand_color() ?>}</style>
<script>
    try {
        var t = localStorage.getItem('spmb-theme');
        if (t === 'dark' || (t === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        if (localStorage.getItem('spmb-sidebar') === 'collapsed') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    } catch (e) {}
</script>
