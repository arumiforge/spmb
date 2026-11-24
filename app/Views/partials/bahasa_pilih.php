<?php
/**
 * Language switcher of the public website (D-109): "ID · EN · العربية", each link opens the same
 * page in that language (the switcher links are the same addresses as the hreflang links).
 * Hidden when the school has switched English and Arabic off.
 *
 * @var array{variant: string} $switcher variant: bar (top bar, white on the brand colour) | drawer | footer
 */
use App\Libraries\Locales;

$enabled = Locales::enabled();

if (count($enabled) < 2) {
    return;
}

$current = locale_current();
$styles  = [
    'bar'    => ['list' => 'flex items-center gap-0.5', 'link' => 'rounded-full px-2 py-1 text-white/85 hover:text-white hover:underline focus:outline-none focus:ring-2 focus:ring-white/60', 'on' => 'rounded-full bg-white/15 px-2 py-1 font-semibold text-white', 'dot' => 'text-white/50'],
    'drawer' => ['list' => 'flex flex-wrap items-center gap-1', 'link' => 'inline-flex min-h-11 items-center rounded-lg px-3 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-200 dark:hover:bg-gray-700', 'on' => 'inline-flex min-h-11 items-center rounded-lg bg-primary-50 px-3 font-semibold text-primary-700 dark:bg-gray-700 dark:text-white', 'dot' => 'text-gray-400'],
    'footer' => ['list' => 'flex flex-wrap items-center gap-1', 'link' => 'inline-flex min-h-9 items-center rounded-lg px-2 text-gray-600 hover:text-primary-700 hover:underline focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-400 dark:hover:text-white', 'on' => 'inline-flex min-h-9 items-center rounded-lg px-2 font-semibold text-gray-900 dark:text-white', 'dot' => 'text-gray-400'],
];
$style = $styles[$switcher['variant'] ?? 'footer'] ?? $styles['footer'];
?>
<nav aria-label="<?= esc(lang('App.languageLabel'), 'attr') ?>" class="bahasa-pilih" data-bahasa-pilih>
    <ul class="<?= $style['list'] ?>">
        <?php if (($switcher['variant'] ?? '') !== 'bar') : ?>
            <li class="me-1"><?= icon('globe', 'h-4 w-4 ' . ($switcher['variant'] === 'drawer' ? 'h-5 w-5 text-gray-500 dark:text-gray-400' : 'text-gray-500 dark:text-gray-400')) ?></li>
        <?php endif ?>
        <?php foreach ($enabled as $i => $locale) : ?>
            <?php if ($i > 0) : ?><li class="<?= $style['dot'] ?>" aria-hidden="true">·</li><?php endif ?>
            <li>
                <?php if ($locale === $current) : ?>
                    <span class="<?= $style['on'] ?>" aria-current="true" lang="<?= $locale ?>"<?= Locales::isRtl($locale) ? ' dir="rtl"' : '' ?>><span class="sr-only"><?= esc(Locales::NAMES[$locale]) ?></span><span aria-hidden="true"><?= esc(Locales::LABELS[$locale]) ?></span></span>
                <?php else : ?>
                    <a href="<?= esc(locale_url($locale), 'attr') ?>" hreflang="<?= $locale ?>" lang="<?= $locale ?>"<?= Locales::isRtl($locale) ? ' dir="rtl"' : '' ?> class="<?= $style['link'] ?>" data-bahasa="<?= $locale ?>"><span class="sr-only"><?= esc(Locales::NAMES[$locale]) ?></span><span aria-hidden="true"><?= esc(Locales::LABELS[$locale]) ?></span></a>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>
</nav>
