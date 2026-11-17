<?php
/**
 * Shared <head> content: meta, title, favicon, stylesheet and white-label colour.
 *
 * @var string|null $title
 */
$schoolName = (string) setting('School.name');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="<?= isset($robots) ? esc($robots, 'attr') : 'noindex' ?>">
<title><?= esc(isset($title) && $title !== '' ? $title . ' · ' . $schoolName : $schoolName) ?></title>
<link rel="icon" href="<?= esc(school_favicon_url(), 'attr') ?>">
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
