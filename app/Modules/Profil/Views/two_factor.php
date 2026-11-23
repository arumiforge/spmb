<?php
/**
 * @var bool        $enabled
 * @var bool        $required
 * @var int         $remaining
 * @var int         $total
 * @var string|null $secret  Manual key, grouped by 4 characters
 * @var string|null $qr      SVG data URI
 */
$btnPrimary   = 'rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800';
$btnSecondary = 'rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700';
$btnDanger    = 'rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php if ($enabled) : ?>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6 lg:col-span-2 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start gap-4">
                <?= icon('shield', 'h-7 w-7 shrink-0 text-green-700 dark:text-green-400') ?>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Akun.tfStatusOn') ?></h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Akun.tfStatusOnText') ?></p>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300"><?= lang('Akun.tfRecoveryLeft', [$remaining, $total]) ?></p>
                </div>
            </div>
        </section>

        <form method="post" action="<?= site_url('admin/profil/verifikasi-dua-langkah/kode-cadangan') ?>" class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
            <?= csrf_field() ?>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Akun.tfNewCodesTitle') ?></h2>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Akun.tfNewCodesText') ?></p>
            <?= view('partials/form_field', ['field' => ['name' => 'password', 'id' => 'regen_password', 'label' => lang('Akun.password'), 'type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'current-password']]]) ?>
            <?php if (is_array(session('errors')) && isset(session('errors')['regen_password'])) : ?>
                <p class="text-sm text-red-600 dark:text-red-500"><?= esc(session('errors')['regen_password']) ?></p>
            <?php endif ?>
            <button type="submit" class="<?= $btnSecondary ?>"><?= lang('Akun.tfNewCodes') ?></button>
        </form>

        <?php if (! $required) : ?>
            <form method="post" action="<?= url_to('profil.dua-langkah.matikan') ?>" data-confirm="<?= lang('Akun.tfDisableConfirm') ?>" class="space-y-4 rounded-lg border border-red-200 bg-white p-5 sm:p-6 dark:border-red-900 dark:bg-gray-800">
                <?= csrf_field() ?>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Akun.tfDisableTitle') ?></h2>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Akun.tfDisableText') ?></p>
                <?= view('partials/form_field', ['field' => ['name' => 'password', 'id' => 'disable_password', 'label' => lang('Akun.password'), 'type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'current-password']]]) ?>
                <?php if (is_array(session('errors')) && isset(session('errors')['disable_password'])) : ?>
                    <p class="text-sm text-red-600 dark:text-red-500"><?= esc(session('errors')['disable_password']) ?></p>
                <?php endif ?>
                <button type="submit" class="<?= $btnDanger ?>"><?= lang('Akun.tfDisable') ?></button>
            </form>
        <?php else : ?>
            <div class="rounded-lg border border-gray-200 bg-white p-5 text-sm text-gray-600 sm:p-6 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <?= lang('Akun.tfCannotDisable') ?>
            </div>
        <?php endif ?>
    </div>
<?php else : ?>
    <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
        <ol class="space-y-6">
            <li class="flex gap-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700 dark:bg-gray-700 dark:text-primary-300">1</span>
                <p class="pt-1 text-sm text-gray-700 dark:text-gray-300"><?= lang('Akun.tfStep1') ?></p>
            </li>
            <li class="flex gap-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700 dark:bg-gray-700 dark:text-primary-300">2</span>
                <div class="min-w-0 flex-1 pt-1">
                    <p class="text-sm text-gray-700 dark:text-gray-300"><?= lang('Akun.tfStep2') ?></p>
                    <div class="mt-4 flex flex-col items-start gap-5 sm:flex-row">
                        <img src="<?= $qr ?>" alt="<?= lang('Akun.tfQrAlt') ?>" width="200" height="200" class="h-48 w-48 shrink-0 rounded-lg border border-gray-200 bg-white p-2">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Akun.tfManualKey') ?></p>
                            <p class="mt-2 break-all rounded-lg bg-gray-100 px-3 py-2 font-mono text-base tracking-wider text-gray-900 dark:bg-gray-700 dark:text-white" id="manual-key"><?= esc($secret) ?></p>
                            <button type="button" data-copy="<?= esc(str_replace(' ', '', (string) $secret), 'attr') ?>" data-copied-label="<?= lang('App.copied') ?>" class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400">
                                <?= icon('copy', 'h-4 w-4') ?> <?= lang('App.copy') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700 dark:bg-gray-700 dark:text-primary-300">3</span>
                <form method="post" action="<?= url_to('profil.dua-langkah.aktifkan') ?>" class="min-w-0 flex-1 space-y-4 pt-1">
                    <?= csrf_field() ?>
                    <p class="text-sm text-gray-700 dark:text-gray-300"><?= lang('Akun.tfStep3') ?></p>
                    <div class="max-w-xs">
                        <?= view('partials/form_field', ['field' => ['name' => 'code', 'label' => lang('Akun.tfConfirmCode'), 'required' => true, 'placeholder' => '123456', 'attrs' => ['inputmode' => 'numeric', 'autocomplete' => 'one-time-code', 'maxlength' => '7', 'pattern' => '[0-9 ]*']]]) ?>
                    </div>
                    <button type="submit" class="<?= $btnPrimary ?>"><?= lang('Akun.tfEnable') ?></button>
                </form>
            </li>
        </ol>
    </section>
<?php endif ?>
<?= $this->endSection() ?>
