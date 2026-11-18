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
$card = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2   = 'mb-4 text-lg font-semibold text-gray-900 dark:text-white';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= url_to('pengaturan.sekolah') ?>" enctype="multipart/form-data" class="max-w-3xl space-y-6">
    <?= csrf_field() ?>

    <section class="<?= $card ?>">
        <h2 class="<?= $h2 ?>"><?= lang('Pengaturan.sectionIdentity') ?></h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><?= $f('name', ['required' => true, 'help' => lang('Pengaturan.nameHelp')]) ?></div>
            <?= $f('shortName', ['required' => true, 'help' => lang('Pengaturan.shortNameHelp')]) ?>
            <?= $f('tagline') ?>
            <div class="sm:col-span-2"><?= $f('address', ['type' => 'textarea']) ?></div>
        </div>
    </section>

    <section class="<?= $card ?>">
        <h2 class="<?= $h2 ?>"><?= lang('Pengaturan.sectionContact') ?></h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?= $f('phone', ['type' => 'tel']) ?>
            <?= $f('whatsapp', ['type' => 'tel', 'help' => lang('Pengaturan.whatsappHelp')]) ?>
            <?= $f('email', ['type' => 'email']) ?>
            <?= $f('website', ['type' => 'url', 'placeholder' => 'https://']) ?>
        </div>
    </section>

    <section class="<?= $card ?>">
        <h2 class="<?= $h2 ?>"><?= lang('Pengaturan.sectionLook') ?></h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?= $f('primaryColor', ['type' => 'color', 'required' => true, 'help' => lang('Pengaturan.primaryColorHelp')]) ?>
            <?= $f('timezoneLabel', ['type' => 'select', 'required' => true, 'options' => ['WIB' => 'WIB (Waktu Indonesia Barat)', 'WITA' => 'WITA (Waktu Indonesia Tengah)', 'WIT' => 'WIT (Waktu Indonesia Timur)']]) ?>
            <div class="sm:col-span-2"><?= $f('footerText') ?></div>
            <?= $f('illustrationAccent', ['type' => 'select', 'options' => lang('Pengaturan.illustrationAccentOptions'), 'help' => lang('Pengaturan.illustrationAccentHelp')]) ?>
        </div>
    </section>

    <section class="<?= $card ?>">
        <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pengaturan.sectionImages') ?></h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengaturan.sectionImagesIntro') ?></p>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <?php foreach (App\Libraries\BrandingAssets::ASSETS as $key => $asset) : ?>
                <?php $accept = implode(',', array_map(static fn (string $e): string => $e === 'ico' ? 'image/x-icon' : ($e === 'jpg' || $e === 'jpeg' ? 'image/jpeg' : 'image/' . $e), $asset['ext'])); ?>
                <div class="<?= in_array($key, ['logoWide', 'logoWideDark', 'shareImage'], true) ? 'sm:col-span-2' : '' ?>">
                    <?= $f($key, ['type' => 'file', 'help' => lang('Pengaturan.' . $key . 'Help'), 'attrs' => ['accept' => $accept]]) ?>
                    <?php if (($images[$key] ?? null) !== null) : ?>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <img src="<?= esc($images[$key], 'attr') ?>" alt="<?= esc(lang('Pengaturan.currentImage', [lang('Pengaturan.' . $key)]), 'attr') ?>"
                                 class="max-h-20 max-w-full rounded-lg border border-gray-200 object-contain p-1 <?= $key === 'logoWideDark' ? 'bg-gray-800' : 'bg-white' ?> dark:border-gray-600">
                            <?= view('partials/form_field', ['field' => ['name' => 'remove_' . $key, 'type' => 'checkbox', 'label' => lang('Pengaturan.removeImage'), 'value' => '0']]) ?>
                        </div>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
            <div class="sm:col-span-2"><?= $f('partnerName', ['help' => lang('Pengaturan.partnerNameHelp')]) ?></div>
        </div>
    </section>

    <div class="flex justify-end">
        <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= lang('Pengaturan.save') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
