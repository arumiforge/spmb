<?php
/**
 * Guide body shared by the public guide page and the dashboard guide page (D-097):
 * contents, other guides, in-page search, then the guide itself.
 *
 * @var array{title: string, description: string} $role
 * @var string                                     $slug
 * @var array<string, string>                      $others  slug => title of the other guides this reader may open
 * @var string                                     $base    URL prefix of those guides ('panduan/' or 'admin/panduan/')
 * @var string                                     $html    Rendered from trusted Markdown files in the repository
 * @var list<array{id: string, label: string}>     $toc
 * @var bool                                       $heading Whether to print the title (the dashboard layout has its own)
 */
$whatsapp = (string) setting('School.whatsapp');
?>
<div class="grid grid-cols-1 gap-8 lg:grid-cols-[16rem_1fr]">
    <aside class="lg:sticky lg:top-24 lg:self-start">
        <?php if ($toc !== []) : ?>
            <nav aria-label="<?= lang('Panduan.contents') ?>" class="daftar-isi rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900" data-daftar-isi>
                <p class="mb-1 px-3 pt-1 text-sm font-semibold text-gray-900 dark:text-white"><?= lang('Panduan.contents') ?></p>
                <div class="lipat" data-lipat data-lipat-tinggi="11rem" data-lipat-media="(width < 64rem)">
                    <ol class="lipat-isi space-y-0.5 text-sm lg:max-h-[calc(100vh-10rem)] lg:overflow-y-auto">
                        <?php foreach ($toc as $item) : ?>
                            <li><a href="#<?= esc($item['id'], 'attr') ?>"><?= esc($item['label']) ?></a></li>
                        <?php endforeach ?>
                    </ol>
                </div>
            </nav>
        <?php endif ?>
        <?php if ($others !== []) : ?>
            <nav aria-label="<?= lang('Panduan.otherGuides') ?>" class="mt-4 hidden text-sm lg:block">
                <p class="mb-2 font-semibold text-gray-900 dark:text-white"><?= lang('Panduan.otherGuides') ?></p>
                <ul class="space-y-1">
                    <?php foreach ($others as $otherSlug => $otherTitle) : ?>
                        <li><a href="<?= esc(site_url($base . $otherSlug), 'attr') ?>" class="block px-2 py-1 text-gray-600 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white"><?= esc($otherTitle) ?></a></li>
                    <?php endforeach ?>
                </ul>
            </nav>
        <?php endif ?>
    </aside>

    <article class="guide min-w-0 max-w-3xl">
        <?php if ($heading) : ?>
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">Panduan <?= esc($role['title']) ?></h1>
            <p class="mt-2 text-lg text-gray-500 dark:text-gray-400"><?= esc($role['description']) ?></p>
        <?php endif ?>
        <div class="<?= $heading ? 'mt-6' : '' ?>">
            <?= $slug === 'calon-murid'
                ? illustration('guide/panduan-calon-murid', lang('App.altGuideApplicant'), 'mx-auto h-48 w-auto sm:h-64', false)
                : illustration('guide/panduan-petugas', lang('App.altGuideStaff'), 'mx-auto h-48 w-auto sm:h-64', false) ?>
        </div>
        <?php if (trim($html) !== '' && $toc !== []) : ?>
            <?= view('partials/cari_halaman', ['search' => [
                'target' => '#isi-panduan', 'kind' => 'bagian', 'id' => 'cari-panduan', 'class' => 'mt-8',
                'label' => lang('Panduan.searchLabel'), 'placeholder' => lang('Panduan.searchHint'),
                'found' => lang('Panduan.searchFound'), 'none' => lang('Panduan.searchNone'),
            ]]) ?>
        <?php endif ?>
        <?php if (trim($html) === '') : ?>
            <p class="mt-8"><?= lang('Panduan.empty') ?></p>
        <?php else : ?>
            <div id="isi-panduan" class="mt-8" data-lipat-bagian="14rem"><?= $html ?></div>
        <?php endif ?>
        <?php if ($whatsapp !== '') : ?>
            <p class="mt-10 rounded-lg bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= lang('Panduan.help', [esc($whatsapp)]) ?></p>
        <?php endif ?>
    </article>
</div>
