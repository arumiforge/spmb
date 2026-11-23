<?php
/**
 * @var bool $forced
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="space-y-4">
    <?php if ($forced) : ?>
        <?= view('auth/alert', ['alert' => ['type' => 'warning', 'text' => lang('Akun.passwordForced')]]) ?>
    <?php endif ?>
    <form method="post" action="<?= url_to('profil.kata-sandi') ?>" class="space-y-5 rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
        <?= csrf_field() ?>
        <?php // Three fields side by side from xl, so the inputs are not stretched across a wide screen. ?>
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <?= view('partials/form_field', ['field' => ['name' => 'current_password', 'label' => lang('Akun.currentPassword'), 'type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'current-password']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'password', 'label' => lang('Akun.newPassword'), 'type' => 'password', 'required' => true, 'help' => lang('Akun.passwordIntro'), 'attrs' => ['autocomplete' => 'new-password', 'minlength' => '8']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'password_confirm', 'label' => lang('Akun.confirmPassword'), 'type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'new-password']]]) ?>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Akun.passwordSave') ?></button>
            <?php if (! $forced) : ?>
                <a href="<?= url_to('profil') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.cancel') ?></a>
            <?php endif ?>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
