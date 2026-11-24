<?php
/**
 * Website → Tanya Asisten (asisten.atur): widget switch, greeting, picture and quick choices.
 *
 * @var array<string, string>                   $values
 * @var string                                  $greetingDefault
 * @var string|null                             $picture
 * @var list<array<string, mixed>>              $choices QuickChoices::all() + available
 * @var bool                                    $custom
 * @var bool                                    $full
 * @var bool                                    $aiOn
 * @var bool                                    $canAi
 * @var bool                                    $hasWhatsapp
 */

use Modules\Asisten\Libraries\AvatarStorage;

$card    = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2      = 'text-lg font-semibold text-gray-900 dark:text-white';
$btn     = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$primary = 'inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800';
$iconBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$last    = count($choices) - 1;
$move    = static function (int $position, string $direction, bool $disabled, string $label) use ($iconBtn): string {
    return '<form method="post" action="' . esc(url_to('asisten.pilihan.pindah', $position), 'attr') . '">' . csrf_field()
        . '<input type="hidden" name="arah" value="' . $direction . '">'
        . '<button type="submit" class="' . $iconBtn . '"' . ($disabled ? ' disabled' : '') . ' title="' . esc($label, 'attr') . '">'
        . '<span class="sr-only">' . esc($label) . '</span>' . icon('chevron-down', 'h-4 w-4' . ($direction === 'naik' ? ' rotate-180' : '')) . '</button></form>';
};
$errors = session('errors');
$errors = is_array($errors) ? $errors : [];
$on     = old('enabled', $values['enabled']) === '1';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <div class="flex items-start gap-3 rounded-lg p-4 text-sm <?= $values['enabled'] === '1' ? 'bg-green-50 text-green-800 dark:bg-gray-800 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' ?>" role="status">
        <?= icon($values['enabled'] === '1' ? 'check' : 'info', 'mt-0.5 h-5 w-5 shrink-0') ?>
        <div class="space-y-1">
            <p class="font-medium"><?= lang($values['enabled'] === '1' ? 'Asisten.stateOn' : 'Asisten.stateOff') ?></p>
            <p><?= lang($aiOn ? 'Asisten.stateAiOn' : 'Asisten.stateAiOff') ?>
                <?php if ($canAi) : ?><a href="<?= url_to('asisten.ai') ?>" class="font-medium underline hover:no-underline"><?= lang('Asisten.aiSettingsLink') ?></a><?php endif ?>
            </p>
        </div>
    </div>

    <form method="post" action="<?= url_to('asisten.admin') ?>" enctype="multipart/form-data" class="form-lebar">
        <?= csrf_field() ?>
        <section class="<?= $card ?>" aria-labelledby="asisten-umum">
            <h2 id="asisten-umum" class="<?= $h2 ?> mb-4"><?= lang('Asisten.generalTitle') ?></h2>
            <div class="grid grid-cols-1 gap-5">
                <div>
                    <input type="hidden" name="enabled" value="0">
                    <label class="inline-flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="enabled" value="1" class="peer sr-only" <?= $on ? 'checked' : '' ?>>
                        <span class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full bg-gray-200 peer-checked:bg-primary-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white rtl:peer-checked:after:-translate-x-full dark:bg-gray-700 dark:peer-focus:ring-primary-800"></span>
                        <span>
                            <span class="block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Asisten.fieldEnabled') ?></span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.fieldEnabledHelp') ?></span>
                        </span>
                    </label>
                </div>
                <?= view('partials/form_field', ['field' => ['name' => 'greeting', 'type' => 'textarea', 'rows' => 3, 'label' => lang('Asisten.fieldGreeting'), 'value' => $values['greeting'], 'help' => lang('Asisten.fieldGreetingHelp'), 'placeholder' => $greetingDefault, 'attrs' => ['maxlength' => '300']]]) ?>
                <?= view('partials/terjemahan', ['translation' => ['fields' => [
                    ['name' => 'greeting', 'label' => lang('Asisten.fieldGreeting'), 'type' => 'textarea', 'rows' => 3, 'values' => ['en' => $values['greeting_en'], 'ar' => $values['greeting_ar']], 'maxlength' => 300],
                ]]]) ?>

                <div>
                    <p class="mb-2 text-sm font-medium text-gray-900 dark:text-white"><?= lang('Asisten.fieldPicture') ?></p>
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-primary-300"><?= AvatarStorage::html('h-12 w-12') ?></span>
                        <div class="min-w-0 flex-1">
                            <label for="f-avatar" class="sr-only"><?= lang('Asisten.fieldPicture') ?></label>
                            <input id="f-avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" aria-describedby="f-avatar-help<?= isset($errors['avatar']) ? ' f-avatar-error' : '' ?>"
                                   class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                            <p id="f-avatar-help" class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.fieldPictureHelp') ?></p>
                            <?php if (isset($errors['avatar'])) : ?><p id="f-avatar-error" class="mt-1 text-sm text-red-600 dark:text-red-500"><?= esc($errors['avatar']) ?></p><?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="submit" class="<?= $primary ?>"><?= lang('Asisten.save') ?></button>
            </div>
        </section>
    </form>

    <?php if ($picture !== null) : ?>
        <form method="post" action="<?= url_to('asisten.gambar.hapus') ?>" data-confirm="<?= esc(lang('Asisten.pictureRemoveConfirm'), 'attr') ?>" class="-mt-3">
            <?= csrf_field() ?>
            <button type="submit" class="<?= $btn ?> text-red-700 dark:text-red-400"><?= icon('trash', 'h-4 w-4') ?><?= lang('Asisten.pictureRemove') ?></button>
        </form>
    <?php endif ?>

    <section class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="asisten-pilihan">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-start sm:justify-between sm:px-6 dark:border-gray-700">
            <div>
                <h2 id="asisten-pilihan" class="<?= $h2 ?>"><?= lang('Asisten.choicesTitle') ?></h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.choicesIntro') ?></p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <?php if ($custom) : ?>
                    <form method="post" action="<?= url_to('asisten.pilihan.kembalikan') ?>" data-confirm="<?= esc(lang('Asisten.choicesResetConfirm'), 'attr') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="<?= $btn ?>"><?= icon('undo', 'h-4 w-4') ?><?= lang('Asisten.choicesResetButton') ?></button>
                    </form>
                <?php endif ?>
                <?php if (! $full) : ?>
                    <a href="<?= url_to('asisten.pilihan.tambah') ?>" class="<?= $primary ?> px-4!"><?= icon('plus', 'h-4 w-4') ?><?= lang('Asisten.choiceAdd') ?></a>
                <?php endif ?>
            </div>
        </div>
        <?php if (! $custom) : ?>
            <p class="flex items-start gap-2 px-5 pt-4 text-sm text-gray-600 sm:px-6 dark:text-gray-300"><?= icon('info', 'mt-0.5 h-4 w-4 shrink-0') ?><?= lang('Asisten.choicesDefaultNote') ?></p>
        <?php endif ?>
        <?php if ($choices === []) : ?>
            <p class="p-5 text-sm text-gray-500 sm:px-6 dark:text-gray-400"><?= lang('Asisten.choicesEmpty') ?></p>
        <?php else : ?>
            <ol class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($choices as $i => $choice) : ?>
                    <?php $position = $i + 1; ?>
                    <li id="pilihan-<?= $position ?>" class="flex scroll-mt-24 flex-col gap-3 p-4 sm:px-6 lg:flex-row lg:items-center">
                        <div class="order-last flex shrink-0 gap-1 lg:order-first">
                            <?= $move($position, 'naik', $i === 0, lang('Asisten.moveUp') . ': ' . $choice['label']) ?>
                            <?= $move($position, 'turun', $i === $last, lang('Asisten.moveDown') . ': ' . $choice['label']) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 dark:text-white"><?= esc($choice['label']) ?></p>
                            <p class="mt-1 flex items-center gap-1.5 break-all text-sm text-gray-600 dark:text-gray-300">
                                <?php if ($choice['kind'] === 'link') : ?>
                                    <?= icon('link', 'h-4 w-4 shrink-0') ?><?= esc($choice['url'] ?? '') ?>
                                <?php elseif ($choice['kind'] === 'answer') : ?>
                                    <?= icon('message-circle-question-mark', 'h-4 w-4 shrink-0') ?><?= esc(mb_strimwidth((string) ($choice['answer'] ?? ''), 0, 110, '…')) ?>
                                <?php else : ?>
                                    <?= social_icon('whatsapp', 'h-4 w-4 shrink-0') ?><?= lang('Asisten.kindWhatsapp') ?>
                                <?php endif ?>
                            </p>
                            <?php if (! $choice['available']) : ?>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('Asisten.choiceHidden'), 'color' => 'yellow']]) ?>
                                    <span class="text-sm text-gray-500 dark:text-gray-400"><?= lang($choice['kind'] === 'whatsapp' ? 'Asisten.choiceNoWhatsapp' : 'Asisten.choiceUnavailable') ?></span>
                                </div>
                            <?php endif ?>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2 lg:order-last">
                            <a href="<?= url_to('asisten.pilihan.ubah', $position) ?>" class="<?= $btn ?>"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Asisten.edit') ?></a>
                            <form method="post" action="<?= url_to('asisten.pilihan.hapus', $position) ?>" data-confirm="<?= esc(lang('Asisten.choiceDeleteConfirm', [$choice['label']]), 'attr') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="<?= $btn ?> text-red-700 dark:text-red-400"><?= icon('trash', 'h-4 w-4') ?><?= lang('Asisten.delete') ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ol>
        <?php endif ?>
    </section>

    <section class="<?= $card ?>" aria-labelledby="asisten-cara">
        <h2 id="asisten-cara" class="<?= $h2 ?> flex items-center gap-2"><?= icon('shield-check', 'h-5 w-5 shrink-0 text-primary-600 dark:text-primary-400') ?><?= lang('Asisten.howTitle') ?></h2>
        <ol class="mt-4 list-decimal space-y-2 ps-5 text-sm text-gray-700 dark:text-gray-300">
            <?php foreach ((array) lang('Asisten.howItems') as $item) : ?>
                <li><?= esc($item) ?></li>
            <?php endforeach ?>
        </ol>
        <?php if (! $hasWhatsapp) : ?>
            <p class="mt-4 flex items-start gap-2 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-gray-700 dark:text-yellow-300"><?= icon('triangle-alert', 'mt-0.5 h-4 w-4 shrink-0') ?><?= lang('Asisten.noWhatsappNote') ?></p>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
