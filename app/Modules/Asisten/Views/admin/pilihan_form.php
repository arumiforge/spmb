<?php
/**
 * Add or edit one quick choice of Tanya Asisten. The fields of the chosen kind show (CSS :has(),
 * everything stays visible without support); empty translations show the Indonesian text.
 *
 * @var array<string, string> $choice
 * @var int|null              $position null for a new choice
 */
$isNew  = $position === null;
$action = $isNew ? url_to('asisten.pilihan.tambah') : url_to('asisten.pilihan.ubah', $position);
$card   = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$kind   = (string) old('kind', $choice['kind']);
$kinds  = [
    'link'     => ['label' => lang('Asisten.kindLink'), 'help' => lang('Asisten.kindLinkHelp')],
    'answer'   => ['label' => lang('Asisten.kindAnswer'), 'help' => lang('Asisten.kindAnswerHelp')],
    'whatsapp' => ['label' => lang('Asisten.kindWhatsapp'), 'help' => lang('Asisten.kindWhatsappHelp')],
];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= $action ?>" class="form-lebar group/pilihan">
    <?= csrf_field() ?>
    <section class="<?= $card ?>">
        <div class="grid grid-cols-1 gap-5">
            <?= view('partials/form_field', ['field' => ['name' => 'label', 'label' => lang('Asisten.fieldLabel'), 'value' => (string) $choice['label'], 'required' => true, 'help' => lang('Asisten.fieldLabelHelp'), 'attrs' => ['maxlength' => '40']]]) ?>

            <fieldset>
                <legend class="mb-2 text-sm font-medium text-gray-900 dark:text-white"><?= lang('Asisten.fieldKind') ?></legend>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <?php foreach ($kinds as $value => $option) : ?>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50 dark:border-gray-600 dark:has-[:checked]:border-primary-500 dark:has-[:checked]:bg-gray-700">
                            <input type="radio" name="kind" value="<?= $value ?>" id="jenis-<?= $value ?>" class="mt-0.5 h-4 w-4 border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700" <?= $kind === $value ? 'checked' : '' ?>>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white"><?= esc($option['label']) ?></span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400"><?= esc($option['help']) ?></span>
                            </span>
                        </label>
                    <?php endforeach ?>
                </div>
            </fieldset>

            <div class="group-has-[#jenis-answer:checked]/pilihan:hidden group-has-[#jenis-whatsapp:checked]/pilihan:hidden">
                <?= view('partials/form_field', ['field' => ['name' => 'url', 'label' => lang('Asisten.fieldUrl'), 'value' => (string) ($choice['url'] ?? ''), 'help' => lang('Asisten.fieldUrlHelp'), 'attrs' => ['maxlength' => '255', 'inputmode' => 'url', 'autocomplete' => 'off']]]) ?>
            </div>

            <div class="grid grid-cols-1 gap-5 group-has-[#jenis-link:checked]/pilihan:hidden group-has-[#jenis-whatsapp:checked]/pilihan:hidden">
                <?= view('partials/form_field', ['field' => ['name' => 'answer', 'type' => 'textarea', 'rows' => 6, 'label' => lang('Asisten.fieldAnswer'), 'value' => (string) ($choice['answer'] ?? ''), 'help' => lang('Asisten.fieldAnswerHelp'), 'attrs' => ['maxlength' => '2000']]]) ?>
            </div>

            <?= view('partials/terjemahan', ['translation' => ['fields' => [
                ['name' => 'label', 'label' => lang('Asisten.fieldLabel'), 'values' => ['en' => (string) ($choice['label_en'] ?? ''), 'ar' => (string) ($choice['label_ar'] ?? '')], 'maxlength' => 40],
                ['name' => 'answer', 'label' => lang('Asisten.fieldAnswer'), 'type' => 'textarea', 'rows' => 4, 'values' => ['en' => (string) ($choice['answer_en'] ?? ''), 'ar' => (string) ($choice['answer_ar'] ?? '')], 'maxlength' => 2000, 'help' => lang('Asisten.fieldAnswerTranslationHelp')],
            ]]]) ?>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row">
        <a href="<?= url_to('asisten.admin') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Asisten.choiceSave') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
