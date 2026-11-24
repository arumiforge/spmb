<?php
/**
 * Public home page: hero with the school's slogan and an automatic slideshow (D-084), the special
 * buttons with Codex art (D-082), "Cara mendaftar" with one painting per step, information cards
 * with 3D painted icons, and the location/contact block with the Google Maps embed (D-085).
 *
 * @var array{registration: bool, banner: array{state: string, text: string, wave: array<string, mixed>|null}|null, onlineOpen: bool, situs: bool} $home
 */
use App\Libraries\BrandingAssets;

$banner = $home['banner'];
$intro  = match (true) {
    ! $home['registration']                            => lang('Situs.homeClosedText'),
    $home['onlineOpen']                                => lang('Situs.homeOpenText'),
    $banner !== null && $banner['state'] === 'offline' => lang('Situs.homeOfflineText'),
    default                                            => lang('Situs.homeClosedText'),
};
$schoolName = (string) setting('School.name');
$motto      = (string) setting('School.motto');
$tagline    = (string) setting('School.tagline');
$address    = (string) setting('School.address');
$phone      = (string) setting('School.phone');
$whatsapp   = (string) setting('School.whatsapp');
$email      = (string) setting('School.email');
$mapUrl     = map_embed_url((string) setting('School.mapEmbed'));
// No embed pasted yet: a preview map of the address instead of only a directions link (D-091).
$mapUrl = $mapUrl !== '' || $address === '' ? $mapUrl : map_search_embed_url($schoolName . ', ' . str_replace(["\r", "\n"], ' ', $address));

// Slides: the school's own pictures replace the default Codex paintings (D-084).
$uploaded = BrandingAssets::heroSlides();
$slides   = [];
foreach ($uploaded as $i => $url) {
    $slides[] = '<img src="' . esc($url, 'attr') . '" alt="' . esc(lang('App.altUploadedSlide', [$schoolName]), 'attr') . '"'
        . ($i === 0 ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async" class="h-full w-full object-cover">';
}
if ($slides === []) {
    foreach (lang('App.altHeroSlides') as $path => $alt) {
        $picture = illustration($path, $alt, 'h-full w-full object-contain', $slides === []);
        if ($picture !== '') {
            $slides[] = $picture;
        }
    }
}

// Information cards: only pages whose module is on.
$cards = [];
foreach (lang('Situs.homeCards') as $slug => $card) {
    $enabled = match ($slug) {
        'persyaratan', 'jadwal' => $home['situs'],
        'cek-status'            => $home['registration'],
        'tanya-jawab'           => $home['situs'] && in_array('tanya-jawab', array_column(public_nav(), 'url'), true),
        default                 => true,
    };
    if ($enabled) {
        $cards[$slug] = $card;
    }
}
$cardIcons = ['persyaratan' => 'clipboard-list', 'jadwal' => 'calendar-days', 'cek-status' => 'search', 'tanya-jawab' => 'message-circle-question-mark', 'panduan' => 'book-open'];
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<section class="relative overflow-hidden bg-white dark:bg-gray-900" data-ikon-3d-src="<?= esc(asset_url('ikon3d.js'), 'attr') ?>">
    <div class="pola pola-kawung pola-pudar" aria-hidden="true"></div>
    <div class="relative mx-auto grid max-w-screen-xl items-center gap-4 px-4 pb-6 pt-4 sm:pt-12 md:gap-6 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] md:gap-0 md:pb-12 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
        <div class="motion-rise relative z-10 text-center md:pe-6 md:text-start">
            <?php if ($tagline !== '') : ?>
                <p class="mb-2 text-base font-semibold text-primary-700 sm:text-lg dark:text-primary-400"><?= esc($tagline) ?></p>
            <?php endif ?>
            <h1 class="mb-3 text-3xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-5xl dark:text-white"><?= esc($schoolName) ?></h1>
            <?php if ($motto !== '') : ?>
                <p class="mx-auto mb-4 max-w-xl text-base font-medium italic leading-relaxed text-gray-700 sm:text-lg md:mx-0 dark:text-gray-300"><?= esc($motto) ?></p>
            <?php endif ?>
            <p class="mx-auto mb-6 max-w-2xl text-base text-gray-500 sm:text-lg md:mx-0 dark:text-gray-400"><?= esc($intro) ?></p>

            <?php if ($banner !== null && $banner['wave'] !== null) : ?>
                <p class="mx-auto mb-6 inline-flex max-w-2xl items-start gap-2 rounded-lg bg-primary-50 px-4 py-3 text-start text-sm font-medium text-primary-800 md:mx-0 dark:bg-gray-800 dark:text-primary-300">
                    <?= icon($banner['state'] === 'upcoming' ? 'clock' : 'calendar', 'h-5 w-5 shrink-0') ?><span><?= esc($banner['text']) ?></span>
                </p>
            <?php endif ?>

            <div class="flex flex-col items-stretch gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-center md:justify-start">
                <?php if ($home['onlineOpen']) : ?>
                    <a href="<?= site_url('daftar') ?>" class="tombol-daftar tombol-daftar-besar tombol-daftar-penuh">
                        <span class="tombol-daftar-isi"><?= lang('Situs.register') ?><?= icon('arrow-right', 'h-5 w-5') ?></span>
                    </a>
                <?php endif ?>
                <?php if ($home['registration']) : ?>
                    <a href="<?= site_url('cek-status') ?>" class="tombol-seni" data-ikon-3d-area>
                        <span class="tombol-seni-gambar"><?= art_icon('ikon/ikon-cek-status', 'search', 'h-full w-full') ?></span>
                        <span class="tombol-seni-teks"><?= lang('Situs.checkStatus') ?></span>
                    </a>
                <?php endif ?>
                <?php if ($home['situs']) : ?>
                    <a href="<?= url_to('situs.persyaratan') ?>" class="tombol-seni tombol-seni-lembut" data-ikon-3d-area>
                        <span class="tombol-seni-gambar"><?= art_icon('ikon/ikon-persyaratan', 'clipboard-list', 'h-full w-full') ?></span>
                        <span class="tombol-seni-teks"><?= lang('Situs.seeRequirements') ?></span>
                    </a>
                <?php else : ?>
                    <a href="<?= site_url('panduan/calon-murid') ?>" class="tombol-seni tombol-seni-lembut" data-ikon-3d-area>
                        <span class="tombol-seni-gambar"><?= art_icon('ikon/ikon-panduan', 'book-open', 'h-full w-full') ?></span>
                        <span class="tombol-seni-teks"><?= lang('Situs.homeGuide') ?></span>
                    </a>
                <?php endif ?>
            </div>
        </div>
        <?php if ($slides !== []) : ?>
            <div class="motion-rise relative order-first mx-auto w-full max-w-md md:order-none md:-ms-16 md:max-w-none lg:-ms-24" style="--rise-delay: 120ms">
                <div class="slide-hero" data-slide-hero data-jeda="6000" <?= count($slides) > 1 ? 'aria-roledescription="carousel"' : '' ?>>
                    <?php foreach ($slides as $i => $slide) : ?>
                        <div class="slide-hero-item<?= $i === 0 ? ' is-tampil' : '' ?>" <?= $i === 0 ? '' : 'aria-hidden="true"' ?>><?= $slide ?></div>
                    <?php endforeach ?>
                </div>
            </div>
        <?php endif ?>
    </div>
    <div class="pemisah pemisah-lengkung relative [--pemisah-warna:var(--color-gray-50)] dark:[--pemisah-warna:var(--color-gray-800)]" aria-hidden="true"></div>
</section>

<?php if ($home['registration']) : ?>
    <?php $stepsArt = illustration('section/beranda-langkah', lang('App.altSections')['section/beranda-langkah'], 'mx-auto h-40 w-auto sm:h-48 md:h-56'); ?>
    <section class="bg-gray-50 dark:bg-gray-800" aria-labelledby="cara-daftar">
        <div class="mx-auto max-w-screen-xl px-4 py-10 sm:py-14">
            <div class="mb-8 flex flex-col items-center gap-4 md:flex-row md:justify-between md:gap-10">
                <div class="text-center md:text-start">
                    <h2 id="cara-daftar" class="mb-2 text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= lang('Situs.homeStepsTitle') ?></h2>
                    <p class="max-w-xl text-gray-600 dark:text-gray-400"><?= lang('Situs.homeStepsIntro') ?></p>
                </div>
                <?php if ($stepsArt !== '') : ?><div class="order-first shrink-0 md:order-none"><?= $stepsArt ?></div><?php endif ?>
            </div>
            <ol class="grid gap-5 md:grid-cols-3">
                <?php foreach (['homeStep1', 'homeStep2', 'homeStep3'] as $i => $key) : ?>
                    <?php
                    // Step scene (briefs 046–048, D-090); a painted icon or Lucide icon until it arrives.
                    $alt   = lang('Situs.homeStepAlts')[$i];
                    $scene = illustration('langkah/langkah-' . ($i + 1), $alt, 'mx-auto aspect-[4/3] w-full max-w-xs object-contain');
                    ?>
                    <li class="kartu-langkah kartu-langkah-<?= $i + 1 ?> motion-rise flex flex-col items-center rounded-2xl border border-gray-200 bg-white p-5 text-center shadow-sm sm:p-6 dark:border-gray-700 dark:bg-gray-900" style="--rise-delay: <?= $i * 80 ?>ms" data-ikon-3d-area>
                        <?php if ($scene !== '') : ?>
                            <div class="mb-4 w-full"><?= $scene ?></div>
                        <?php else : ?>
                            <?= art_icon('langkah/langkah-' . ($i + 1), ['clipboard-list', 'pencil', 'id-card'][$i], 'mb-4 mt-2 h-28 w-28 sm:h-32 sm:w-32', $alt) ?>
                        <?php endif ?>
                        <p class="mb-1 text-sm font-semibold text-primary-700 dark:text-primary-400"><?= lang('Situs.step', [$i + 1]) ?></p>
                        <h3 class="mb-2 text-lg font-bold text-gray-900 dark:text-white"><?= esc(lang('Situs.homeStepTitles')[$i]) ?></h3>
                        <p class="text-gray-600 dark:text-gray-300"><?= lang('Situs.' . $key) ?></p>
                    </li>
                <?php endforeach ?>
            </ol>
        </div>
    </section>
<?php endif ?>

<?php if ($cards !== []) : ?>
    <section class="bg-white dark:bg-gray-900" aria-labelledby="info-daftar">
        <div class="mx-auto max-w-screen-xl px-4 py-10 sm:py-14">
            <h2 id="info-daftar" class="mb-2 text-center text-2xl font-bold text-gray-900 sm:text-3xl md:text-start dark:text-white"><?= lang('Situs.homeInfoTitle') ?></h2>
            <p class="mb-8 text-center text-gray-600 md:text-start dark:text-gray-400"><?= lang('Situs.homeInfoIntro') ?></p>
            <ul class="grid grid-cols-2 gap-4 sm:gap-5 lg:grid-cols-<?= min(count($cards), 5) ?>">
                <?php foreach ($cards as $slug => $card) : ?>
                    <li class="motion-rise" style="--rise-delay: <?= array_search($slug, array_keys($cards), true) * 60 ?>ms">
                        <a href="<?= esc(site_url($slug === 'panduan' ? 'panduan/calon-murid' : $slug), 'attr') ?>" data-ikon-3d-area
                           class="motion-lift group flex h-full flex-col items-center rounded-2xl border border-gray-200 bg-white p-4 text-center hover:border-primary-300 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-300 sm:p-6 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-700">
                            <?= art_icon('ikon/ikon-' . $slug, $cardIcons[$slug] ?? 'info', 'mb-3 h-20 w-20 sm:h-24 sm:w-24') ?>
                            <span class="mb-1 text-base font-bold text-gray-900 sm:text-lg dark:text-white"><?= esc($card['title']) ?></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400"><?= esc($card['text']) ?></span>
                            <span class="mt-auto inline-flex items-center gap-1 pt-3 text-sm font-semibold text-primary-700 dark:text-primary-400"><?= lang('Situs.homeCardOpen') ?><?= icon('arrow-right', 'h-4 w-4 transition-transform group-hover:translate-x-0.5') ?></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </section>
<?php endif ?>

<?php if ($mapUrl !== '' || $address !== '' || $phone !== '' || $whatsapp !== '' || $email !== '') : ?>
    <?php
    // Contacts on the left, the map on the right from lg; on phones the map follows the contacts at full width (D-096).
    $directions = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim($schoolName . ' ' . str_replace(["\r", "\n"], ' ', $address)));
    $contacts   = array_values(array_filter([
        $address !== '' ? ['art' => 'ikon/ikon-alamat', 'icon' => 'map-pin', 'label' => lang('Situs.homeAddress'), 'html' => nl2br(esc($address)), 'href' => null] : null,
        $phone !== '' ? ['art' => 'ikon/ikon-telepon', 'icon' => 'phone', 'label' => lang('Situs.homePhone'), 'html' => esc($phone), 'href' => 'tel:' . preg_replace('/[^0-9+]/', '', $phone)] : null,
        $whatsapp !== '' ? ['art' => 'ikon/ikon-pesan', 'brand' => 'whatsapp', 'label' => lang('Situs.homeWhatsapp'), 'html' => esc($whatsapp), 'href' => whatsapp_url($whatsapp), 'external' => true] : null,
        $email !== '' ? ['art' => 'ikon/ikon-email', 'icon' => 'mail', 'label' => lang('Situs.homeEmail'), 'html' => esc($email), 'href' => 'mailto:' . $email, 'breakAll' => true] : null,
    ]));
    ?>
    <section class="bg-gray-50 dark:bg-gray-800" aria-labelledby="lokasi-kontak">
        <div class="mx-auto grid max-w-screen-xl gap-6 px-4 py-10 sm:py-14 lg:gap-10 <?= $mapUrl !== '' ? 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)] lg:items-start' : '' ?>">
            <div class="<?= $mapUrl === '' ? 'max-w-2xl' : '' ?>">
                <h2 id="lokasi-kontak" class="mb-2 text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= lang('Situs.homeContactTitle') ?></h2>
                <p class="mb-6 text-gray-600 dark:text-gray-400"><?= lang('Situs.homeContactIntro') ?></p>
                <?php if ($contacts !== []) : ?>
                    <ul class="kontak-daftar divide-y divide-gray-200 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-900">
                        <?php foreach ($contacts as $contact) : ?>
                            <li class="flex items-start gap-4 px-4 py-4 sm:px-5" data-ikon-3d-area>
                                <?= art_icon($contact['art'], $contact['brand'] ?? $contact['icon'], 'kontak-ikon h-11 w-11 shrink-0') ?>
                                <div class="min-w-0 flex-1 pt-0.5">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><?= $contact['label'] ?></p>
                                    <?php if ($contact['href'] === null) : ?>
                                        <p class="font-medium leading-relaxed text-gray-900 dark:text-white"><?= $contact['html'] ?></p>
                                    <?php else : ?>
                                        <a class="font-medium text-gray-900 hover:text-primary-700 hover:underline dark:text-white dark:hover:text-primary-400 <?= ! empty($contact['breakAll']) ? 'break-all' : '' ?>" href="<?= esc($contact['href'], 'attr') ?>"<?= ! empty($contact['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $contact['html'] ?></a>
                                    <?php endif ?>
                                </div>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
                <?php if ($mapUrl === '' && $address !== '') : ?>
                    <a class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary-700 hover:underline dark:text-primary-400" target="_blank" rel="noopener" href="<?= esc($directions, 'attr') ?>"><?= icon('map', 'h-4 w-4') ?><?= lang('Situs.homeDirections') ?></a>
                <?php endif ?>
            </div>
            <?php if ($mapUrl !== '') : ?>
                <figure class="peta-kartu overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <iframe src="<?= esc($mapUrl, 'attr') ?>" title="<?= esc(lang('App.mapTitle', [$schoolName]), 'attr') ?>" class="peta-sekolah" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    <figcaption class="flex flex-col gap-3 border-t border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                        <span class="flex min-w-0 items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-700 dark:bg-gray-800 dark:text-primary-200" aria-hidden="true"><?= icon('map', 'h-5 w-5') ?></span>
                            <span class="min-w-0"><?= esc(lang('App.mapTitle', [$schoolName])) ?></span>
                        </span>
                        <a class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800" target="_blank" rel="noopener" href="<?= esc($directions, 'attr') ?>"><?= lang('Situs.homeDirections') ?><?= icon('external-link', 'h-4 w-4') ?></a>
                    </figcaption>
                </figure>
            <?php endif ?>
        </div>
    </section>
<?php endif ?>
<?= $this->endSection() ?>
