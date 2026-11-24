<?php
/**
 * Guides this staff member may read (D-097), plus the public guide for applicants and parents.
 *
 * @var array<string, array{title: string, description: string, group: string|null, icon: string}> $roles
 * @var array<string, array{title: string, description: string, group: string|null, icon: string}> $public
 */
$card = static fn (string $url, array $role): string => '<a href="' . esc(site_url($url), 'attr') . '" class="motion-lift group flex h-full items-start gap-4 rounded-lg border border-gray-200 bg-white p-5 hover:border-primary-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">'
    . icon($role['icon'], 'motion-icon h-7 w-7 shrink-0 text-primary-600 dark:text-primary-400')
    . '<span class="min-w-0"><span class="block font-semibold text-gray-900 group-hover:text-primary-700 dark:text-white">Panduan ' . esc($role['title']) . '</span>'
    . '<span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">' . esc($role['description']) . '</span></span></a>';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($roles as $slug => $role) : ?><?= $card('admin/panduan/' . $slug, $role) ?><?php endforeach ?>
</div>

<?php if ($public !== []) : ?>
    <h2 class="mb-1 mt-10 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Panduan.forPublic') ?></h2>
    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Panduan.forApplicants') ?></p>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($public as $slug => $role) : ?><?= $card('panduan/' . $slug, $role) ?><?php endforeach ?>
    </div>
<?php endif ?>
<?= $this->endSection() ?>
