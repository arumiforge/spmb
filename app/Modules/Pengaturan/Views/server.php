<?php
/**
 * Server condition (Super Admin, D-101). Read-only.
 *
 * @var array<string, list<array{label: string, value: string, status: string, hint?: string}>> $report
 * @var string                                                                                  $overall ok|warn|bad
 * @var int                                                                                     $maxUpload bytes, 0 = no PHP limit
 */
use App\Libraries\ServerCheck;

$sections = lang('Pengaturan.serverSections');
$statuses = lang('Pengaturan.serverStatus');
$colors   = ['ok' => 'green', 'warn' => 'yellow', 'bad' => 'red', 'info' => 'gray'];
$icons    = ['upload' => 'upload', 'php' => 'cpu', 'extensions' => 'layout-grid', 'storage' => 'hard-drive', 'database' => 'database', 'app' => 'server'];
$banner   = [
    'ok'   => ['border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-gray-800 dark:text-green-300', 'circle-check', lang('Pengaturan.serverOk')],
    'warn' => ['border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-800 dark:bg-gray-800 dark:text-yellow-300', 'triangle-alert', lang('Pengaturan.serverWarn')],
    'bad'  => ['border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-gray-800 dark:text-red-300', 'circle-x', lang('Pengaturan.serverBad')],
][$overall];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="space-y-6" data-lipatan-lingkup>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="flex items-start gap-3 rounded-lg border p-5 lg:col-span-2 <?= $banner[0] ?>" role="status">
            <?= icon($banner[1], 'h-6 w-6 shrink-0') ?>
            <div>
                <p class="font-semibold"><?= esc($banner[2]) ?></p>
                <p class="mt-1 text-sm opacity-90"><?= esc(lang('Pengaturan.serverHowToFix')) ?></p>
            </div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengaturan.serverMaxUpload') ?></p>
            <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white" data-max-upload><?= esc($maxUpload === 0 ? lang('Pengaturan.serverUnlimited') : ServerCheck::formatBytes($maxUpload)) ?></p>
        </div>
    </div>

    <?= view('partials/lipatan_semua') ?>

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
        <?php foreach ($report as $key => $rows) : ?>
            <?php
            $worst = in_array('bad', array_column($rows, 'status'), true) ? 'bad' : (in_array('warn', array_column($rows, 'status'), true) ? 'warn' : 'ok');
            $badge = view('partials/status_badge', ['badge' => ['label' => $statuses[$worst], 'color' => $colors[$worst]]]);
            ?>
            <?= view('partials/lipatan_buka', ['id' => 'server-' . $key, 'title' => $sections[$key] ?? $key, 'icon' => $icons[$key] ?? 'server', 'badge' => $badge, 'open' => $key !== 'extensions' || $worst !== 'ok']) ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="sr-only">
                            <tr><th scope="col"><?= lang('Pengaturan.serverColItem') ?></th><th scope="col"><?= lang('Pengaturan.serverColValue') ?></th><th scope="col"><?= lang('Pengaturan.serverColStatus') ?></th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php foreach ($rows as $row) : ?>
                                <tr class="align-top">
                                    <th scope="row" class="py-2.5 pe-3 font-medium text-gray-900 dark:text-white">
                                        <?= esc($row['label']) ?>
                                        <?php if (! empty($row['hint'])) : ?>
                                            <span class="mt-0.5 block text-xs font-normal text-gray-500 dark:text-gray-400"><?= esc($row['hint']) ?></span>
                                        <?php endif ?>
                                    </th>
                                    <td class="break-all py-2.5 pe-3 font-mono text-xs text-gray-700 sm:text-sm dark:text-gray-300"><?= esc($row['value']) ?></td>
                                    <td class="py-2.5 text-end"><?= view('partials/status_badge', ['badge' => ['label' => $statuses[$row['status']] ?? $row['status'], 'color' => $colors[$row['status']] ?? 'gray']]) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?= view('partials/lipatan_tutup') ?>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
