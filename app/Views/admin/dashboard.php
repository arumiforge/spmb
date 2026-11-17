<?php
/**
 * Staff dashboard. Statistics are placeholders until the registration module (phase 2) exists.
 *
 * @var CodeIgniter\Shield\Entities\User $user
 * @var list<string>                     $roles
 * @var bool                             $twoFactorOn
 */
$stats = [
    ['label' => 'Total Pendaftar', 'icon' => 'users', 'note' => 'Semua gelombang'],
    ['label' => 'Berkas Menunggu Diperiksa', 'icon' => 'clipboard', 'note' => 'Perlu verifikasi panitia'],
    ['label' => 'Terjadwal Tes', 'icon' => 'calendar', 'note' => 'Tes pemetaan'],
    ['label' => 'Sudah Daftar Ulang', 'icon' => 'check', 'note' => 'Calon murid diterima'],
];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($stats as $stat) : ?>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($stat['label']) ?></p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white" aria-label="Belum ada data">–</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-gray-700 dark:text-primary-400"><?= icon($stat['icon'], 'h-6 w-6') ?></span>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400"><?= esc($stat['note']) ?></p>
        </div>
    <?php endforeach ?>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="xl:col-span-2">
        <?= view('partials/empty_state', ['empty' => [
            'title' => 'Data pendaftaran belum tersedia',
            'text'  => 'Ringkasan pendaftar, berkas, dan jadwal tes akan tampil di sini setelah fitur pendaftaran diaktifkan.',
            'icon'  => 'chart',
            'image' => 'state/kosong-data',
            'imageAlt' => lang('App.altNoData'),
        ]]) ?>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Akun Anda</h2>
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Peran</dt>
                <dd class="mt-1 flex flex-wrap gap-1.5">
                    <?php foreach ($roles as $role) : ?>
                        <?= view('partials/status_badge', ['badge' => ['label' => $role, 'color' => 'primary']]) ?>
                    <?php endforeach ?>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Verifikasi dua langkah</dt>
                <dd class="mt-1 flex flex-wrap items-center gap-2">
                    <?= view('partials/status_badge', ['badge' => $twoFactorOn ? ['label' => 'Aktif', 'color' => 'green'] : ['label' => 'Belum aktif', 'color' => 'yellow']]) ?>
                    <a href="<?= site_url('admin/profil/verifikasi-dua-langkah') ?>" class="font-medium text-primary-700 hover:underline dark:text-primary-400">Atur</a>
                </dd>
            </div>
        </dl>
        <a href="<?= site_url('panduan') ?>" class="mt-5 inline-flex items-center gap-2 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= icon('book', 'h-4 w-4') ?> Buka panduan penggunaan</a>
    </div>
</div>
<?= $this->endSection() ?>
