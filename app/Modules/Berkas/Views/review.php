<?php

use Modules\Berkas\Services\DocumentStorage;

/**
 * @var array{
 *     document: array<string, mixed>, applicant: array<string, mixed>, type: array<string, mixed>,
 *     others: list<array{type: array<string, mixed>, doc: array<string, mixed>|null, verifier: string|null}>,
 *     verifier: string|null, canViewApplicant: bool
 * } $review
 */
$doc       = $review['document'];
$applicant = $review['applicant'];
$type      = $review['type'];
$fileUrl   = url_to('berkas.lihat', $doc['id']);
$hasFile   = $doc['file_path'] !== null;
$isPdf     = $doc['mime'] === 'application/pdf';
$card      = 'rounded-lg border border-gray-200 bg-white p-4 sm:p-5 dark:border-gray-700 dark:bg-gray-800';
$dt        = 'text-xs text-gray-500 dark:text-gray-400';
$dd        = 'text-sm font-medium text-gray-900 dark:text-white';
$parents   = implode(' / ', array_filter([$applicant['father_name'] ?? null, $applicant['mother_name'] ?? null, $applicant['guardian_name'] ?? null]));
$errors    = session('errors');
$noteError = is_array($errors) ? ($errors['note'] ?? null) : null;
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= url_to('berkas.antrean') ?>" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('Berkas.backToQueue') ?></a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] xl:grid-cols-[minmax(0,1fr)_26rem]">
    <section class="<?= $card ?> min-w-0 self-start" aria-label="<?= esc($type['name'], 'attr') ?>">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= esc($type['name']) ?></h2>
            <?php if ($hasFile) : ?>
                <a href="<?= $fileUrl ?>" target="_blank" rel="noopener" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('Berkas.openFile') ?></a>
            <?php endif ?>
        </div>
        <?php if (! $hasFile) : ?>
            <p class="rounded-lg bg-gray-50 p-6 text-center text-sm text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?= lang('Status.document.to_follow') ?></p>
        <?php elseif ($isPdf) : ?>
            <object data="<?= $fileUrl ?>" type="application/pdf" class="h-[70vh] w-full rounded-lg border border-gray-200 dark:border-gray-700">
                <p class="p-6 text-center text-sm text-gray-600 dark:text-gray-300"><?= lang('Berkas.pdfFallback') ?> <a href="<?= $fileUrl ?>" target="_blank" rel="noopener" class="font-medium text-primary-700 underline dark:text-primary-400"><?= lang('Berkas.openFile') ?></a></p>
            </object>
        <?php else : ?>
            <a href="<?= $fileUrl ?>" target="_blank" rel="noopener" class="block rounded-lg bg-gray-100 dark:bg-gray-900">
                <img src="<?= $fileUrl ?>" alt="<?= esc($type['name'] . ' — ' . $applicant['full_name'], 'attr') ?>" class="mx-auto max-h-[70vh] w-auto max-w-full object-contain">
            </a>
        <?php endif ?>
    </section>

    <div class="space-y-6">
        <section class="<?= $card ?>">
            <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white"><?= lang('Berkas.applicantData') ?></h2>
            <dl class="space-y-2">
                <div><dt class="<?= $dt ?>"><?= lang('Berkas.fullName') ?></dt><dd class="<?= $dd ?>"><?= esc($applicant['full_name']) ?></dd></div>
                <div><dt class="<?= $dt ?>"><?= lang('Berkas.regNumber') ?></dt><dd class="<?= $dd ?> font-mono"><?= esc($applicant['registration_number']) ?></dd></div>
                <div><dt class="<?= $dt ?>"><?= lang('Berkas.birth') ?></dt><dd class="<?= $dd ?>"><?= esc($applicant['birth_place']) ?>, <?= esc(tanggal_indo($applicant['birth_date'])) ?></dd></div>
                <?php if ($parents !== '') : ?>
                    <div><dt class="<?= $dt ?>"><?= lang('Berkas.parents') ?></dt><dd class="<?= $dd ?>"><?= esc($parents) ?></dd></div>
                <?php endif ?>
            </dl>
            <?php if ($review['canViewApplicant']) : ?>
                <a href="<?= site_url('admin/pendaftar/' . (int) $applicant['id']) ?>" class="mt-3 inline-block text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('Berkas.openApplicant') ?></a>
            <?php endif ?>
        </section>

        <section class="<?= $card ?>">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white"><?= lang('Berkas.fileInfo') ?></h2>
                <?= view('partials/status_badge', ['badge' => status_badge_for('document', (string) $doc['status'])]) ?>
            </div>
            <dl class="space-y-2">
                <?php if ($hasFile) : ?>
                    <div><dt class="<?= $dt ?>"><?= lang('Berkas.originalName') ?></dt><dd class="<?= $dd ?> break-all"><?= esc((string) $doc['original_name']) ?></dd></div>
                    <div><dt class="<?= $dt ?>"><?= lang('Berkas.fileSize') ?></dt><dd class="<?= $dd ?>"><?= esc(DocumentStorage::bytesLabel((int) $doc['size_bytes'])) ?></dd></div>
                <?php endif ?>
                <div><dt class="<?= $dt ?>"><?= lang('Berkas.colUploaded') ?></dt><dd class="<?= $dd ?>"><?= esc(tanggal_indo($doc['created_at'], true)) ?><?= $doc['uploaded_by'] !== null ? ' · ' . lang('Berkas.uploadedByStaff') : '' ?></dd></div>
                <?php if ($doc['verified_at'] !== null) : ?>
                    <div><dt class="<?= $dt ?>"><?= lang('Berkas.colStatus') ?></dt><dd class="<?= $dd ?>"><?= esc(lang('Berkas.checkedBy', [tanggal_indo($doc['verified_at'], true), $review['verifier'] ?? '-'])) ?></dd></div>
                <?php endif ?>
                <?php if (! empty($doc['note'])) : ?>
                    <div><dt class="<?= $dt ?>"><?= lang('Berkas.committeeNote') ?></dt><dd class="<?= $dd ?>"><?= esc($doc['note']) ?></dd></div>
                <?php endif ?>
            </dl>

            <?php if ($hasFile) : ?>
                <form method="post" action="<?= url_to('berkas.periksa', $doc['id']) ?>" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <?= csrf_field() ?>
                    <input type="hidden" name="kembali" value="antrean">
                    <label for="note" class="mb-2 block text-sm font-medium <?= $noteError !== null ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white' ?>"><?= lang('Berkas.noteLabel') ?></label>
                    <textarea id="note" name="note" rows="3" maxlength="255" aria-describedby="note-help<?= $noteError !== null ? ' note-error' : '' ?>"
                              class="block w-full rounded-lg border <?= $noteError !== null ? 'border-red-500 bg-red-50' : 'border-gray-300 bg-gray-50' ?> p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"><?= esc((string) old('note', (string) ($doc['note'] ?? ''))) ?></textarea>
                    <p id="note-help" class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?= lang('Berkas.noteHelp') ?></p>
                    <?php if ($noteError !== null) : ?><p id="note-error" class="mt-2 text-sm text-red-600 dark:text-red-500"><?= esc($noteError) ?></p><?php endif ?>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button type="submit" name="status" value="valid" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-700 px-4 py-3 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"><?= icon('check', 'h-4 w-4') ?><?= lang('Berkas.markValid') ?></button>
                        <button type="submit" name="status" value="invalid" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-700 px-4 py-3 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"><?= icon('x', 'h-4 w-4') ?><?= lang('Berkas.markInvalid') ?></button>
                    </div>
                </form>
            <?php endif ?>
        </section>

        <?php if ($review['others'] !== []) : ?>
            <section class="<?= $card ?>">
                <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white"><?= lang('Berkas.otherDocuments') ?></h2>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($review['others'] as $other) : ?>
                        <li class="flex items-center justify-between gap-2 py-2">
                            <a href="<?= url_to('berkas.periksa', $other['doc']['id']) ?>" class="min-w-0 truncate text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= esc($other['type']['name']) ?></a>
                            <?= view('partials/status_badge', ['badge' => status_badge_for('document', (string) $other['doc']['status'])]) ?>
                        </li>
                    <?php endforeach ?>
                </ul>
            </section>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
