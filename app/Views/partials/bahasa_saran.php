<?php
/**
 * Offer to switch back to the language the visitor chose before (D-109). Shown on an Indonesian page
 * when the language cookie says English or Arabic and the visitor arrived from outside the website
 * (bookmark, typed address, search engine). Never redirects by itself: search engines and shared links
 * always get the language of the address. Either link moves inside the website, so the cookie then
 * follows the choice (App\Filters\WebsiteLanguage).
 */
use App\Filters\WebsiteLanguage;
use App\Libraries\Locales;
use CodeIgniter\HTTP\IncomingRequest;

$request = service('request');

if (! $request instanceof IncomingRequest || locale_current() !== Locales::DEFAULT || $request->getMethod() !== 'GET') {
    return;
}

$remembered = (string) $request->getCookie(Locales::COOKIE);

if ($remembered === Locales::DEFAULT || ! in_array($remembered, Locales::ALL, true) || ! Locales::isEnabled($remembered) || WebsiteLanguage::fromThisSite($request)) {
    return;
}
?>
<div class="border-b border-primary-100 bg-white dark:border-gray-700 dark:bg-gray-900" data-no-print data-bahasa-saran>
    <div class="mx-auto flex max-w-screen-xl flex-col gap-2 px-4 py-2 text-sm sm:flex-row sm:items-center sm:gap-4" lang="<?= $remembered ?>" dir="<?= Locales::direction($remembered) ?>">
        <p class="flex items-center gap-2 text-gray-700 dark:text-gray-200"><?= icon('globe', 'h-4 w-4 shrink-0 text-primary-700 dark:text-primary-400') ?><?= esc(lang('App.languageSuggest', [], $remembered)) ?></p>
        <div class="flex flex-wrap items-center gap-2 sm:ms-auto">
            <a href="<?= esc(locale_url($remembered), 'attr') ?>" hreflang="<?= $remembered ?>" class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-primary-600 px-3 font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                <?= esc(lang('App.languageSuggestGo', [], $remembered)) ?><?= icon('arrow-right', 'h-4 w-4') ?>
            </a>
            <a href="<?= esc(locale_url(Locales::DEFAULT), 'attr') ?>" lang="id" dir="ltr" class="inline-flex min-h-9 items-center rounded-lg px-3 font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:text-gray-300 dark:hover:bg-gray-800"><?= lang('App.languageSuggestStay') ?></a>
        </div>
    </div>
</div>
