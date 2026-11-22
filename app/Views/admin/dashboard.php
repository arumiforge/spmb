<?php
/**
 * Staff dashboard: registration counts for the academic year (Pendaftaran
 * module) and the signed-in account.
 *
 * @var CodeIgniter\Shield\Entities\User $user
 * @var list<string>                     $roles
 * @var bool                             $twoFactorOn
 * @var array<string, mixed>|null        $stats       see Modules\Pendaftaran\Services\ApplicantStats::summary()
 * @var bool                             $canViewList
 */
$num   = static fn (int $n): string => number_format($n, 0, ',', '.');
$card  = 'rounded-lg border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-800';
$tiles = $stats === null ? [] : [
    ['label' => 'Total Pendaftar', 'value' => $stats['total'], 'icon' => 'users', 'note' => 'Tahun ajaran ' . $stats['year'], 'url' => 'admin/pendaftar'],
    ['label' => 'Daftar Hari Ini', 'value' => $stats['today'], 'icon' => 'calendar', 'note' => tanggal_indo(date('Y-m-d')), 'url' => null],
    ['label' => 'Berkas Lengkap', 'value' => $stats['complete'], 'icon' => 'clipboard', 'note' => $stats['total'] > 0 ? round($stats['complete'] / $stats['total'] * 100) . '% dari semua pendaftar' : 'Belum ada pendaftar', 'url' => 'admin/pendaftar?berkas=complete'],
    ['label' => 'Mungkin Data Ganda', 'value' => $stats['duplicates'], 'icon' => 'warning', 'note' => 'Perlu diperiksa panitia', 'url' => 'admin/pendaftar?ganda=1'],
];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php if ($stats !== null) : ?>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($tiles as $i => $tile) : ?>
            <?php $link = $tile['url'] !== null && $canViewList; ?>
            <<?= $link ? 'a href="' . esc(site_url($tile['url']), 'attr') . '"' : 'div' ?> class="<?= $card ?> motion-rise block<?= $link ? ' motion-lift hover:border-primary-300 dark:hover:border-primary-700' : '' ?>" style="--rise-delay: <?= $i * 60 ?>ms">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($tile['label']) ?></p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white"><?= $num($tile['value']) ?></p>
                    </div>
                    <?= icon($tile['icon'], 'motion-icon h-7 w-7 shrink-0 text-primary-600 dark:text-primary-400') ?>
                </div>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400"><?= esc($tile['note']) ?></p>
            </<?= $link ? 'a' : 'div' ?>>
        <?php endforeach ?>
    </div>
<?php endif ?>

<div class="<?= $stats !== null ? 'mt-6 ' : '' ?>grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="space-y-4 xl:col-span-2">
        <?php // Modules add their own summary here (panel slot "dashboard"); each panel checks its permission. ?>
        <?= module_panels('dashboard', ['user' => $user]) ?>
        <?php if ($stats === null || $stats['total'] === 0) : ?>
            <?= view('partials/empty_state', ['empty' => [
                'title'    => 'Belum ada pendaftar',
                'text'     => $stats === null ? 'Ringkasan pendaftaran tampil di sini setelah fitur Pendaftaran diaktifkan.' : 'Ringkasan per gelombang akan tampil di sini setelah ada calon murid yang mendaftar.',
                'icon'     => 'chart',
                'image'    => 'state/kosong-data',
                'imageAlt' => lang('App.altNoData'),
            ]]) ?>
        <?php else : ?>
            <section class="<?= $card ?> motion-rise" style="--rise-delay: 200ms">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Pendaftar per gelombang</h2>
                <ul class="space-y-4">
                    <?php foreach ($stats['waves'] as $wave) : ?>
                        <?php $pct = $wave['quota'] ? min(100, (int) round($wave['count'] / $wave['quota'] * 100)) : null; ?>
                        <li>
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3 text-sm">
                                <span class="font-medium text-gray-900 dark:text-white"><?= esc($wave['name']) ?> <span class="font-normal text-gray-500 dark:text-gray-400">· <?= esc(tanggal_indo($wave['starts_on'])) ?> – <?= esc(tanggal_indo($wave['ends_on'])) ?></span></span>
                                <span class="text-gray-700 dark:text-gray-300"><?= $num($wave['count']) ?><?= $wave['quota'] ? ' / ' . $num($wave['quota']) . ' kuota' : ' pendaftar' ?></span>
                            </div>
                            <?php if ($pct !== null) : ?>
                                <div class="mt-2 h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= esc($wave['name'], 'attr') ?>">
                                    <div class="h-2.5 rounded-full bg-primary-600" style="width: <?= $pct ?>%"></div>
                                </div>
                            <?php endif ?>
                        </li>
                    <?php endforeach ?>
                    <?php if ($stats['noWave'] > 0) : ?>
                        <li class="flex justify-between text-sm"><span class="font-medium text-gray-900 dark:text-white">Tanpa gelombang</span><span class="text-gray-700 dark:text-gray-300"><?= $num($stats['noWave']) ?> pendaftar</span></li>
                    <?php endif ?>
                </ul>
            </section>
            <section class="<?= $card ?> motion-rise" style="--rise-delay: 200ms">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Cara mendaftar</h2>
                <div class="grid grid-cols-1 items-center gap-4 md:grid-cols-[minmax(0,1fr)_14rem]">
                    <?php
                    $methodChart = [
                        'chart'  => ['type' => 'donut', 'height' => 280],
                        'series' => [(int) $stats['online'], (int) $stats['offline']],
                        'labels' => ['Online', 'Di sekolah'],
                        'plotOptions' => ['pie' => ['donut' => ['labels' => ['total' => ['label' => 'Semua']]]]],
                    ];
                    ?>
                    <div class="min-h-[280px] min-w-0" data-chart="<?= esc(json_encode($methodChart, JSON_THROW_ON_ERROR), 'attr') ?>" role="img" aria-label="<?= esc('Online ' . $num($stats['online']) . ', di sekolah ' . $num($stats['offline']), 'attr') ?>"></div>
                    <dl class="grid grid-cols-2 gap-4 md:grid-cols-1">
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700"><dt class="text-sm text-gray-500 dark:text-gray-400">Online</dt><dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= $num($stats['online']) ?></dd></div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700"><dt class="text-sm text-gray-500 dark:text-gray-400">Di sekolah</dt><dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= $num($stats['offline']) ?></dd></div>
                    </dl>
                </div>
            </section>
        <?php endif ?>
    </div>
    <div class="<?= $card ?> self-start">
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

<?php if ($stats !== null && $stats['total'] > 0) : ?>
    <?= $this->section('scripts') ?>
    <script src="<?= asset_url('charts.js') ?>" defer></script>
    <?= $this->endSection() ?>
<?php endif ?>
