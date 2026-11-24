<?php
/**
 * News ticker under the public header ("Berita singkat"), on every public page.
 * Renders nothing while the feature is off or no item shows today.
 *
 * One item is visible at a time; resources/js/berita.js rotates them every
 * Situs.newsInterval seconds, pauses on hover and focus, and has a pause/play
 * button. With reduced motion it does not rotate by itself (previous/next
 * buttons only). Without JavaScript every item is listed. On phones the label is
 * only the icon, the previous button is hidden and an item is cut after three lines.
 */
use Modules\Situs\Libraries\SiteLink;
use Modules\Situs\Models\SiteNewsModel;

if (! (bool) setting('Situs.newsEnabled')) {
    return;
}

$items = model(SiteNewsModel::class)->visible();

if ($items === []) {
    return;
}

$count    = count($items);
$interval = max(3, min(30, (int) setting('Situs.newsInterval'))) * 1000;
$btn      = 'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-primary-800 hover:bg-primary-100 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-primary-200 dark:hover:bg-gray-700';
?>
<section class="berita-singkat border-b border-primary-100 bg-primary-50 text-primary-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" aria-label="<?= esc(lang('Situs.newsRegion'), 'attr') ?>"
         data-berita data-berita-jeda="<?= $interval ?>" data-no-print>
    <div class="mx-auto flex max-w-screen-xl items-center gap-2 px-4 py-1.5 text-sm sm:gap-3">
        <p class="inline-flex shrink-0 items-center gap-1.5 font-semibold text-primary-800 dark:text-primary-300">
            <?= icon('megaphone', 'h-4 w-4') ?><span class="sr-only sm:not-sr-only"><?= lang('Situs.newsLabel') ?></span>
        </p>
        <ul class="berita-daftar min-w-0 flex-1" data-berita-daftar aria-live="off">
            <?php foreach ($items as $i => $item) : ?>
                <?php $url = (string) $item['url']; ?>
                <li class="berita-item<?= $i === 0 ? ' is-tampil' : '' ?>" data-berita-item>
                    <?php if ($url !== '') : ?>
                        <?php $external = SiteLink::isExternal($url); ?>
                        <a href="<?= esc(SiteLink::href($url), 'attr') ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?> class="inline-flex max-w-full items-center gap-1 underline decoration-primary-300 underline-offset-2 hover:decoration-current focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-sm dark:decoration-gray-500">
                            <span class="berita-teks line-clamp-3 sm:line-clamp-none"><bdi><?= esc(localized($item, 'text')) ?></bdi></span>
                            <?= icon($external ? 'external-link' : 'chevron-right', 'h-3.5 w-3.5 shrink-0') ?>
                            <?php if ($external) : ?><span class="sr-only"><?= lang('Situs.opensNewTab') ?></span><?php endif ?>
                        </a>
                    <?php else : ?>
                        <span class="berita-teks line-clamp-3 sm:line-clamp-none"><bdi><?= esc(localized($item, 'text')) ?></bdi></span>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ul>
        <?php if ($count > 1) : ?>
            <div class="berita-kontrol hidden shrink-0 items-center" data-berita-kontrol>
                <button type="button" class="<?= $btn ?> max-sm:hidden" data-berita-sebelum><?= icon('chevron-left', 'h-4 w-4') ?><span class="sr-only"><?= lang('Situs.newsPrev') ?></span></button>
                <button type="button" class="<?= $btn ?>" data-berita-jeda-tombol aria-pressed="false"
                        data-label-jeda="<?= esc(lang('Situs.newsPause'), 'attr') ?>" data-label-putar="<?= esc(lang('Situs.newsPlay'), 'attr') ?>">
                    <span data-ikon-jeda><?= icon('pause', 'h-4 w-4') ?></span><span data-ikon-putar hidden><?= icon('play', 'h-4 w-4') ?></span><span class="sr-only" data-berita-jeda-label><?= lang('Situs.newsPause') ?></span>
                </button>
                <button type="button" class="<?= $btn ?>" data-berita-berikut><?= icon('chevron-right', 'h-4 w-4') ?><span class="sr-only"><?= lang('Situs.newsNext') ?></span></button>
            </div>
        <?php endif ?>
    </div>
</section>
