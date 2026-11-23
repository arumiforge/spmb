<?php
/**
 * @var CodeIgniter\Shield\Entities\User $user
 * @var list<string>                     $roles
 * @var bool                             $twoFactorOn
 * @var bool                             $twoFactorRequired
 */
$rows = [
    lang('Akun.fullName') => $user->full_name ?: '-',
    lang('Akun.username') => $user->username,
    lang('Akun.email')    => $user->email,
    lang('Akun.lastActive') => tanggal_indo($user->last_active, true),
];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-5 flex items-center gap-4">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xl font-semibold text-white"><?= esc(initials($user->full_name ?: $user->username)) ?></span>
            <div class="min-w-0">
                <p class="truncate text-lg font-semibold text-gray-900 dark:text-white"><?= esc($user->full_name ?: $user->username) ?></p>
                <div class="mt-1 flex flex-wrap gap-1.5">
                    <?php foreach ($roles as $role) : ?>
                        <?= view('partials/status_badge', ['badge' => ['label' => $role, 'color' => 'primary']]) ?>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        <dl class="divide-y divide-gray-100 text-sm dark:divide-gray-700">
            <?php foreach ($rows as $label => $value) : ?>
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3">
                    <dt class="text-gray-500 dark:text-gray-400"><?= esc($label) ?></dt>
                    <dd class="break-words font-medium text-gray-900 sm:col-span-2 dark:text-white"><?= esc($value) ?></dd>
                </div>
            <?php endforeach ?>
        </dl>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Akun.security') ?></h2>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                <div class="flex items-center gap-3">
                    <?= icon('key', 'h-5 w-5 text-gray-500 dark:text-gray-400') ?>
                    <span class="text-sm font-medium text-gray-900 dark:text-white"><?= lang('App.changePassword') ?></span>
                </div>
                <a href="<?= url_to('profil.kata-sandi') ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('Akun.manage') ?></a>
            </li>
            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <?= icon('shield', 'h-5 w-5 text-gray-500 dark:text-gray-400') ?>
                    <span class="text-sm font-medium text-gray-900 dark:text-white"><?= lang('App.twoFactor') ?></span>
                    <?= view('partials/status_badge', ['badge' => $twoFactorOn ? ['label' => lang('Akun.twoFactorOn'), 'color' => 'green'] : ['label' => lang('Akun.twoFactorOff'), 'color' => 'yellow']]) ?>
                    <?php if ($twoFactorRequired) : ?>
                        <?= view('partials/status_badge', ['badge' => ['label' => lang('Akun.twoFactorRequired'), 'color' => 'blue']]) ?>
                    <?php endif ?>
                </div>
                <a href="<?= url_to('profil.dua-langkah') ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('Akun.manage') ?></a>
            </li>
        </ul>
    </section>
</div>
<?= $this->endSection() ?>
