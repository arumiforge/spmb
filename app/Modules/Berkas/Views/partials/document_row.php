<?php

use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\DocumentStorage;
use Modules\Berkas\Services\UploadInput;

/**
 * One document type in an applicant panel (public or admin).
 *
 * @var array{
 *     row: array{type: array<string, mixed>, doc: array<string, mixed>|null, verifier: string|null},
 *     mode: string, applicant: array<string, mixed>, error: string|null,
 *     canEdit?: bool, canVerify?: bool
 * } $item
 */
$type      = $item['row']['type'];
$doc       = $item['row']['doc'];
$typeId    = (int) $type['id'];
$isAdmin   = $item['mode'] === 'admin';
$status    = $doc['status'] ?? null;
$hasFile   = $doc !== null && $doc['file_path'] !== null;
$cancelled = ($item['applicant']['status'] ?? '') === 'cancelled';
$fileUrl   = $hasFile ? ($isAdmin ? url_to('berkas.lihat', $doc['id']) : url_to('berkas.milik-saya', $doc['id'])) : null;
$postUrl   = $isAdmin ? url_to('berkas.unggah-petugas', $item['applicant']['id'], $typeId) : url_to('berkas.unggah', $typeId);
$canUpload = $isAdmin ? ! empty($item['canEdit']) : (! $cancelled && $status !== 'valid');
$canFollow = $canUpload && (bool) $type['may_follow'] && ($status === null || $status === 'invalid');
$inputId   = 'berkas-file-' . $typeId;
$btn       = 'inline-flex items-center justify-center gap-1.5 rounded-lg px-4 py-2.5 text-sm font-medium focus:outline-none focus:ring-4';
$primary   = $btn . ' bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-300 dark:focus:ring-primary-800';
$secondary = $btn . ' border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$hint      = lang('Berkas.formatsHint', [DocumentTypeModel::formatLabel($type), DocumentStorage::sizeLabel((int) $type['max_size_kb'])]);
?>
<li id="berkas-<?= $typeId ?>" class="scroll-mt-24 p-4 sm:px-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
        <div class="min-w-0">
            <p class="font-medium text-gray-900 dark:text-white">
                <?= esc(localized($type, 'name')) ?>
                <span class="ms-1 text-xs font-normal <?= (bool) $type['is_required'] ? 'text-red-700 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' ?>">(<?= lang((bool) $type['is_required'] ? 'Berkas.required' : 'Berkas.optional') ?><?= (bool) $type['may_follow'] ? ', ' . lang('Berkas.mayFollowHint') : '' ?>)</span>
            </p>
            <?php if (localized($type, 'description') !== '') : ?>
                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300"><span><bdi><?= esc(localized($type, 'description')) ?></bdi></span></p>
            <?php endif ?>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"><?= esc($hint) ?></p>
            <?php $templateLink = view('Modules\Berkas\Views\partials\template_link', ['link' => ['type' => $type]], ['saveData' => false]); ?>
            <?php if (trim($templateLink) !== '') : ?>
                <p class="mt-1.5"><?= $templateLink ?></p>
            <?php endif ?>
            <?php if ($hasFile) : ?>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <?= esc(lang('Berkas.uploadedAt', [tanggal_indo($doc['created_at'], true)])) ?><?= $isAdmin && $doc['uploaded_by'] !== null ? ' · ' . lang('Berkas.uploadedByStaff') : '' ?>
                    <?php if ($isAdmin && $doc['verified_at'] !== null) : ?>
                        <br><?= esc(lang('Berkas.checkedBy', [tanggal_indo($doc['verified_at'], true), $item['row']['verifier'] ?? '-'])) ?>
                    <?php endif ?>
                </p>
            <?php endif ?>
        </div>
        <div class="order-first shrink-0 sm:order-none">
            <?= view('partials/status_badge', ['badge' => $status === null ? ['label' => lang('Berkas.notUploaded'), 'color' => (bool) $type['is_required'] ? 'red' : 'gray'] : status_badge_for('document', (string) $status)]) ?>
        </div>
    </div>

    <?php if (! empty($doc['note']) && in_array($status, ['invalid', 'valid'], true)) : ?>
        <div class="mt-3 flex gap-2 rounded-lg p-3 text-sm <?= $status === 'invalid' ? 'bg-red-50 text-red-800 dark:bg-gray-700 dark:text-red-400' : 'bg-gray-50 text-gray-800 dark:bg-gray-700 dark:text-gray-200' ?>">
            <?= icon($status === 'invalid' ? 'warning' : 'info', 'mt-0.5 h-4 w-4 shrink-0') ?>
            <p><span class="font-medium"><?= lang('Berkas.committeeNote') ?>:</span> <?= esc($doc['note']) ?></p>
        </div>
    <?php elseif (! $isAdmin && $status === 'valid') : ?>
        <p class="mt-2 text-sm text-green-700 dark:text-green-400"><?= lang('Berkas.validInfo') ?></p>
    <?php elseif (! $isAdmin && $status === 'to_follow') : ?>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300"><?= lang('Berkas.toFollowInfo') ?></p>
    <?php endif ?>

    <?php if (! empty($item['error'])) : ?>
        <div class="mt-3 flex gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-gray-700 dark:text-red-400" role="alert">
            <?= icon('warning', 'mt-0.5 h-4 w-4 shrink-0') ?><p><?= esc($item['error']) ?></p>
        </div>
    <?php endif ?>

    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start">
        <?php if ($fileUrl !== null) : ?>
            <a href="<?= $fileUrl ?>" target="_blank" rel="noopener" class="<?= $secondary ?>"><?= icon('eye', 'h-4 w-4') ?><?= lang('Berkas.view') ?><span class="sr-only">: <?= esc(localized($type, 'name')) ?></span></a>
        <?php endif ?>

        <?php if ($isAdmin && ! empty($item['canVerify']) && $hasFile) : ?>
            <details class="group w-full rounded-lg border border-gray-200 sm:w-auto dark:border-gray-600">
                <summary class="<?= $primary ?> cursor-pointer list-none"><?= icon('check', 'h-4 w-4') ?><?= lang('Berkas.verifyButton') ?></summary>
                <form method="post" action="<?= url_to('berkas.periksa', $doc['id']) ?>" class="space-y-3 p-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="kembali" value="pendaftar">
                    <label for="note-<?= $typeId ?>" class="block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Berkas.noteLabel') ?></label>
                    <textarea id="note-<?= $typeId ?>" name="note" rows="2" maxlength="255" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"><?= esc((string) ($doc['note'] ?? '')) ?></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= lang('Berkas.noteHelp') ?></p>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="submit" name="status" value="valid" class="<?= $btn ?> bg-green-700 text-white hover:bg-green-800 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"><?= lang('Berkas.markValid') ?></button>
                        <button type="submit" name="status" value="invalid" class="<?= $btn ?> bg-red-700 text-white hover:bg-red-800 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"><?= lang('Berkas.markInvalid') ?></button>
                    </div>
                </form>
            </details>
        <?php endif ?>

        <?php if ($canUpload) : ?>
            <?php if ($isAdmin) : ?><details class="w-full rounded-lg border border-gray-200 sm:w-auto dark:border-gray-600"><summary class="<?= $secondary ?> cursor-pointer list-none border-0"><?= icon('plus', 'h-4 w-4') ?><?= lang('Berkas.staffUploadFor') ?></summary><div class="p-3"><?php endif ?>
            <form method="post" action="<?= $postUrl ?>" enctype="multipart/form-data" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <?= csrf_field() ?>
                <label for="<?= $inputId ?>" class="sr-only"><?= esc(lang('Berkas.chooseFile', [localized($type, 'name')])) ?></label>
                <input id="<?= $inputId ?>" type="file" name="<?= UploadInput::FIELD ?>" accept="<?= esc(DocumentTypeModel::acceptOf($type), 'attr') ?>" required
                       class="block w-full min-w-0 cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 file:me-3 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:py-2.5 file:text-white focus:outline-none sm:w-72 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:file:bg-gray-600">
                <button type="submit" class="<?= $primary ?> shrink-0"><?= lang($hasFile ? ($status === 'invalid' ? 'Berkas.uploadAgain' : 'Berkas.replace') : 'Berkas.upload') ?></button>
            </form>
            <?php if ($canFollow) : ?>
                <form method="post" action="<?= $postUrl ?>" data-confirm="<?= esc(lang('Berkas.toFollowConfirm', [localized($type, 'name')]), 'attr') ?>" class="<?= $isAdmin ? 'mt-2' : '' ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="menyusul">
                    <button type="submit" class="<?= $secondary ?> w-full sm:w-auto"><?= icon('clock', 'h-4 w-4') ?><?= lang('Berkas.toFollowButton') ?></button>
                </form>
            <?php endif ?>
            <?php if ($isAdmin) : ?></div></details><?php endif ?>
        <?php endif ?>
    </div>
</li>
