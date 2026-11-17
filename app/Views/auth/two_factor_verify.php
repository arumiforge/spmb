<?php
/**
 * Second sign-in step: code from Google Authenticator or a recovery code.
 *
 * @var bool $expired
 */
?>
<?php $this->setVar('title', lang('Akun.verifyTitle')) ?>
<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('main') ?>
<div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-gray-700 dark:text-primary-400">
    <?= icon('shield', 'h-6 w-6') ?>
</div>
<h1 class="mb-1 text-2xl font-bold text-gray-900 dark:text-white"><?= lang('Akun.verifyTitle') ?></h1>

<?php if ($expired) : ?>
    <?= view('auth/alert', ['alert' => ['type' => 'warning', 'text' => lang('Akun.verifyExpired')]]) ?>
    <a href="<?= url_to('login') ?>" class="inline-flex w-full justify-center rounded-lg bg-primary-600 px-5 py-3 text-base font-medium text-white hover:bg-primary-700"><?= lang('Akun.loginButton') ?></a>
<?php else : ?>
    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400"><?= lang('Akun.verifyIntro') ?></p>

    <?php if (session('error') !== null) : ?>
        <?= view('auth/alert', ['alert' => ['type' => 'error', 'text' => session('error')]]) ?>
    <?php endif ?>

    <form action="<?= url_to('auth-action-verify') ?>" method="post" class="space-y-5">
        <?= csrf_field() ?>
        <div>
            <label for="code" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Akun.verifyCode') ?></label>
            <input type="text" name="code" id="code" required autofocus autocomplete="one-time-code" maxlength="11"
                   aria-describedby="code-help" placeholder="123456"
                   class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-center font-mono text-2xl tracking-[0.3em] text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500">
            <p id="code-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Akun.verifyCodeHelp') ?></p>
        </div>
        <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-3 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
            <?= lang('Akun.verifyButton') ?>
        </button>
    </form>
    <p class="mt-6 text-center text-sm">
        <a href="<?= url_to('logout') ?>" class="font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('Akun.verifyCancel') ?></a>
    </p>
<?php endif ?>
<?= $this->endSection() ?>
