<?php
/**
 * Public guide list. Only shown when there is more than one public guide; with one, /panduan
 * opens it straight away. Staff guides are not listed here (D-097).
 *
 * @var array<string, array{title: string, description: string, group: string|null, icon: string}> $roles
 */
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:py-12">
    <div class="grid items-center gap-6 md:grid-cols-[1fr_minmax(0,20rem)]">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white"><?= lang('Panduan.title') ?></h1>
            <p class="mt-2 max-w-2xl text-gray-500 dark:text-gray-400"><?= lang('Panduan.intro') ?></p>
        </div>
        <div class="hidden md:block">
            <?= illustration('guide/panduan-calon-murid', lang('App.altGuideApplicant'), 'h-auto w-full', false) ?>
        </div>
    </div>

    <div class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($roles as $slug => $role) : ?>
            <a href="<?= esc(site_url('panduan/' . $slug), 'attr') ?>" class="motion-lift group flex h-full items-start gap-4 rounded-lg border border-gray-200 bg-white p-5 hover:border-primary-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <?= icon($role['icon'], 'motion-icon h-7 w-7 shrink-0 text-primary-600 dark:text-primary-400') ?>
                <span class="min-w-0">
                    <span class="block font-semibold text-gray-900 group-hover:text-primary-700 dark:text-white"><?= esc($role['title']) ?></span>
                    <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400"><?= esc($role['description']) ?></span>
                </span>
            </a>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
