<?php
/**
 * Public website layout for parents and students: mobile first, large tap targets.
 *
 * Sections: content (required).
 *
 * @var string|null                               $title
 * @var list<array{label: string, url?: string}>  $breadcrumbs
 */
// Home and Panduan are fixed; enabled modules add links in Config/PublicMenu.php.
$nav = [
    ['label' => lang('App.home'), 'url' => '/', 'order' => 0],
    ['label' => lang('App.guide'), 'url' => 'panduan', 'order' => 90],
    ...service('modules')->collect('Config/PublicMenu.php'),
];
usort($nav, static fn (array $a, array $b): int => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));
$current = trim(uri_string(), '/');
$address = (string) setting('School.address');
$phone   = (string) setting('School.phone');
$email   = (string) setting('School.email');
?>
<!doctype html>
<html lang="id">
<head>
    <?= view('partials/head', ['title' => $title ?? '', 'robots' => 'index, follow']) ?>
</head>
<body class="flex min-h-screen flex-col bg-white text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-primary-700 focus:shadow"><?= lang('App.skipToContent') ?></a>

<header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-700 dark:bg-gray-900/95">
    <nav class="mx-auto flex max-w-screen-xl flex-wrap items-center justify-between gap-2 px-4 py-3" aria-label="<?= lang('App.publicNavLabel') ?>">
        <?= view('partials/school_brand', ['brand' => ['size' => 'h-9 w-9', 'wide' => true, 'wideSize' => 'h-10 sm:h-12']]) ?>
        <div class="flex items-center gap-1 md:order-2">
            <?= view('partials/theme_toggle') ?>
            <button data-collapse-toggle="public-menu" type="button" aria-controls="public-menu" aria-expanded="false"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 md:hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                <span class="sr-only"><?= lang('App.openMenu') ?></span><?= icon('menu', 'h-6 w-6') ?>
            </button>
        </div>
        <div id="public-menu" class="hidden w-full md:order-1 md:block md:w-auto">
            <ul class="mt-2 flex flex-col gap-1 rounded-lg border border-gray-100 bg-gray-50 p-2 font-medium md:mt-0 md:flex-row md:gap-2 md:border-0 md:bg-transparent md:p-0 dark:border-gray-700 dark:bg-gray-800 md:dark:bg-transparent">
                <?php foreach ($nav as $item) : ?>
                    <?php $active = trim($item['url'], '/') === $current || ($item['url'] !== '/' && str_starts_with($current, trim($item['url'], '/'))); ?>
                    <li>
                        <a href="<?= esc(site_url($item['url']), 'attr') ?>" <?= $active ? 'aria-current="page"' : '' ?>
                           class="block rounded-lg px-4 py-3 md:py-2 <?= $active ? 'bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' ?>">
                            <?= esc($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </nav>
</header>

<main id="main" class="flex-1">
    <?php if (! empty($breadcrumbs)) : ?>
        <div class="mx-auto max-w-screen-xl px-4 pt-4">
            <?= view('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>
        </div>
    <?php endif ?>
    <?= $this->renderSection('content') ?>
</main>

<footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
    <div class="mx-auto grid max-w-screen-xl gap-6 px-4 py-8 text-sm md:grid-cols-3">
        <div>
            <div class="mb-2 flex items-center gap-3">
                <?php if (($footerLogo = school_logo_url()) !== null) : ?>
                    <img src="<?= esc($footerLogo, 'attr') ?>" alt="" class="h-12 w-12 shrink-0 object-contain">
                <?php endif ?>
                <p class="font-semibold text-gray-900 dark:text-white"><?= esc(setting('School.name')) ?></p>
            </div>
            <?php if ($address !== '') : ?><p class="text-gray-600 dark:text-gray-400"><?= nl2br(esc($address)) ?></p><?php endif ?>
            <?php $partnerName = (string) setting('School.partnerName'); $partnerLogo = brand_asset_url('partnerLogo'); ?>
            <?php if ($partnerName !== '' || $partnerLogo !== null) : ?>
                <div class="mt-4 flex items-center gap-3">
                    <?php if ($partnerLogo !== null) : ?>
                        <img src="<?= esc($partnerLogo, 'attr') ?>" alt="<?= esc('Logo ' . $partnerName, 'attr') ?>" class="h-12 w-auto max-w-[8rem] shrink-0 rounded object-contain">
                    <?php endif ?>
                    <p class="text-gray-600 dark:text-gray-400"><?= lang('App.partnerLabel') ?><?php if ($partnerName !== '') : ?><br><span class="font-medium text-gray-900 dark:text-white"><?= esc($partnerName) ?></span><?php endif ?></p>
                </div>
            <?php endif ?>
        </div>
        <div class="space-y-1 text-gray-600 dark:text-gray-400">
            <?php if ($phone !== '') : ?><p>Telepon: <a class="hover:underline" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone), 'attr') ?>"><?= esc($phone) ?></a></p><?php endif ?>
            <?php if ($email !== '') : ?><p>Email: <a class="hover:underline" href="mailto:<?= esc($email, 'attr') ?>"><?= esc($email) ?></a></p><?php endif ?>
        </div>
        <div class="md:text-end">
            <a href="<?= url_to('login') ?>" class="text-gray-600 hover:underline dark:text-gray-400"><?= lang('App.staffLogin') ?></a>
        </div>
    </div>
    <div class="border-t border-gray-200 px-4 py-4 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
        <?= esc(setting('School.footerText') ?: '© ' . date('Y') . ' ' . setting('School.name') . '. ' . lang('App.footerRights')) ?>
    </div>
</footer>

<?= view('partials/flash') ?>
<script src="<?= esc(asset_url('app.js'), 'attr') ?>"></script>
</body>
</html>
