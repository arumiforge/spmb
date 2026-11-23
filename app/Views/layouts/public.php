<?php
/**
 * Public website layout for parents and students: mobile first, large tap targets.
 *
 * Small bar on top (social media, staff sign-in), sticky header (menu inline from md,
 * drawer from the right below md), mobile bottom bar with Daftar raised in the middle (D-081).
 *
 * Sections: content (required), scripts (optional).
 *
 * @var string|null                               $title
 * @var list<array{label: string, url?: string}>  $breadcrumbs
 */
// Beranda and Panduan are fixed; enabled modules add links in Config/PublicMenu.php.
$nav      = public_nav();
$bottom   = public_bottom_nav($nav);
$address  = (string) setting('School.address');
$phone    = (string) setting('School.phone');
$whatsapp = (string) setting('School.whatsapp');
$email    = (string) setting('School.email');
$motto    = (string) setting('School.motto');
$social   = school_social_links();
?>
<!doctype html>
<html lang="id">
<head>
    <?= view('partials/head', ['title' => $title ?? '', 'robots' => 'index, follow']) ?>
    <?= $this->renderSection('head') ?>
</head>
<body class="flex min-h-screen flex-col bg-white pb-24 text-gray-900 antialiased md:pb-0 dark:bg-gray-900 dark:text-gray-100">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-primary-700 focus:shadow"><?= lang('App.skipToContent') ?></a>

<div class="bar-atas bg-primary-800 text-white dark:bg-gray-950" data-no-print>
    <div class="mx-auto flex h-10 max-w-screen-xl items-center justify-between gap-3 px-4 text-sm">
        <?php if ($social !== []) : ?>
            <ul class="flex items-center gap-1" aria-label="<?= lang('App.socialLabel') ?>">
                <?php foreach ($social as $link) : ?>
                    <li>
                        <a href="<?= esc($link['url'], 'attr') ?>" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-white/85 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/60">
                            <?= social_icon($link['network'], 'h-4 w-4') ?><span class="sr-only"><?= esc($link['label']) ?></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php else : ?>
            <p class="hidden truncate text-white/85 sm:block"><?= esc($motto !== '' ? $motto : (string) setting('School.tagline')) ?></p>
        <?php endif ?>
        <div class="ms-auto flex items-center gap-4">
            <?php if ($phone !== '') : ?>
                <a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone), 'attr') ?>" class="hidden items-center gap-1.5 text-white/85 hover:text-white lg:inline-flex"><?= icon('phone', 'h-4 w-4') ?><?= esc($phone) ?></a>
            <?php endif ?>
            <?php if ($email !== '') : ?>
                <a href="mailto:<?= esc($email, 'attr') ?>" class="hidden items-center gap-1.5 text-white/85 hover:text-white lg:inline-flex"><?= icon('mail', 'h-4 w-4') ?><?= esc($email) ?></a>
            <?php endif ?>
            <a href="<?= url_to('login') ?>" class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 font-medium text-white hover:underline focus:outline-none focus:ring-2 focus:ring-white/60">
                <?= icon('log-in', 'h-4 w-4') ?><?= lang('App.staffLogin') ?>
            </a>
        </div>
    </div>
</div>

<header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-700 dark:bg-gray-900/95">
    <nav class="mx-auto flex max-w-screen-xl items-center justify-between gap-2 px-4 py-3" aria-label="<?= lang('App.publicNavLabel') ?>">
        <?= view('partials/school_brand', ['brand' => ['size' => 'h-9 w-9', 'wide' => true, 'wideSize' => 'h-10 sm:h-12']]) ?>
        <ul class="hidden items-center gap-1 font-medium md:flex lg:gap-2">
            <?php foreach ($nav as $item) : ?>
                <?php $active = $item['active']; ?>
                <li>
                    <a href="<?= esc(site_url($item['url']), 'attr') ?>" <?= $active ? 'aria-current="page"' : '' ?>
                       class="block rounded-lg px-3 py-2 <?= $active ? 'bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' ?>">
                        <?= esc($item['label']) ?>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
        <div class="flex items-center gap-1">
            <?= view('partials/theme_toggle') ?>
            <button type="button" data-drawer-target="menu-publik" data-drawer-show="menu-publik" data-drawer-placement="right" aria-controls="menu-publik"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 md:hidden dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                <span class="sr-only"><?= lang('App.openMenu') ?></span><?= icon('menu', 'h-6 w-6') ?>
            </button>
        </div>
    </nav>
</header>

<div id="menu-publik" class="fixed right-0 top-0 z-50 flex h-dvh w-80 max-w-[85vw] translate-x-full flex-col overflow-y-auto bg-white p-4 shadow-xl transition-transform duration-300 md:hidden dark:bg-gray-800"
     tabindex="-1" aria-labelledby="menu-publik-judul" aria-hidden="true">
    <div class="mb-4 flex items-center justify-between gap-2">
        <p id="menu-publik-judul" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('App.menu') ?></p>
        <button type="button" data-drawer-hide="menu-publik" aria-controls="menu-publik" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
            <?= icon('x', 'h-6 w-6') ?><span class="sr-only"><?= lang('App.closeMenu') ?></span>
        </button>
    </div>
    <ul class="space-y-1 font-medium">
        <?php foreach ($nav as $item) : ?>
            <?php $active = $item['active']; ?>
            <li>
                <a href="<?= esc(site_url($item['url']), 'attr') ?>" <?= $active ? 'aria-current="page"' : '' ?>
                   class="flex items-center gap-3 rounded-lg px-3 py-3 <?= $active ? 'bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-white' : 'text-gray-800 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700' ?>">
                    <?= icon($item['icon'], 'h-5 w-5 shrink-0') ?><?= esc($item['label']) ?>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
    <div class="mt-auto space-y-2 border-t border-gray-200 pt-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
        <?php if ($phone !== '') : ?><a class="flex items-center gap-2 py-1.5 hover:underline" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone), 'attr') ?>"><?= icon('phone', 'h-4 w-4') ?><?= esc($phone) ?></a><?php endif ?>
        <?php if ($email !== '') : ?><a class="flex items-center gap-2 py-1.5 hover:underline" href="mailto:<?= esc($email, 'attr') ?>"><?= icon('mail', 'h-4 w-4') ?><?= esc($email) ?></a><?php endif ?>
        <a href="<?= url_to('login') ?>" class="flex items-center gap-2 py-1.5 font-medium text-primary-700 hover:underline dark:text-primary-400"><?= icon('log-in', 'h-4 w-4') ?><?= lang('App.staffLogin') ?></a>
    </div>
</div>

<main id="main" class="flex-1">
    <?php if (! empty($breadcrumbs)) : ?>
        <div class="mx-auto max-w-screen-xl px-4 pt-4">
            <?= view('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>
        </div>
    <?php endif ?>
    <?= $this->renderSection('content') ?>
</main>

<footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
    <div class="mx-auto grid max-w-screen-xl gap-8 px-4 py-10 text-sm md:grid-cols-3">
        <div>
            <div class="mb-3 flex items-center gap-3">
                <?php if (($footerLogo = school_logo_url()) !== null) : ?>
                    <img src="<?= esc($footerLogo, 'attr') ?>"<?= App\Libraries\BrandingAssets::sizeAttributes('logo') ?> alt="" loading="lazy" class="h-12 w-12 shrink-0 object-contain">
                <?php endif ?>
                <p class="font-semibold text-gray-900 dark:text-white"><?= esc(setting('School.name')) ?></p>
            </div>
            <?php if ($motto !== '') : ?><p class="mb-3 text-gray-600 italic dark:text-gray-400"><?= esc($motto) ?></p><?php endif ?>
            <?php $partnerName = (string) setting('School.partnerName'); $partnerLogo = brand_asset_url('partnerLogo'); ?>
            <?php if ($partnerName !== '' || $partnerLogo !== null) : ?>
                <div class="mt-4 flex items-center gap-3">
                    <?php if ($partnerLogo !== null) : ?>
                        <img src="<?= esc($partnerLogo, 'attr') ?>"<?= App\Libraries\BrandingAssets::sizeAttributes('partnerLogo') ?> loading="lazy" alt="<?= esc('Logo ' . $partnerName, 'attr') ?>" class="h-12 w-auto max-w-[8rem] shrink-0 object-contain">
                    <?php endif ?>
                    <p class="text-gray-600 dark:text-gray-400"><?= lang('App.partnerLabel') ?><?php if ($partnerName !== '') : ?><br><span class="font-medium text-gray-900 dark:text-white"><?= esc($partnerName) ?></span><?php endif ?></p>
                </div>
            <?php endif ?>
        </div>
        <?php if ($address !== '' || $phone !== '' || $whatsapp !== '' || $email !== '') : ?>
        <div>
            <h2 class="mb-3 font-semibold text-gray-900 dark:text-white"><?= lang('App.contactTitle') ?></h2>
            <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                <?php if ($address !== '') : ?><li class="flex gap-2"><?= icon('map-pin', 'mt-0.5 h-4 w-4 shrink-0') ?><span><?= nl2br(esc($address)) ?></span></li><?php endif ?>
                <?php if ($phone !== '') : ?><li><a class="flex items-center gap-2 hover:underline" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone), 'attr') ?>"><?= icon('phone', 'h-4 w-4 shrink-0') ?><?= esc($phone) ?></a></li><?php endif ?>
                <?php if ($whatsapp !== '') : ?><li><a class="flex items-center gap-2 hover:underline" href="<?= esc(whatsapp_url($whatsapp), 'attr') ?>" target="_blank" rel="noopener"><?= social_icon('whatsapp', 'h-4 w-4 shrink-0') ?><?= esc($whatsapp) ?></a></li><?php endif ?>
                <?php if ($email !== '') : ?><li><a class="flex items-center gap-2 break-all hover:underline" href="mailto:<?= esc($email, 'attr') ?>"><?= icon('mail', 'h-4 w-4 shrink-0') ?><?= esc($email) ?></a></li><?php endif ?>
            </ul>
        </div>
        <?php endif ?>
        <div>
            <h2 class="mb-3 font-semibold text-gray-900 dark:text-white"><?= lang('App.footerLinks') ?></h2>
            <ul class="grid grid-cols-2 gap-2 text-gray-600 md:grid-cols-1 dark:text-gray-400">
                <?php foreach ($nav as $item) : ?>
                    <li><a class="hover:underline" href="<?= esc(site_url($item['url']), 'attr') ?>"><?= esc($item['label']) ?></a></li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
    <div class="border-t border-gray-200 dark:border-gray-700">
        <div class="mx-auto flex max-w-screen-xl flex-col items-center justify-between gap-1 px-4 py-4 text-center text-xs text-gray-500 sm:flex-row sm:text-start dark:text-gray-400">
            <p><?= esc(setting('School.footerText') ?: '© ' . date('Y') . ' ' . setting('School.name') . '. ' . lang('App.footerRights')) ?></p>
            <p><?= lang('App.developedBy') ?></p>
        </div>
    </div>
</footer>

<?= view('partials/bottom_nav', ['bottomNav' => ['label' => lang('App.bottomNavLabel'), 'items' => $bottom]]) ?>
<?= view('partials/flash') ?>
<script src="<?= esc(asset_url('app.js'), 'attr') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
