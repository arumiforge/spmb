<?php
/**
 * Staff sign-in (email + password). Replaces Shield's Bootstrap view.
 */
?>
<?php $this->setVar('title', lang('Akun.loginTitle')) ?>
<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('main') ?>
<h1 class="mb-1 text-2xl font-bold text-gray-900 dark:text-white"><?= lang('Akun.loginTitle') ?></h1>
<p class="mb-6 text-sm text-gray-500 dark:text-gray-400"><?= lang('Akun.loginIntro') ?></p>

<?php if (session('error') !== null) : ?>
    <?= view('auth/alert', ['alert' => ['type' => 'error', 'text' => session('error')]]) ?>
<?php elseif (session('errors') !== null) : ?>
    <?= view('auth/alert', ['alert' => ['type' => 'error', 'text' => is_array(session('errors')) ? implode(' ', session('errors')) : session('errors')]]) ?>
<?php endif ?>
<?php if (session('message') !== null) : ?>
    <?= view('auth/alert', ['alert' => ['type' => 'success', 'text' => session('message')]]) ?>
<?php endif ?>

<form action="<?= url_to('login') ?>" method="post" class="space-y-5">
    <?= csrf_field() ?>
    <div>
        <label for="email" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Akun.email') ?></label>
        <input type="email" name="email" id="email" inputmode="email" autocomplete="email" required autofocus
               value="<?= esc((string) old('email'), 'attr') ?>" placeholder="<?= lang('Akun.emailPlaceholder') ?>"
               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500">
    </div>
    <div>
        <label for="password" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Akun.password') ?></label>
        <div class="relative">
            <input type="password" name="password" id="password" autocomplete="current-password" required
                   class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 pe-11 text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500">
            <button type="button" data-password-toggle="password" aria-pressed="false" class="absolute inset-y-0 end-0 flex items-center px-3 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                <span class="sr-only"><?= lang('App.showPassword') ?></span><?= icon('eye', 'h-5 w-5') ?>
            </button>
        </div>
    </div>
    <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
        <div class="flex items-center gap-3">
            <input id="remember" name="remember" type="checkbox" value="1" <?= old('remember') ? 'checked' : '' ?>
                   class="h-4 w-4 rounded-sm border-gray-300 bg-gray-50 text-primary-600 focus:ring-2 focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600">
            <label for="remember" class="text-sm text-gray-600 dark:text-gray-300"><?= lang('Akun.rememberMe') ?></label>
        </div>
    <?php endif ?>
    <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-3 text-center text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
        <?= lang('Akun.loginButton') ?>
    </button>
    <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Akun.forgotPassword') ?></p>
</form>

<div class="mt-6 border-t border-gray-200 pt-4 text-sm dark:border-gray-700">
    <p class="text-gray-500 dark:text-gray-400"><?= lang('Akun.notStaff') ?>
        <a href="<?= base_url('/') ?>" class="font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('Akun.backToSite') ?></a>
    </p>
</div>
<?= $this->endSection() ?>
