<?php
/**
 * @var array<string, string> $values
 * @var string|null           $logoUrl
 * @var bool                  $hasFavicon
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
            <div>
                <?= $f('logo', ['type' => 'file', 'help' => lang('Pengaturan.logoHelp'), 'attrs' => ['accept' => 'image/png,image/jpeg,image/webp']]) ?>
                <?php if ($logoUrl !== null) : ?>
                    <div class="mt-3 flex items-center gap-3">
                        <img src="<?= esc($logoUrl, 'attr') ?>" alt="<?= lang('Pengaturan.currentLogo') ?>" class="h-14 w-14 rounded-lg border border-gray-200 bg-white object-contain p-1 dark:border-gray-600">
                        <?= view('partials/form_field', ['field' => ['name' => 'remove_logo', 'type' => 'checkbox', 'label' => lang('Pengaturan.removeLogo'), 'value' => '0']]) ?>
                    </div>
                <?php endif ?>
            </div>
            <div>
                <?= $f('favicon', ['type' => 'file', 'help' => lang('Pengaturan.faviconHelp'), 'attrs' => ['accept' => 'image/png,image/x-icon']]) ?>
                <?php if ($hasFavicon) : ?>
                    <div class="mt-3"><?= view('partials/form_field', ['field' => ['name' => 'remove_favicon', 'type' => 'checkbox', 'label' => lang('Pengaturan.removeFavicon'), 'value' => '0']]) ?></div>
                <?php endif ?>
            </div>
        </div>
    </section>

    <div class="flex justify-end">
        <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= lang('Pengaturan.save') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
