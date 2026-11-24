<?php
/**
 * Public guide page (applicants and parents). Variables: see _isi.php.
 */
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-screen-xl px-4 py-8">
    <?= view('Modules\Panduan\Views\_isi', ['role' => $role, 'slug' => $slug, 'others' => $others, 'base' => $base, 'html' => $html, 'toc' => $toc, 'heading' => true]) ?>
</div>
<?= $this->endSection() ?>
