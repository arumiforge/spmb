<?php
/**
 * Sign-in and verification layout: centred card with the school identity.
 * Also used by Shield for its own screens (Config\Auth::$views['layout']).
 *
 * @var string|null $title
 */
?>
<!doctype html>
<html lang="id">
<head>
    <?= view('partials/head', ['title' => $title ?? '']) ?>
</head>
<body class="bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">
<main class="flex min-h-screen flex-col items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
        <div class="mb-6 flex items-center justify-between">
            <?= view('partials/school_brand', ['brand' => ['size' => 'h-10 w-10']]) ?>
            <?= view('partials/theme_toggle') ?>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm sm:p-8 dark:bg-gray-800">
            <?= $this->renderSection('main') ?>
        </div>
        <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
            &copy; <?= date('Y') ?> <?= esc(setting('School.name')) ?>
        </p>
    </div>
</main>
<?= view('partials/flash', ['formErrorsInline' => true]) ?>
<script src="<?= esc(asset_url('app.js'), 'attr') ?>"></script>
</body>
</html>
