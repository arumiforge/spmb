<?php
/**
 * Staff area layout: full width, collapsible sidebar (drawer below lg),
 * navbar with user menu, breadcrumb bar on every page, toasts.
 *
 * Sections: content (required), actions (optional buttons next to the page title).
 *
 * @var string                                        $title
 * @var list<array{label: string, url?: string}>      $breadcrumbs
 * @var string|null                                   $subtitle
 */
$user     = auth()->user();
$name     = $user->full_name ?: $user->username;
$menu     = service('menu')->forUser($user, uri_string());
$subtitle ??= null;

// Mobile bottom bar (D-081): Dasbor · Data Pendaftar · CARI (raised) · Periksa Berkas · Pengaturan,
// only with links this user may open; a missing slot takes the next allowed fallback.
$allowed = [];
foreach ($menu as $group) {
    foreach ($group['items'] as $item) {
        $allowed[trim($item['url'], '/')] = $item;
    }
}
$used = [];
$pick = static function (array $urls) use ($allowed, &$used): ?array {
    foreach ($urls as $url) {
        if (isset($allowed[$url]) && ! in_array($url, $used, true)) {
            $used[] = $url;

            return $allowed[$url];
        }
    }

    return null;
};
$short  = ['admin' => lang('App.dashboard'), 'admin/pendaftar' => lang('App.navApplicants'), 'admin/berkas' => lang('App.navDocuments'),
    'admin/tes/kehadiran' => lang('App.navAttendance'), 'admin/kartu' => lang('App.navCards'), 'admin/tes/peserta' => lang('App.navTestTakers')];
$toItem = static fn (array $item): array => ['label' => $short[trim($item['url'], '/')] ?? $item['label'], 'url' => $item['url'], 'icon' => $item['icon'] ?? 'info', 'active' => $item['active']];
$canSearch = isset($allowed['admin/pendaftar']);
$bottom    = [];
if (($first = $pick(['admin'])) !== null) {
    $bottom[] = $toItem($first);
}
if (($second = $pick(['admin/pendaftar', 'admin/tes/kehadiran', 'admin/kartu'])) !== null) {
    $bottom[] = $toItem($second);
}
if ($canSearch) {
    $bottom[] = ['label' => lang('App.searchApplicant'), 'icon' => 'user-round-search', 'center' => true, 'attrs' => 'data-dialog-open="cari-cepat" aria-haspopup="dialog"'];
}
if (($fourth = $pick(['admin/berkas', 'admin/tes/kehadiran', 'admin/kartu', 'admin/tes/peserta', 'admin/panduan'])) !== null) {
    $bottom[] = $toItem($fourth);
}
// Last slot: Pengaturan (D-095) — the first settings page this user may open, active on any of them.
// Without any settings access it stays the Menu button; the drawer also opens from the top bar.
$settings = [];
foreach ($menu as $group) {
    if ($group['label'] === 'Pengaturan') {
        $settings = $group['items'];
    }
}
$bottom[] = $settings !== []
    ? ['label' => lang('App.navSettings'), 'url' => $settings[0]['url'], 'icon' => 'settings', 'active' => in_array(true, array_column($settings, 'active'), true)]
    : ['label' => lang('App.menu'), 'icon' => 'menu', 'attrs' => 'data-drawer-target="sidebar" data-drawer-toggle="sidebar" aria-controls="sidebar"'];
?>
<!doctype html>
<html lang="id">
<head>
    <?= view('partials/head', ['title' => $title ?? '']) ?>
    <?= $this->renderSection('head') ?>
</head>
<body class="bg-gray-50 pb-24 text-gray-900 antialiased md:pb-0 dark:bg-gray-900 dark:text-gray-100">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-primary-700 focus:shadow"><?= lang('App.skipToContent') ?></a>

<nav class="fixed top-0 z-40 flex h-16 w-full items-center border-b border-gray-200 bg-white px-3 sm:px-4 dark:border-gray-700 dark:bg-gray-800">
    <div class="flex w-full items-center justify-between gap-2">
        <div class="flex min-w-0 items-center gap-1">
            <button type="button" data-sidebar-collapse aria-expanded="true" aria-controls="sidebar"
                    class="hidden rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 lg:inline-flex dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                <span class="sr-only"><?= lang('App.collapseMenu') ?></span><?= icon('menu', 'h-6 w-6') ?>
            </button>
            <button type="button" data-drawer-target="sidebar" data-drawer-toggle="sidebar" aria-controls="sidebar"
                    class="inline-flex rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 lg:hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                <span class="sr-only"><?= lang('App.openMenu') ?></span><?= icon('menu', 'h-6 w-6') ?>
            </button>
            <?= view('partials/school_brand', ['brand' => ['href' => site_url('admin'), 'class' => 'ms-1']]) ?>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <a href="<?= site_url('/') ?>" target="_blank" rel="noopener" title="<?= lang('App.viewSite') ?>"
               class="hidden items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 sm:inline-flex dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                <?= icon('house', 'h-5 w-5') ?><span class="hidden md:inline"><?= lang('App.viewSite') ?></span><span class="sr-only md:hidden"><?= lang('App.viewSite') ?></span>
            </a>
            <?= view('partials/theme_toggle') ?>
            <button type="button" id="user-menu-button" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom-end" aria-expanded="false"
                    class="ms-1 flex items-center gap-2 rounded-full text-sm focus:outline-none focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600">
                <span class="sr-only"><?= lang('App.userMenu') ?></span>
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600 font-semibold text-white"><?= esc(initials($name)) ?></span>
            </button>
            <div id="user-dropdown" class="z-50 hidden w-64 divide-y divide-gray-100 rounded-lg bg-white shadow-lg dark:divide-gray-600 dark:bg-gray-700">
                <div class="px-4 py-3">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white"><?= esc($name) ?></p>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400"><?= esc($user->email) ?></p>
                </div>
                <ul class="p-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <li><a href="<?= site_url('admin/profil') ?>" class="flex items-center gap-2 rounded-md px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><?= icon('user', 'h-4 w-4 text-gray-500 dark:text-gray-400') ?><?= lang('App.myProfile') ?></a></li>
                    <li><a href="<?= site_url('admin/profil/verifikasi-dua-langkah') ?>" class="flex items-center gap-2 rounded-md px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><?= icon('shield', 'h-4 w-4 text-gray-500 dark:text-gray-400') ?><?= lang('App.twoFactor') ?></a></li>
                    <li><a href="<?= site_url('admin/profil/kata-sandi') ?>" class="flex items-center gap-2 rounded-md px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><?= icon('key', 'h-4 w-4 text-gray-500 dark:text-gray-400') ?><?= lang('App.changePassword') ?></a></li>
                    <li><a href="<?= site_url('admin/panduan') ?>" class="flex items-center gap-2 rounded-md px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><?= icon('book', 'h-4 w-4 text-gray-500 dark:text-gray-400') ?><?= lang('App.guide') ?></a></li>
                </ul>
                <div class="p-2">
                    <a href="<?= url_to('logout') ?>" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-gray-600"><?= icon('logout', 'h-4 w-4') ?><?= lang('App.logout') ?></a>
                </div>
            </div>
        </div>
    </div>
</nav>

<aside id="sidebar" aria-label="<?= lang('App.mainMenu') ?>"
       class="fixed left-0 top-0 z-50 h-screen w-72 max-w-[85vw] -translate-x-full transition-transform lg:z-30 lg:w-72 lg:translate-x-0 lg:pt-16">
    <div class="flex h-full flex-col overflow-y-auto border-r border-gray-200 bg-white px-3 py-4 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 px-2 lg:hidden">
            <?= view('partials/school_brand', ['brand' => ['href' => site_url('admin')]]) ?>
        </div>
        <?php foreach ($menu as $group) : ?>
            <?php if ($group['label'] !== '') : ?>
                <p class="mb-2 mt-5 px-2 text-sm font-medium text-gray-500 dark:text-gray-400" data-sidebar-label><?= esc($group['label']) ?></p>
            <?php endif ?>
            <ul class="space-y-1">
                <?php foreach ($group['items'] as $item) : ?>
                    <li>
                        <a href="<?= esc(site_url($item['url']), 'attr') ?>" title="<?= esc($item['label'], 'attr') ?>"
                           <?= $item['active'] ? 'aria-current="page"' : '' ?>
                           class="group flex items-center rounded-lg p-2 text-base font-medium <?= $item['active'] ? 'bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-white' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' ?>">
                            <?= icon($item['icon'] ?? 'info', 'h-5 w-5 shrink-0 ' . ($item['active'] ? 'text-primary-600 dark:text-white' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white')) ?>
                            <span class="ms-3 truncate" data-sidebar-label><?= esc($item['label']) ?></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endforeach ?>
        <div class="mt-auto space-y-1 border-t border-gray-200 pt-4 dark:border-gray-700">
            <a href="<?= site_url('/') ?>" target="_blank" rel="noopener" title="<?= lang('App.viewSite') ?>" class="group flex items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                <?= icon('house', 'h-5 w-5 shrink-0 text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white') ?>
                <span class="ms-3 flex-1 truncate" data-sidebar-label><?= lang('App.viewSite') ?></span>
                <span class="shrink-0" data-sidebar-label><?= icon('external-link', 'h-4 w-4 text-gray-400') ?><span class="sr-only"><?= lang('App.opensNewTab') ?></span></span>
            </a>
            <a href="<?= url_to('logout') ?>" title="<?= lang('App.logout') ?>" class="group flex items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                <?= icon('logout', 'h-5 w-5 shrink-0 text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white') ?>
                <span class="ms-3" data-sidebar-label><?= lang('App.logout') ?></span>
            </a>
        </div>
    </div>
</aside>

<div id="main-content" class="relative min-h-screen pt-16 lg:ms-72">
    <div class="border-b border-gray-200 bg-white px-4 py-3 sm:px-6 dark:border-gray-700 dark:bg-gray-800">
        <?= view('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs ?? []]) ?>
    </div>
    <main id="main" class="px-4 py-6 sm:px-6">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl dark:text-white"><?= esc($title ?? '') ?></h1>
                <?php if ($subtitle !== null) : ?>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= esc($subtitle) ?></p>
                <?php endif ?>
            </div>
            <div class="flex flex-wrap items-center gap-2"><?= $this->renderSection('actions') ?></div>
        </div>
        <?= $this->renderSection('content') ?>
    </main>
    <footer class="flex flex-col gap-1 px-4 pb-6 text-sm text-gray-500 sm:flex-row sm:justify-between sm:px-6 dark:text-gray-400">
        <p>&copy; <?= date('Y') ?> <?= esc(setting('School.name')) ?></p>
        <p><?= lang('App.developedBy') ?></p>
    </footer>
</div>

<?= view('partials/bottom_nav', ['bottomNav' => ['label' => lang('App.bottomNavLabel'), 'items' => $bottom]]) ?>
<?php if ($canSearch) : ?>
    <dialog id="cari-cepat" class="lembar-cari" aria-labelledby="cari-cepat-judul">
        <form method="get" action="<?= site_url('admin/pendaftar') ?>" class="space-y-4 p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 id="cari-cepat-judul" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('App.quickSearchTitle') ?></h2>
                <button type="button" data-dialog-close class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                    <?= icon('x', 'h-5 w-5') ?><span class="sr-only"><?= lang('App.close') ?></span>
                </button>
            </div>
            <label for="cari-cepat-q" class="sr-only"><?= lang('App.quickSearchLabel') ?></label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500"><?= icon('search', 'h-5 w-5') ?></span>
                <input type="search" id="cari-cepat-q" name="q" required autocomplete="off" enterkeyhint="search" placeholder="<?= lang('App.quickSearchPlaceholder') ?>"
                       class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-3.5 ps-11 text-base text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <button type="submit" class="w-full rounded-xl bg-primary-600 px-5 py-3 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('App.search') ?></button>
        </form>
    </dialog>
<?php endif ?>

<?= view('partials/flash') ?>
<script src="<?= esc(asset_url('app.js'), 'attr') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
