<?php

use Modules\Situs\Services\HomePopup;

/**
 * Admin form for the home page popup (settings Situs.popup*).
 *
 * @var array<string, mixed> $popup      HomePopup::settings()
 * @var bool                 $live       Visitors see it today
 * @var string|null          $imageUrl
 * @var string|null          $attachment Download name of the current attachment
 */
$btn     = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$state   = match (true) {
    $live              => ['text' => lang('Situs.popupStateLive'), 'class' => 'bg-green-50 text-green-800 dark:bg-gray-800 dark:text-green-400', 'icon' => 'check'],
    $popup['enabled']  => ['text' => lang('Situs.popupStateWaiting'), 'class' => 'bg-yellow-50 text-yellow-800 dark:bg-gray-800 dark:text-yellow-300', 'icon' => 'clock'],
    default            => ['text' => lang('Situs.popupStateOff'), 'class' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300', 'icon' => 'info'],
};
$frequencies = array_map(static fn (string $key): string => lang('Situs.' . $key), HomePopup::FREQUENCIES);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= site_url('/') ?>?popup=pratinjau" target="_blank" rel="noopener" class="<?= $btn ?>" title="<?= esc(lang('Situs.popupPreviewHelp'), 'attr') ?>">
    <?= icon('eye', 'h-4 w-4') ?><?= lang('Situs.popupPreviewLink') ?><span class="sr-only"><?= lang('Situs.opensNewTab') ?></span>
</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="tata-isi" data-lipatan-lingkup>
<form id="form-popup" method="post" action="<?= url_to('situs.popup.admin') ?>" enctype="multipart/form-data" class="form-lebar">
    <?= csrf_field() ?>

    <div class="flex items-start gap-3 rounded-lg p-4 text-sm <?= $state['class'] ?>" role="status">
        <?= icon($state['icon'], 'mt-0.5 h-5 w-5 shrink-0') ?>
        <p><?= $state['text'] ?> <?= lang('Situs.popupPreviewHelp') ?></p>
    </div>

    <?= lipatan_buka(['id' => 'popup-isi', 'icon' => 'megaphone', 'title' => lang('Situs.popupContentTitle')]) ?>
        <div class="grid grid-cols-1 gap-5">
            <div>
                <input type="hidden" name="enabled" value="0">
                <label class="inline-flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="enabled" value="1" class="peer sr-only" <?= old('enabled', $popup['enabled'] ? '1' : '0') === '1' ? 'checked' : '' ?>>
                    <span class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full bg-gray-200 peer-checked:bg-primary-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white rtl:peer-checked:after:-translate-x-full dark:bg-gray-700 dark:peer-focus:ring-primary-800"></span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white"><?= lang('Situs.popupEnabled') ?></span>
                </label>
            </div>
            <?= view('partials/form_field', ['field' => ['name' => 'title', 'label' => lang('Situs.popupFieldTitle'), 'value' => $popup['title'], 'help' => lang('Situs.popupFieldTitleHelp'), 'attrs' => ['maxlength' => '120']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'text', 'type' => 'textarea', 'rows' => 7, 'label' => lang('Situs.popupFieldText'), 'value' => $popup['text'], 'help' => lang('Situs.popupFieldTextHelp'), 'attrs' => ['maxlength' => '3000']]]) ?>
        </div>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'popup-tambahan', 'icon' => 'paperclip', 'title' => lang('Situs.popupExtrasTitle')]) ?>
        <div class="grid grid-cols-1 gap-5">
            <div class="space-y-3">
                <?php if ($imageUrl !== null) : ?>
                    <div class="flex flex-wrap items-center gap-4">
                        <img src="<?= esc($imageUrl, 'attr') ?>" alt="<?= esc(lang('Situs.popupImageCurrent'), 'attr') ?>" class="h-24 w-auto max-w-[12rem] rounded-lg border border-gray-200 object-contain dark:border-gray-600">
                        <?= view('partials/form_field', ['field' => ['name' => 'remove_image', 'type' => 'checkbox', 'label' => lang('Situs.popupRemoveImage'), 'value' => '0']]) ?>
                    </div>
                <?php endif ?>
                <?= view('partials/form_field', ['field' => ['name' => 'image', 'type' => 'file', 'label' => lang('Situs.popupFieldImage'), 'help' => lang('Situs.popupFieldImageHelp'), 'attrs' => ['accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp']]]) ?>
            </div>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <?= view('partials/form_field', ['field' => ['name' => 'buttonLabel', 'label' => lang('Situs.popupFieldButtonLabel'), 'value' => $popup['buttonLabel'], 'help' => lang('Situs.popupFieldButtonLabelHelp'), 'attrs' => ['maxlength' => '40']]]) ?>
                <?= view('partials/form_field', ['field' => ['name' => 'buttonUrl', 'label' => lang('Situs.popupFieldButtonUrl'), 'value' => $popup['buttonUrl'], 'help' => lang('Situs.linkHelp'), 'placeholder' => '/daftar', 'attrs' => ['maxlength' => '255', 'autocomplete' => 'off', 'inputmode' => 'url']]]) ?>
            </div>
            <div class="space-y-3">
                <?php if ($attachment !== null) : ?>
                    <div class="flex flex-wrap items-center gap-4">
                        <a href="<?= site_url('popup/lampiran') ?>" class="inline-flex items-center gap-1.5 break-all text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= icon('paperclip', 'h-4 w-4 shrink-0') ?><?= esc(lang('Situs.popupAttachmentCurrent', [$attachment])) ?></a>
                        <?= view('partials/form_field', ['field' => ['name' => 'remove_attachment', 'type' => 'checkbox', 'label' => lang('Situs.popupRemoveAttachment'), 'value' => '0']]) ?>
                    </div>
                <?php endif ?>
                <?= view('partials/form_field', ['field' => ['name' => 'attachment', 'type' => 'file', 'label' => lang('Situs.popupFieldAttachment'), 'help' => lang('Situs.popupFieldAttachmentHelp'), 'attrs' => ['accept' => '.pdf,.doc,.docx,.xls,.xlsx']]]) ?>
            </div>
        </div>
    <?= lipatan_tutup() ?>

    <?= lipatan_buka(['id' => 'popup-jadwal', 'icon' => 'calendar', 'title' => lang('Situs.popupScheduleTitle')]) ?>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <?= view('partials/form_field', ['field' => ['name' => 'startsOn', 'type' => 'date', 'label' => lang('Situs.fieldStartsOn'), 'value' => $popup['startsOn'], 'help' => lang('Situs.dateHelp')]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'endsOn', 'type' => 'date', 'label' => lang('Situs.fieldEndsOn'), 'value' => $popup['endsOn'], 'help' => lang('Situs.dateHelp')]]) ?>
            <div class="md:col-span-2">
                <?= view('partials/form_field', ['field' => ['name' => 'frequency', 'type' => 'select', 'label' => lang('Situs.popupFieldFrequency'), 'value' => $popup['frequency'], 'required' => true, 'options' => $frequencies, 'help' => lang('Situs.popupFrequencyHelp')]]) ?>
            </div>
        </div>
    <?= lipatan_tutup() ?>

    <div class="flex flex-col-reverse gap-3 sm:flex-row">
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Situs.popupSave') ?></button>
    </div>
</form>
<?= view('partials/tata_isi_rail', ['form' => 'form-popup', 'save' => lang('Situs.popupSave')], ['saveData' => false]) ?>
</div>
<?= $this->endSection() ?>
