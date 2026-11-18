<?php
/**
 * Status check form: registration number + date of birth (D-008).
 *
 * @var string $example
 */
$input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-base text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$error = session('check_error');
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-md px-4 py-8 sm:py-12">
    <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= lang('Pendaftaran.checkTitle') ?></h1>
    <p class="mt-2 text-base text-gray-600 dark:text-gray-300"><?= lang('Pendaftaran.checkIntro') ?></p>

    <form method="post" action="<?= url_to('cek-status') ?>" class="mt-6 space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-xs sm:p-6 dark:border-gray-700 dark:bg-gray-800">
        <?= csrf_field() ?>
        <?php if (is_string($error)) : ?>
            <div class="flex items-start gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-gray-700 dark:text-red-400" role="alert"><?= icon('warning', 'mt-0.5 h-4 w-4 shrink-0') ?><span><?= esc($error) ?></span></div>
        <?php endif ?>
        <div>
            <label for="number" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Pendaftaran.checkNumber') ?></label>
            <input type="text" id="number" name="number" value="<?= esc((string) old('number'), 'attr') ?>" required autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="number-help" class="<?= $input ?> font-mono uppercase">
            <p id="number-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= esc(lang('Pendaftaran.checkNumberHelp', [$example])) ?></p>
        </div>
        <div>
            <label for="birth_date" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Pendaftaran.checkBirth') ?></label>
            <input type="date" id="birth_date" name="birth_date" value="<?= esc((string) old('birth_date'), 'attr') ?>" required class="<?= $input ?>">
        </div>
        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-3.5 text-base font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= icon('search', 'h-5 w-5') ?><?= lang('Pendaftaran.checkSubmit') ?></button>
    </form>

    <p class="mt-5 text-sm text-gray-600 dark:text-gray-400"><?= lang('Pendaftaran.checkForgot') ?></p>
    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400"><?= lang('Pendaftaran.checkNotYet') ?> <a href="<?= url_to('daftar') ?>" class="font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('Pendaftaran.checkRegister') ?></a></p>
</div>
<?= $this->endSection() ?>
