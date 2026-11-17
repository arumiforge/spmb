<?php
/**
 * @var array{title: string, description: string}  $role
 * @var string                                     $slug
 * @var array<string, array{title: string}>        $roles
 * @var string                                     $html  Rendered from trusted Markdown files in the repository
 * @var list<array{id: string, label: string}>     $toc
 */
$whatsapp = (string) setting('School.whatsapp');
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto grid max-w-screen-xl grid-cols-1 gap-8 px-4 py-8 lg:grid-cols-[16rem_1fr]">
    <aside class="lg:sticky lg:top-24 lg:self-start">
        <?php if ($toc !== []) : ?>
            <nav aria-label="<?= lang('Panduan.contents') ?>" class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="mb-2 text-sm font-semibold text-gray-900 dark:text-white"><?= lang('Panduan.contents') ?></p>
                <ol class="space-y-1 text-sm">
                    <?php foreach ($toc as $item) : ?>
                        <li><a href="#<?= esc($item['id'], 'attr') ?>" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-primary-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"><?= esc($item['label']) ?></a></li>
                    <?php endforeach ?>
                </ol>
            </nav>
        <?php endif ?>
        <nav aria-label="<?= lang('Panduan.otherGuides') ?>" class="mt-4 hidden text-sm lg:block">
            <p class="mb-2 font-semibold text-gray-900 dark:text-white"><?= lang('Panduan.otherGuides') ?></p>
            <ul class="space-y-1">
                <?php foreach ($roles as $otherSlug => $other) : ?>
                    <?php if ($otherSlug !== $slug) : ?>
                        <li><a href="<?= esc(site_url('panduan/' . $otherSlug), 'attr') ?>" class="block px-2 py-1 text-gray-600 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white"><?= esc($other['title']) ?></a></li>
                    <?php endif ?>
                <?php endforeach ?>
            </ul>
        </nav>
    </aside>

    <article class="guide min-w-0 max-w-3xl">
        <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">Panduan <?= esc($role['title']) ?></h1>
        <p class="mt-2 text-lg text-gray-500 dark:text-gray-400"><?= esc($role['description']) ?></p>
        <?php if (trim($html) === '') : ?>
            <p class="mt-8"><?= lang('Panduan.empty') ?></p>
        <?php else : ?>
            <div class="mt-8"><?= $html ?></div>
        <?php endif ?>
        <?php if ($whatsapp !== '') : ?>
            <p class="mt-10 rounded-lg bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= lang('Panduan.help', [esc($whatsapp)]) ?></p>
        <?php endif ?>
    </article>
</div>
<?= $this->endSection() ?>
