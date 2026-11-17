<?php
/**
 * @var list<array{name: string, label: string, description: string, icon: string, core: bool, enabled: bool}> $modules
 */
$optional = array_values(array_filter($modules, static fn (array $m): bool => ! $m['core']));
$core     = array_values(array_filter($modules, static fn (array $m): bool => $m['core']));
$card     = static function (array $m): string {
    $id = 'fitur-' . strtolower($m['name']);
    ob_start(); ?>
    <li class="flex items-start gap-4 p-4">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg <?= $m['enabled'] ? 'bg-primary-50 text-primary-600 dark:bg-gray-700 dark:text-primary-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' ?>"><?= icon($m['icon'], 'h-5 w-5') ?></span>
        <div class="min-w-0 flex-1">
            <p class="font-medium text-gray-900 dark:text-white" id="<?= $id ?>-label"><?= esc($m['label']) ?></p>
            <?php if ($m['description'] !== '') : ?>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400" id="<?= $id ?>-desc"><?= esc($m['description']) ?></p>
            <?php endif ?>
        </div>
        <?php if ($m['core']) : ?>
            <?= view('partials/status_badge', ['badge' => ['label' => lang('Pengaturan.featureAlwaysOn'), 'color' => 'gray']]) ?>
        <?php else : ?>
            <label class="inline-flex shrink-0 cursor-pointer items-center gap-3">
                <span class="hidden text-sm font-medium text-gray-700 sm:inline dark:text-gray-300" data-toggle-text data-on="<?= esc(lang('Pengaturan.featureOn'), 'attr') ?>" data-off="<?= esc(lang('Pengaturan.featureOff'), 'attr') ?>"><?= $m['enabled'] ? lang('Pengaturan.featureOn') : lang('Pengaturan.featureOff') ?></span>
                <input type="checkbox" name="aktif[]" value="<?= esc($m['name'], 'attr') ?>" class="peer sr-only" <?= $m['enabled'] ? 'checked' : '' ?>
                       aria-labelledby="<?= $id ?>-label" <?= $m['description'] !== '' ? 'aria-describedby="' . $id . '-desc"' : '' ?>>
                <span class="relative h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-primary-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white rtl:peer-checked:after:-translate-x-full dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800"></span>
            </label>
        <?php endif ?>
    </li>
    <?php return (string) ob_get_clean();
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="max-w-3xl space-y-6">
    <form method="post" action="<?= url_to('pengaturan.fitur') ?>" data-confirm="<?= esc(lang('Pengaturan.featuresConfirm'), 'attr') ?>" class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <?= csrf_field() ?>
        <h2 class="border-b border-gray-200 px-4 py-3 text-base font-semibold text-gray-900 dark:border-gray-700 dark:text-white"><?= lang('Pengaturan.featuresOptional') ?></h2>
        <?php if ($optional === []) : ?>
            <div class="p-4">
                <?= view('partials/empty_state', ['empty' => ['title' => lang('Pengaturan.featuresNone'), 'text' => lang('Pengaturan.featuresNoneText'), 'icon' => 'grid', 'image' => 'state/fitur-tambahan', 'imageAlt' => lang('App.altFeatures')]]) ?>
            </div>
        <?php else : ?>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($optional as $m) : ?><?= $card($m) ?><?php endforeach ?>
            </ul>
            <div class="flex justify-end border-t border-gray-200 p-4 dark:border-gray-700">
                <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= lang('Pengaturan.featuresSave') ?></button>
            </div>
        <?php endif ?>
    </form>

    <section class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="fitur-dasar">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 id="fitur-dasar" class="text-base font-semibold text-gray-900 dark:text-white"><?= lang('Pengaturan.featuresCore') ?></h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengaturan.featuresCoreHelp') ?></p>
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            <?php foreach ($core as $m) : ?><?= $card($m) ?><?php endforeach ?>
        </ul>
    </section>
</div>
<?= $this->endSection() ?>
