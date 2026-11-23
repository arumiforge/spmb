<?php
/**
 * Public questions and answers (/tanya-jawab, D-072). Topics as sections,
 * questions as native disclosure widgets (<details>), so the page works
 * without JavaScript and with screen readers.
 *
 * @var array{
 *     topics: list<array{slug: string, topic: string, items: list<array{id: string, question: string, html: string}>}>,
 *     onlineOpen: bool, berkasOn: bool, phone: string, whatsapp: string, email: string, address: string
 * } $page
 */
$answer    = 'space-y-3 leading-relaxed text-gray-700 dark:text-gray-300 [&_a]:font-medium [&_a]:text-primary-700 [&_a]:underline hover:[&_a]:no-underline dark:[&_a]:text-primary-400 [&_ol]:list-decimal [&_ol]:space-y-1 [&_ol]:ps-6 [&_strong]:font-semibold [&_strong]:text-gray-900 dark:[&_strong]:text-white [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:ps-6';
$primary   = 'inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-6 py-3 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800';
$secondary = 'inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-base font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$wa        = preg_replace('/\D/', '', $page['whatsapp']);
$wa        = str_starts_with((string) $wa, '0') ? '62' . substr((string) $wa, 1) : $wa;
$contacts  = array_filter([
    $page['whatsapp'] !== '' ? ['icon' => 'phone', 'label' => lang('Situs.qaWhatsapp'), 'text' => $page['whatsapp'], 'href' => 'https://wa.me/' . $wa] : null,
    $page['phone'] !== '' ? ['icon' => 'phone', 'label' => lang('Situs.qaPhone'), 'text' => $page['phone'], 'href' => 'tel:' . preg_replace('/[^0-9+]/', '', $page['phone'])] : null,
    $page['email'] !== '' ? ['icon' => 'mail', 'label' => lang('Situs.qaEmail'), 'text' => $page['email'], 'href' => 'mailto:' . $page['email']] : null,
    $page['address'] !== '' ? ['icon' => 'map-pin', 'label' => lang('Situs.qaAddress'), 'text' => $page['address'], 'href' => null] : null,
]);
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-screen-md px-4 py-6 sm:py-10">
    <header class="mb-8">
        <h1 class="mb-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white"><?= lang('Situs.qaTitle') ?></h1>
        <p class="text-lg text-gray-600 dark:text-gray-300"><?= lang('Situs.qaIntro') ?></p>
    </header>

    <?php if ($page['topics'] === []) : ?>
        <?= view('partials/empty_state', ['empty' => ['title' => lang('Situs.qaEmptyTitle'), 'text' => lang('Situs.qaEmptyText'), 'image' => 'state/kosong-tanya-jawab', 'fallback' => 'state/kosong-data', 'imageAlt' => '']]) ?>
    <?php else : ?>
        <?php if (count($page['topics']) > 1) : ?>
            <?php // Topic contents (D-088): sticks under the header while reading and highlights the topic in view. ?>
            <nav class="daftar-isi sticky top-16 z-30 -mx-4 mb-8 border-b border-gray-200 bg-white/95 px-4 py-3 backdrop-blur sm:top-[4.5rem] dark:border-gray-700 dark:bg-gray-900/95" aria-label="<?= lang('Situs.qaTopicsLabel') ?>" data-daftar-isi>
                <p class="mb-2 text-sm font-semibold text-gray-900 dark:text-white"><?= lang('Situs.qaTopicsLabel') ?></p>
                <ul class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:thin] sm:flex-wrap sm:overflow-visible">
                    <?php foreach ($page['topics'] as $topic) : ?>
                        <li class="shrink-0"><a href="#<?= esc($topic['slug'], 'attr') ?>" class="rounded-full! border border-gray-300 px-4! py-2! text-sm font-medium whitespace-nowrap focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-300 dark:border-gray-600 dark:focus-visible:ring-primary-800 [&.is-aktif]:border-primary-600 dark:[&.is-aktif]:border-primary-400"><?= esc($topic['topic']) ?></a></li>
                    <?php endforeach ?>
                </ul>
            </nav>
        <?php endif ?>

        <div class="space-y-10">
            <?php foreach ($page['topics'] as $topic) : ?>
                <section aria-labelledby="<?= esc($topic['slug'], 'attr') ?>">
                    <h2 id="<?= esc($topic['slug'], 'attr') ?>" class="mb-4 scroll-mt-56 text-xl font-bold text-gray-900 sm:text-2xl dark:text-white"><?= esc($topic['topic']) ?></h2>
                    <div class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 bg-white dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-800">
                        <?php foreach ($topic['items'] as $item) : ?>
                            <details id="<?= esc($item['id'], 'attr') ?>" class="group scroll-mt-56">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-start text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-primary-300 dark:text-white dark:hover:bg-gray-700 dark:focus-visible:ring-primary-800 [&::-webkit-details-marker]:hidden">
                                    <span><?= esc($item['question']) ?></span>
                                    <?= icon('chevron-down', 'h-5 w-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180 motion-reduce:transition-none dark:text-gray-400') ?>
                                </summary>
                                <div class="<?= $answer ?> px-5 pb-5 pt-1"><?= $item['html'] ?></div>
                            </details>
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <section class="mt-12 rounded-lg border border-gray-200 bg-gray-50 p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800" aria-labelledby="kontak">
        <h2 id="kontak" class="mb-1 text-xl font-bold text-gray-900 dark:text-white"><?= lang('Situs.qaMoreTitle') ?></h2>
        <p class="mb-4 text-gray-600 dark:text-gray-300"><?= $contacts === [] ? lang('Situs.qaNoContact') : lang('Situs.qaMoreText') ?></p>
        <?php if ($contacts !== []) : ?>
            <dl class="mb-6 grid gap-4 sm:grid-cols-2">
                <?php foreach ($contacts as $contact) : ?>
                    <div class="flex min-w-0 items-start gap-3">
                        <?= icon($contact['icon'], 'mt-0.5 h-5 w-5 shrink-0 text-primary-700 dark:text-primary-400') ?>
                        <div class="min-w-0">
                        <dt class="text-sm text-gray-500 dark:text-gray-400"><?= esc($contact['label']) ?></dt>
                        <dd class="break-words font-medium text-gray-900 dark:text-white">
                                <?php if ($contact['href'] !== null) : ?>
                                    <a href="<?= esc($contact['href'], 'attr') ?>" class="hover:underline"<?= str_starts_with($contact['href'], 'https://') ? ' target="_blank" rel="noopener"' : '' ?>><?= esc($contact['text']) ?></a>
                                <?php else : ?>
                                    <?= nl2br(esc($contact['text'])) ?>
                                <?php endif ?>
                        </dd>
                        </div>
                    </div>
                <?php endforeach ?>
            </dl>
        <?php endif ?>
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <?php if ($page['onlineOpen']) : ?>
                <a href="<?= site_url('daftar') ?>" class="tombol-daftar tombol-daftar-penuh"><span class="tombol-daftar-isi"><?= lang('Situs.register') ?><?= icon('arrow-right', 'h-5 w-5') ?></span></a>
            <?php endif ?>
            <a href="<?= url_to('situs.persyaratan') ?>" class="<?= $page['onlineOpen'] ? $secondary : $primary ?>"><?= icon('clipboard', 'h-5 w-5') ?><?= lang('Situs.seeRequirements') ?></a>
            <a href="<?= url_to('situs.jadwal') ?>" class="<?= $secondary ?>"><?= icon('calendar', 'h-5 w-5') ?><?= lang('Situs.seeSchedule') ?></a>
            <a href="<?= site_url('cek-status') ?>" class="<?= $secondary ?>"><?= icon('search', 'h-5 w-5') ?><?= lang('Situs.checkStatus') ?></a>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
