<?php
/**
 * Staff guide inside the dashboard (D-097). Variables: see _isi.php.
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?= view('Modules\Panduan\Views\_isi', ['role' => $role, 'slug' => $slug, 'others' => $others, 'base' => $base, 'html' => $html, 'toc' => $toc, 'heading' => false]) ?>
<?= $this->endSection() ?>
