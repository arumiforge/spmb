<?php
/**
 * "Tanya Asisten" widget (D-110) on every public page and the applicant's own pages: a round button
 * in the bottom corner (above the mobile bottom bar and the sticky step bar of /daftar, mirrored on
 * Arabic pages) that opens the chat. Phones: a full-height sheet (modal dialog). From 40rem: a panel
 * above the button that leaves the page usable. Renders nothing while the widget is off.
 *
 * resources/js/asisten.js does the rest: quick choices, questions to asisten/tanya (JSON, CSRF token
 * from asisten/mulai, so a session only starts when someone uses the chat), loading and retry, and
 * the conversation kept for the browser tab (sessionStorage). Without JavaScript the button stays hidden.
 */
use App\Libraries\Locales;
use Modules\Asisten\Libraries\AvatarStorage;
use Modules\Asisten\Libraries\QuickChoices;

if (! (bool) setting('Asisten.enabled')) {
    return;
}

$choices  = QuickChoices::visible();
$greeting = setting_localized('Asisten.greeting');
$greeting = $greeting !== '' ? $greeting : lang('Asisten.greetingDefault', [(string) setting('School.name')]);
$whatsapp = (string) setting('School.whatsapp');
$max      = max(20, (int) setting('Asisten.maxLength'));
$locale   = Locales::current();
$texts    = [
    'you'         => lang('Asisten.you'),
    'assistant'   => lang('Asisten.assistant'),
    'autoAnswer'  => lang('Asisten.autoAnswer'),
    'fromQa'      => lang('Asisten.fromQa'),
    'notThis'     => lang('Asisten.notThis'),
    'related'     => lang('Asisten.related'),
    'retry'       => lang('Asisten.retry'),
    'masked'      => lang('Asisten.maskedNote'),
    'network'     => lang('Asisten.errNetworkVisitor'),
    'charsLeft'   => lang('Asisten.charsLeft'),
    'opensNewTab' => lang('App.opensNewTab'),
    'restarted'   => lang('Asisten.restarted'),
];
$chip = 'asisten-chip';
?>
<div class="asisten" data-asisten hidden data-no-print
     data-asisten-mulai="<?= esc(site_url('asisten/mulai'), 'attr') ?>"
     data-asisten-tanya="<?= esc(site_url('asisten/tanya'), 'attr') ?>"
     data-asisten-kunci="spmb-asisten-<?= esc($locale, 'attr') ?>"
     data-asisten-teks="<?= esc(json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'attr') ?>">
    <button type="button" class="asisten-peluncur" data-asisten-buka aria-haspopup="dialog" aria-controls="asisten-panel" aria-expanded="false" title="<?= esc(lang('Asisten.launcher'), 'attr') ?>">
        <span class="asisten-peluncur-gambar" aria-hidden="true"><?= AvatarStorage::html('h-9 w-9') ?></span>
        <span class="sr-only"><?= lang('Asisten.launcher') ?></span>
    </button>

    <dialog id="asisten-panel" class="asisten-panel" aria-labelledby="asisten-judul" aria-describedby="asisten-catatan" lang="<?= esc($locale, 'attr') ?>">
        <div class="asisten-kepala">
            <span class="asisten-avatar" aria-hidden="true"><?= AvatarStorage::html('h-10 w-10') ?></span>
            <div class="min-w-0 flex-1">
                <h2 id="asisten-judul" class="truncate text-base font-semibold text-gray-900 dark:text-white" tabindex="-1"><?= lang('Asisten.title') ?></h2>
                <p class="truncate text-xs text-gray-600 dark:text-gray-400"><?= lang('Asisten.subtitle') ?></p>
            </div>
            <button type="button" class="asisten-tombol-ikon" data-asisten-ulang title="<?= esc(lang('Asisten.restart'), 'attr') ?>">
                <?= icon('refresh-cw', 'h-5 w-5') ?><span class="sr-only"><?= lang('Asisten.restart') ?></span>
            </button>
            <button type="button" class="asisten-tombol-ikon" data-asisten-tutup title="<?= esc(lang('Asisten.close'), 'attr') ?>">
                <?= icon('x', 'h-5 w-5') ?><span class="sr-only"><?= lang('Asisten.close') ?></span>
            </button>
        </div>
        <div id="asisten-catatan" class="asisten-catatan">
            <p class="flex items-start gap-2"><?= icon('shield-check', 'mt-0.5 h-4 w-4 shrink-0') ?><span><?= lang('Asisten.notice') ?></span></p>
            <?php if ($whatsapp !== '') : ?>
                <a href="<?= esc(whatsapp_url($whatsapp), 'attr') ?>" target="_blank" rel="noopener" class="asisten-catatan-wa">
                    <?= social_icon('whatsapp', 'h-4 w-4 shrink-0') ?><span><?= lang('Asisten.askOnWhatsapp') ?></span><span class="sr-only"><?= lang('App.opensNewTab') ?></span>
                </a>
            <?php endif ?>
        </div>

        <div class="asisten-isi" data-asisten-isi>
            <div class="asisten-daftar" data-asisten-daftar role="log" aria-live="polite" aria-relevant="additions" aria-label="<?= esc(lang('Asisten.conversation'), 'attr') ?>">
                <div class="asisten-pesan dari-asisten">
                    <span class="asisten-pesan-avatar" aria-hidden="true"><?= AvatarStorage::html('h-7 w-7') ?></span>
                    <div class="asisten-gelembung">
                        <p class="sr-only"><?= lang('Asisten.assistant') ?>:</p>
                        <p dir="auto"><bdi><?= esc($greeting) ?></bdi></p>
                    </div>
                </div>
                <?php if ($choices !== []) : ?>
                    <div class="asisten-pilihan" role="group" aria-label="<?= esc(lang('Asisten.quickChoices'), 'attr') ?>" data-asisten-pilihan>
                        <?php foreach ($choices as $item) : ?>
                            <?php if ($item['kind'] === 'answer') : ?>
                                <button type="button" class="<?= $chip ?>" data-asisten-chip="<?= $item['position'] ?>"><bdi><?= esc($item['label']) ?></bdi></button>
                            <?php else : ?>
                                <a href="<?= esc(site_url('asisten/buka/' . $item['position']), 'attr') ?>" rel="nofollow<?= $item['external'] ? ' noopener' : '' ?>" class="<?= $chip ?>"<?= $item['external'] ? ' target="_blank"' : '' ?>>
                                    <?php if ($item['kind'] === 'whatsapp') : ?><?= social_icon('whatsapp', 'h-4 w-4 shrink-0') ?><?php endif ?>
                                    <bdi><?= esc($item['label']) ?></bdi>
                                    <?php if ($item['external']) : ?><span class="sr-only"><?= lang('App.opensNewTab') ?></span><?php endif ?>
                                </a>
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
            <p class="asisten-memuat" data-asisten-memuat role="status" hidden>
                <span class="asisten-titik" aria-hidden="true"><span></span><span></span><span></span></span>
                <span><?= lang('Asisten.loading') ?></span>
            </p>
        </div>

        <form class="asisten-form" data-asisten-form novalidate>
            <label for="asisten-masukan" class="sr-only"><?= lang('Asisten.inputLabel') ?></label>
            <textarea id="asisten-masukan" name="pertanyaan" rows="1" maxlength="<?= $max ?>" dir="auto" autocomplete="off"
                      placeholder="<?= esc(lang('Asisten.placeholder'), 'attr') ?>" aria-describedby="asisten-sisa" data-asisten-masukan></textarea>
            <button type="submit" class="asisten-kirim" data-asisten-kirim>
                <?= icon('arrow-right', 'h-5 w-5') ?><span class="sr-only"><?= lang('Asisten.send') ?></span>
            </button>
            <p id="asisten-sisa" class="asisten-sisa" data-asisten-sisa aria-live="polite"></p>
        </form>

        <template data-asisten-ikon="wa"><?= social_icon('whatsapp', 'h-4 w-4 shrink-0') ?></template>
        <template data-asisten-ikon="otomatis"><?= icon('sparkles', 'h-3.5 w-3.5 shrink-0') ?></template>
        <template data-asisten-ikon="tanya-jawab"><?= icon('book-open', 'h-3.5 w-3.5 shrink-0') ?></template>
        <template data-asisten-ikon="buka"><?= icon('arrow-right', 'h-4 w-4 shrink-0') ?></template>
        <template data-asisten-ikon="ulang"><?= icon('refresh-cw', 'h-4 w-4 shrink-0') ?></template>
        <template data-asisten-ikon="avatar"><?= AvatarStorage::html('h-7 w-7') ?></template>
    </dialog>
</div>
