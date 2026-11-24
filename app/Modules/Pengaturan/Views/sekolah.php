<?php
/**
 * @var array<string, string> $values
 * @var array<string, string|null> $images Brand image URLs by key (App\Libraries\BrandingAssets)
 */
$f = static fn (string $name, array $extra = []): string => view('partials/form_field', ['field' => $extra + [
    'name'  => $name,
    'label' => lang('Pengaturan.' . $name),
    'value' => $values[$name] ?? '',
]]);
$h2   = 'mb-4 text-lg font-semibold text-gray-900 dark:text-white';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= url_to('pengaturan.sekolah') ?>" enctype="multipart/form-data" class="grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
    <?= csrf_field() ?>
    <?= view('partials/lipatan_semua', ['class' => 'xl:col-span-2'], ['saveData' => false]) ?>

    <?= lipatan_buka(['id' => 'sekolah-identity', 'icon' => 'school', 'title' => lang('Pengaturan.sectionIdentity')]) ?>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><?= $f('name', ['required' => true, 'help' => lang('Pengaturan.nameHelp')]) ?></div>
            <?= $f('shortName', ['required' => true, 'help' => lang('Pengaturan.shortNameHelp')]) ?>
            <?= $f('tagline') ?>
            <div class="sm:col-span-2"><?= $f('motto', ['help' => lang('Pengaturan.mottoHelp')]) ?></div>
            <div class="sm:col-span-2"><?= $f('address', ['type' => 'textarea']) ?></div>
        </div>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'sekolah-contact', 'icon' => 'phone', 'title' => lang('Pengaturan.sectionContact')]) ?>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?= $f('phone', ['type' => 'tel']) ?>
            <?= $f('whatsapp', ['type' => 'tel', 'help' => lang('Pengaturan.whatsappHelp')]) ?>
            <?= $f('email', ['type' => 'email']) ?>
            <?= $f('website', ['type' => 'url', 'placeholder' => 'https://']) ?>
            <div class="sm:col-span-2"><?= $f('mapEmbed', ['type' => 'textarea', 'help' => lang('Pengaturan.mapEmbedHelp'), 'placeholder' => '<iframe src="https://www.google.com/maps/embed?pb=…"></iframe>']) ?></div>
            <?php if (($mapUrl = map_embed_url($values['mapEmbed'] ?? '')) !== '') : ?>
                <iframe src="<?= esc($mapUrl, 'attr') ?>" title="<?= esc(lang('App.mapTitle', [setting('School.name')]), 'attr') ?>" class="peta-sekolah sm:col-span-2" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            <?php endif ?>
        </div>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'sekolah-social', 'icon' => 'sparkles', 'title' => lang('Pengaturan.sectionSocial'), 'intro' => lang('Pengaturan.sectionSocialIntro')]) ?>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?php foreach (['socialInstagram' => 'instagram.com/…', 'socialFacebook' => 'facebook.com/…', 'socialYoutube' => 'youtube.com/@…', 'socialTiktok' => 'tiktok.com/@…'] as $social => $hint) : ?>
                <?= $f($social, ['type' => 'url', 'placeholder' => 'https://' . $hint]) ?>
            <?php endforeach ?>
        </div>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'sekolah-look', 'icon' => 'sun', 'title' => lang('Pengaturan.sectionLook')]) ?>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?= $f('primaryColor', ['type' => 'color', 'required' => true, 'help' => lang('Pengaturan.primaryColorHelp')]) ?>
            <?= $f('timezoneLabel', ['type' => 'select', 'required' => true, 'options' => ['WIB' => 'WIB (Waktu Indonesia Barat)', 'WITA' => 'WITA (Waktu Indonesia Tengah)', 'WIT' => 'WIT (Waktu Indonesia Timur)']]) ?>
            <div class="sm:col-span-2"><?= $f('footerText') ?></div>
            <?= $f('illustrationAccent', ['type' => 'select', 'options' => lang('Pengaturan.illustrationAccentOptions'), 'help' => lang('Pengaturan.illustrationAccentHelp')]) ?>
        </div>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'sekolah-images', 'icon' => 'camera', 'title' => lang('Pengaturan.sectionImages'), 'intro' => lang('Pengaturan.sectionImagesIntro')]) ?>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <?php foreach (array_diff_key(App\Libraries\BrandingAssets::ASSETS, array_flip(App\Libraries\BrandingAssets::HERO_SLIDES)) as $key => $asset) : ?>
                <?php $accept = implode(',', array_map(static fn (string $e): string => $e === 'ico' ? 'image/x-icon' : ($e === 'jpg' || $e === 'jpeg' ? 'image/jpeg' : 'image/' . $e), $asset['ext'])); ?>
                <div class="<?= in_array($key, ['logoWide', 'logoWideDark', 'shareImage'], true) ? 'sm:col-span-2' : '' ?>">
                    <?= $f($key, ['type' => 'file', 'help' => lang('Pengaturan.' . $key . 'Help'), 'attrs' => ['accept' => $accept]]) ?>
                    <?php if (($images[$key] ?? null) !== null) : ?>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <img src="<?= esc($images[$key], 'attr') ?>" alt="<?= esc(lang('Pengaturan.currentImage', [lang('Pengaturan.' . $key)]), 'attr') ?>"
                                 class="max-h-20 max-w-full object-contain">
                            <?= view('partials/form_field', ['field' => ['name' => 'remove_' . $key, 'type' => 'checkbox', 'label' => lang('Pengaturan.removeImage'), 'value' => '0']]) ?>
                        </div>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
            <div class="sm:col-span-2"><?= $f('partnerName', ['help' => lang('Pengaturan.partnerNameHelp')]) ?></div>
        </div>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'sekolah-slides', 'icon' => 'layout-grid', 'title' => lang('Pengaturan.sectionSlides'), 'intro' => lang('Pengaturan.sectionSlidesIntro'), 'class' => 'xl:col-span-2']) ?>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach (App\Libraries\BrandingAssets::HERO_SLIDES as $n => $key) : ?>
                <div>
                    <?= $f($key, ['type' => 'file', 'label' => lang('Pengaturan.heroSlide', [$n + 1]), 'help' => $n === 0 ? lang('Pengaturan.heroSlideHelp') : null, 'attrs' => ['accept' => 'image/png,image/jpeg,image/webp']]) ?>
                    <?php if (($images[$key] ?? null) !== null) : ?>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <img src="<?= esc($images[$key], 'attr') ?>" alt="<?= esc(lang('Pengaturan.currentImage', [lang('Pengaturan.heroSlide', [$n + 1])]), 'attr') ?>" class="h-24 w-auto max-w-full rounded-lg object-cover">
                            <?= view('partials/form_field', ['field' => ['name' => 'remove_' . $key, 'type' => 'checkbox', 'label' => lang('Pengaturan.removeImage'), 'value' => '0']]) ?>
                        </div>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    <?= lipatan_tutup() ?>

    <div class="flex justify-end xl:col-span-2">
        <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= lang('Pengaturan.save') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
