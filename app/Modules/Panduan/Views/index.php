<?php
/**
 * @var array<string, array{title: string, description: string, group: string|null, icon: string}> $roles
 * @var list<string>                                                                                $mine
 */
$public = array_filter($roles, static fn (array $r): bool => $r['group'] === null);
$staff  = array_filter($roles, static fn (array $r): bool => $r['group'] !== null);
$card   = static fn (string $slug, array $role, bool $highlight): string => '<a href="' . esc(site_url('panduan/' . $slug), 'attr') . '" class="motion-lift group flex h-full items-start gap-4 rounded-lg border p-5 hover:shadow-md ' . ($highlight ? 'border-primary-500 bg-primary-50 dark:bg-gray-800' : 'border-gray-200 bg-white hover:border-primary-300 dark:border-gray-700 dark:bg-gray-800') . '">'
    . icon($role['icon'], 'motion-icon h-7 w-7 shrink-0 text-primary-600 dark:text-primary-400')
    . '<span class="min-w-0"><span class="block font-semibold text-gray-900 group-hover:text-primary-700 dark:text-white">' . esc($role['title']) . '</span>'
    . '<span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">' . esc($role['description']) . '</span>'
    . ($highlight ? '<span class="mt-2 inline-block rounded-md bg-primary-600 px-2 py-0.5 text-xs font-medium text-white">' . lang('Panduan.yourGuide') . '</span>' : '')
    . '</span></a>';
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:py-12">
    <div class="grid items-center gap-6 md:grid-cols-[1fr_minmax(0,20rem)]">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white"><?= lang('Panduan.title') ?></h1>
            <p class="mt-2 max-w-2xl text-gray-500 dark:text-gray-400"><?= lang('Panduan.intro') ?></p>
        </div>
        <div class="hidden overflow-hidden rounded-2xl bg-white md:block">
            <?= illustration('guide/panduan-calon-murid', lang('App.altGuideApplicant'), 'h-auto w-full', false) ?>
        </div>
    </div>

    <h2 class="mb-4 mt-10 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Panduan.forPublic') ?></h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($public as $slug => $role) : ?><?= $card($slug, $role, false) ?><?php endforeach ?>
    </div>

    <h2 class="mb-4 mt-10 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Panduan.forStaff') ?></h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($staff as $slug => $role) : ?><?= $card($slug, $role, in_array($slug, $mine, true)) ?><?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
