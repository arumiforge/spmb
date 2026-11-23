<?php
/**
 * Create / edit a staff account.
 *
 * @var CodeIgniter\Shield\Entities\User|null                                     $target
 * @var array<string, array{title: string, description: string, locked: bool}>   $roleOptions
 * @var list<string>                                                               $selected
 * @var bool|null                                                                  $isSelf
 * @var bool|null                                                                  $twoFactorOn
 */
$isSelf ??= false;
$editing  = $target !== null;
$action   = $editing ? url_to('pengguna.ubah', $target->id) : url_to('pengguna.tambah');
$oldRoles = old('roles');
$checked  = is_array($oldRoles) ? $oldRoles : $selected;
$errors   = session('errors');
$name     = $editing ? ($target->full_name ?: $target->username) : '';
$card     = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$btnSec   = 'rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php if (session('temp_password') !== null) : ?>
    <div class="mb-6 rounded-lg border border-yellow-300 bg-yellow-50 p-5 dark:border-yellow-800 dark:bg-gray-800" role="status">
        <h2 class="flex items-center gap-2 font-semibold text-yellow-800 dark:text-yellow-300"><?= icon('key', 'h-5 w-5') ?><?= lang('Pengguna.tempTitle') ?></h2>
        <p class="mt-1 text-sm text-yellow-800 dark:text-yellow-200"><?= lang('Pengguna.tempText', [esc($name)]) ?></p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <code class="rounded-lg bg-white px-4 py-2 font-mono text-xl tracking-wider text-gray-900 dark:bg-gray-700 dark:text-white" data-temp-password><?= esc(session('temp_password')) ?></code>
            <button type="button" data-copy="<?= esc(session('temp_password'), 'attr') ?>" data-copied-label="<?= lang('App.copied') ?>" class="<?= $btnSec ?> inline-flex items-center gap-1.5"><?= icon('copy', 'h-4 w-4') ?><?= lang('App.copy') ?></button>
        </div>
    </div>
<?php endif ?>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <form method="post" action="<?= $action ?>" class="space-y-6 xl:col-span-2">
        <?= csrf_field() ?>
        <section class="<?= $card ?>">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><?= view('partials/form_field', ['field' => ['name' => 'full_name', 'label' => lang('Pengguna.fullName'), 'required' => true, 'value' => $target?->full_name, 'attrs' => ['autocomplete' => 'off']]]) ?></div>
                <?= view('partials/form_field', ['field' => ['name' => 'username', 'label' => lang('Pengguna.username'), 'required' => true, 'value' => $target?->username, 'help' => lang('Pengguna.usernameHelp'), 'attrs' => ['autocomplete' => 'off', 'autocapitalize' => 'none']]]) ?>
                <?= view('partials/form_field', ['field' => ['name' => 'email', 'label' => lang('Pengguna.email'), 'type' => 'email', 'required' => true, 'value' => $target?->email, 'help' => lang('Pengguna.emailHelp'), 'attrs' => ['autocomplete' => 'off']]]) ?>
            </div>
        </section>

        <fieldset class="<?= $card ?>">
            <legend class="sr-only"><?= lang('Pengguna.roles') ?></legend>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pengguna.roles') ?></h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= $isSelf ? lang('Pengguna.ownRolesLocked') : lang('Pengguna.rolesHelp') ?></p>
            <?php if (is_array($errors) && isset($errors['roles'])) : ?>
                <p class="mt-2 text-sm text-red-600 dark:text-red-500" id="roles-error"><?= esc($errors['roles']) ?></p>
            <?php endif ?>
            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <?php foreach ($roleOptions as $key => $role) : ?>
                    <?php $disabled = $role['locked'] || $isSelf; ?>
                    <li>
                        <label for="role-<?= esc($key, 'attr') ?>" class="flex h-full cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:border-gray-600 dark:has-[:checked]:bg-gray-700 <?= $disabled ? 'cursor-not-allowed opacity-60' : '' ?>">
                            <input id="role-<?= esc($key, 'attr') ?>" type="checkbox" name="roles[]" value="<?= esc($key, 'attr') ?>" <?= in_array($key, $checked, true) ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>
                                   class="mt-0.5 h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white"><?= esc($role['title']) ?></span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400"><?= esc($role['locked'] ? lang('Pengguna.roleLocked') : $role['description']) ?></span>
                            </span>
                        </label>
                    </li>
                <?php endforeach ?>
            </ul>
        </fieldset>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="<?= url_to('pengguna') ?>" class="<?= $btnSec ?> text-center"><?= lang('App.back') ?></a>
            <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Pengguna.save') ?></button>
        </div>
        <?php if (! $editing) : ?>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengguna.createHelp') ?></p>
        <?php endif ?>
    </form>

    <?php if ($editing && ! $isSelf) : ?>
        <aside class="<?= $card ?> h-fit space-y-5">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pengguna.accountTitle') ?></h2>

            <form method="post" action="<?= url_to('pengguna.reset-sandi', $target->id) ?>" data-confirm="<?= esc(lang('Pengguna.resetConfirm', [$name]), 'attr') ?>">
                <?= csrf_field() ?>
                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengguna.resetPasswordText') ?></p>
                <button type="submit" class="<?= $btnSec ?> inline-flex w-full items-center justify-center gap-2"><?= icon('key', 'h-4 w-4') ?><?= lang('Pengguna.resetPassword') ?></button>
            </form>

            <?php if ($twoFactorOn) : ?>
                <form method="post" action="<?= url_to('pengguna.reset-dua-langkah', $target->id) ?>" data-confirm="<?= esc(lang('Pengguna.resetTwoFactorConfirm', [$name]), 'attr') ?>" class="border-t border-gray-100 pt-5 dark:border-gray-700">
                    <?= csrf_field() ?>
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengguna.resetTwoFactorText') ?></p>
                    <button type="submit" class="<?= $btnSec ?> inline-flex w-full items-center justify-center gap-2"><?= icon('shield', 'h-4 w-4') ?><?= lang('Pengguna.resetTwoFactor') ?></button>
                </form>
            <?php endif ?>

            <div class="border-t border-gray-100 pt-5 dark:border-gray-700">
                <?php if ($target->isBanned()) : ?>
                    <form method="post" action="<?= url_to('pengguna.aktifkan', $target->id) ?>">
                        <?= csrf_field() ?>
                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengguna.activateText') ?></p>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300"><?= icon('check', 'h-4 w-4') ?><?= lang('Pengguna.activate') ?></button>
                    </form>
                <?php else : ?>
                    <form method="post" action="<?= url_to('pengguna.nonaktifkan', $target->id) ?>" data-confirm="<?= esc(lang('Pengguna.deactivateConfirm', [$name]), 'attr') ?>">
                        <?= csrf_field() ?>
                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengguna.deactivateText') ?></p>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300"><?= icon('ban', 'h-4 w-4') ?><?= lang('Pengguna.deactivate') ?></button>
                    </form>
                <?php endif ?>
            </div>
        </aside>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
