<?php
/**
 * Public online registration form (FR-REG-01): one form and one POST, shown as a
 * step-by-step wizard (resources/js/wizard.js): one step per form section, then
 * "Periksa dan kirim" with a summary and the consent. Without JavaScript every
 * section shows on one page. After a server-side error the first step with an
 * error opens and the stepper marks the steps that need fixing.
 *
 * @var Modules\Pendaftaran\Libraries\ApplicantForm $form
 * @var array<string, mixed>|null                   $wave
 */

use Modules\Pendaftaran\Libraries\ApplicantForm;

$card = 'rounded-xl border border-gray-200 bg-white p-5 shadow-xs sm:p-6 dark:border-gray-700 dark:bg-gray-800';
// Larger inputs and text for touch screens (public pages only).
$big    = '[&_input]:py-3 [&_input]:text-base [&_select]:py-3 [&_select]:text-base [&_textarea]:text-base';
$errors = session('errors');
$errors = is_array($errors) ? $errors : [];
$labels = lang('Pendaftaran.stepLabels');

$steps = [];

foreach (array_keys(ApplicantForm::sections($form->visible())) as $i => $section) {
    $steps[] = [
        'id'    => 'langkah-' . ($i + 1),
        'title' => lang('Pendaftaran.' . $section),
        'short' => $labels[$section] ?? lang('Pendaftaran.' . $section),
        'error' => array_filter(array_keys($errors), static fn ($key): bool => ApplicantForm::sectionOf((string) $key) === $section) !== [],
    ];
}
$review  = count($steps) + 1;
$steps[] = ['id' => 'langkah-' . $review, 'title' => lang('Pendaftaran.sectionReview'), 'short' => $labels['sectionReview'], 'error' => isset($errors['agree'])];
$first   = array_key_first(array_filter($steps, static fn (array $s): bool => $s['error']));
$current = $first === null ? 1 : $first + 1;
// After a round trip to the server every step holds data, so any step may be opened.
$filled = old('full_name') !== null;
$button = 'inline-flex items-center justify-center gap-2 rounded-lg px-5 py-3 text-base font-semibold focus:outline-none focus:ring-4';
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-screen-xl px-4 py-6 sm:py-10">
    <?php // Phones: picture, form, then the general steps; from lg the picture and steps sit on the right. ?>
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:grid-rows-[auto_1fr] xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="order-2 lg:order-none lg:col-start-1 lg:row-span-2 lg:row-start-1">
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= lang('Pendaftaran.publicFormTitle') ?></h1>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-300"><?= lang('Pendaftaran.publicFormIntro') ?></p>
            <?php if ($wave !== null) : ?>
                <p class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-800 dark:bg-gray-800 dark:text-primary-300">
                    <?= icon('calendar', 'h-4 w-4') ?><?= esc(lang('Pendaftaran.publicWave', [localized($wave, 'name'), tanggal_indo($wave['ends_on'])])) ?>
                </p>
            <?php endif ?>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.publicRequiredNote') ?></p>

            <form method="post" action="<?= url_to('daftar') ?>" class="wizard mt-6 space-y-6 <?= $big ?>" novalidate
                  data-wizard data-wizard-awal="<?= $current ?>" data-wizard-terisi="<?= $filled ? '1' : '0' ?>"
                  data-pesan-wajib="<?= esc(lang('Pendaftaran.wizardFieldRequired'), 'attr') ?>"
                  data-pesan-format="<?= esc(lang('Pendaftaran.wizardFieldInvalid'), 'attr') ?>"
                  data-pesan-centang="<?= esc(lang('Pendaftaran.agreeRequired'), 'attr') ?>">
                <?= csrf_field() ?>
                <div class="hidden" aria-hidden="true">
                    <label for="website"><?= lang('Pendaftaran.publicHoneypot') ?></label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <?= view('partials/stepper', ['stepper' => ['label' => lang('Pendaftaran.wizardLabel'), 'steps' => $steps, 'current' => $current]]) ?>

                <?= view('Modules\Pendaftaran\Views\partials\applicant_fields', ['applicantForm' => ['form' => $form, 'card' => $card, 'wizard' => true]]) ?>

                <section class="<?= $card ?> wizard-langkah" id="langkah-<?= $review ?>" data-wizard-langkah="<?= $review ?>" data-wizard-periksa
                         data-judul="<?= esc(lang('Pendaftaran.sectionReview'), 'attr') ?>" aria-labelledby="judul-langkah-<?= $review ?>"<?= isset($errors['agree']) ? ' data-ada-salah' : '' ?>>
                    <h2 id="judul-langkah-<?= $review ?>" tabindex="-1" class="mb-2 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.sectionReview') ?></h2>
                    <div class="wizard-saja">
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300"><?= lang('Pendaftaran.summaryIntro') ?></p>
                        <div class="space-y-4" data-wizard-ringkasan
                             data-teks-ubah="<?= esc(lang('Pendaftaran.summaryEdit'), 'attr') ?>"
                             data-teks-ubah-label="<?= esc(lang('Pendaftaran.summaryEditLabel'), 'attr') ?>"
                             data-teks-kosong="<?= esc(lang('Pendaftaran.summaryEmpty'), 'attr') ?>"></div>
                    </div>
                    <div class="mt-5 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                        <?= view('partials/form_field', ['field' => ['name' => 'agree', 'id' => 'f-agree', 'type' => 'checkbox', 'label' => lang('Pendaftaran.publicAgree'), 'required' => true, 'value' => '']]) ?>
                    </div>
                </section>

                <div class="wizard-aksi">
                    <button type="button" class="<?= $button ?> wizard-saja border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700" data-wizard-kembali>
                        <?= icon('arrow-left', 'h-5 w-5') ?><?= lang('Pendaftaran.wizardBack') ?>
                    </button>
                    <button type="button" class="<?= $button ?> wizard-saja bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-300 dark:focus:ring-primary-800" data-wizard-lanjut>
                        <?= lang('Pendaftaran.wizardNext') ?><?= icon('arrow-right', 'h-5 w-5') ?>
                    </button>
                    <button type="submit" class="<?= $button ?> bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-300 dark:focus:ring-primary-800" data-wizard-kirim>
                        <?= lang('Pendaftaran.publicSubmit') ?><?= icon('arrow-right', 'h-5 w-5') ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="order-1 flex justify-center lg:order-none lg:col-start-2 lg:row-start-1">
            <?= illustration('form/daftar-online', lang('Pendaftaran.altForm'), 'h-40 w-auto sm:h-56 lg:h-auto lg:w-full', false) ?>
        </div>
        <aside class="order-3 lg:order-none lg:col-start-2 lg:row-start-2" aria-labelledby="langkah-umum">
            <div class="<?= $card ?> lg:sticky lg:top-24">
                <h2 id="langkah-umum" class="mb-4 text-base font-semibold text-gray-900 dark:text-white"><?= lang('Pendaftaran.publicStepsTitle') ?></h2>
                <ol class="garis-waktu garis-waktu-ringkas">
                    <?php foreach (lang('Pendaftaran.publicSteps') as $i => $step) : ?>
                        <li class="garis-waktu-item">
                            <span class="langkah-bulat" aria-hidden="true"><span class="langkah-angka"><?= $i + 1 ?></span></span>
                            <span class="garis-waktu-isi text-sm text-gray-700 dark:text-gray-300"><span class="sr-only"><?= esc(lang('App.stepper.counter', [$i + 1, count(lang('Pendaftaran.publicSteps'))])) ?>: </span><?= esc($step) ?></span>
                        </li>
                    <?php endforeach ?>
                </ol>
            </div>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
