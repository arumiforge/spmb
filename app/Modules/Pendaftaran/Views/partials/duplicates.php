<?php
/**
 * Staff-only list of applicants that look like the same child (D-032).
 *
 * @var array{rows: list<array<string, mixed>>, link?: bool} $duplicates
 */
$reasons = lang('Pendaftaran.duplicateReasons');
?>
<ul class="divide-y divide-yellow-200 rounded-lg border border-yellow-200 bg-white dark:divide-gray-700 dark:border-gray-600 dark:bg-gray-800">
    <?php foreach ($duplicates['rows'] as $d) : ?>
        <li class="flex flex-col gap-1 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <?php if (! empty($duplicates['link'])) : ?>
                    <a href="<?= url_to('pendaftar.detail', $d['id']) ?>" class="font-medium text-gray-900 hover:underline dark:text-white" target="_blank" rel="noopener"><?= esc($d['full_name']) ?></a>
                <?php else : ?>
                    <span class="font-medium text-gray-900 dark:text-white"><?= esc($d['full_name']) ?></span>
                <?php endif ?>
                <span class="font-mono text-gray-600 dark:text-gray-300">· <?= esc($d['registration_number']) ?></span>
                <span class="text-gray-500 dark:text-gray-400">· <?= esc(tanggal_indo($d['birth_date'])) ?></span>
            </div>
            <div class="flex flex-wrap gap-1">
                <?php foreach ($d['reasons'] as $reason) : ?>
                    <?= view('partials/status_badge', ['badge' => ['label' => $reasons[$reason] ?? $reason, 'color' => 'yellow']]) ?>
                <?php endforeach ?>
            </div>
        </li>
    <?php endforeach ?>
</ul>
