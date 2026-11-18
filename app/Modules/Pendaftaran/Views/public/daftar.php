<?php
/**
 * Public online registration form (FR-REG-01): one column on phones, big inputs.
 *
 * @var Modules\Pendaftaran\Libraries\ApplicantForm $form
 * @var array<string, mixed>|null                   $wave
 */
$card = 'rounded-xl border border-gray-200 bg-white p-5 shadow-xs sm:p-6 dark:border-gray-700 dark:bg-gray-800';
// Larger inputs and text for touch screens (public pages only).
$big = '[&_input]:py-3 [&_input]:text-base [&_select]:py-3 [&_select]:text-base [&_textarea]:text-base';
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-screen-xl px-4 py-6 sm:py-10">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="order-2 lg:order-1">
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= lang('Pendaftaran.publicFormTitle') ?></h1>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-300"><?= lang('Pendaftaran.publicFormIntro') ?></p>
            <?php if ($wave !== null) : ?>
                <p class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-800 dark:bg-gray-800 dark:text-primary-300">
                    <?= icon('calendar', 'h-4 w-4') ?><?= esc(lang('Pendaftaran.publicWave', [$wave['name'], tanggal_indo($wave['ends_on'])])) ?>
                </p>
            <?php endif ?>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.publicRequiredNote') ?></p>

            <form method="post" action="<?= url_to('daftar') ?>" class="mt-6 space-y-6 <?= $big ?>" novalidate>
                <?= csrf_field() ?>
                <div class="hidden" aria-hidden="true">
                    <label for="website"><?= lang('Pendaftaran.publicHoneypot') ?></label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <?= view('Modules\Pendaftaran\Views\partials\applicant_fields', ['applicantForm' => ['form' => $form, 'card' => $card]]) ?>

                <div class="<?= $card ?>">
                    <p class="text-sm text-gray-600 dark:text-gray-300"><?= lang('Pendaftaran.publicAgree') ?></p>
                    <button type="submit" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-6 py-3.5 text-base font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800">
                        <?= lang('Pendaftaran.publicSubmit') ?><?= icon('arrow-right', 'h-5 w-5') ?>
                    </button>
                </div>
            </form>
        </div>

        <aside class="order-1 lg:order-2">
            <div class="space-y-6 lg:sticky lg:top-24">
                <div class="flex justify-center rounded-xl bg-primary-50 p-4 dark:bg-gray-800">
                    <?= illustration('form/daftar-online', lang('Pendaftaran.altForm'), 'h-48 w-auto sm:h-64 lg:h-auto lg:w-full', false) ?>
                </div>
                <div class="<?= $card ?>">
                    <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.publicStepsTitle') ?></h2>
                    <ol class="space-y-3">
                        <?php foreach (lang('Pendaftaran.publicSteps') as $i => $step) : ?>
                            <li class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-600 text-sm font-semibold text-white"><?= $i + 1 ?></span>
                                <span class="pt-1"><?= esc($step) ?></span>
                            </li>
                        <?php endforeach ?>
                    </ol>
                </div>
            </div>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
