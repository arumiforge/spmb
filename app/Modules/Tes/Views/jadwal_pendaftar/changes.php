<?php
/**
 * Short list of schedule changes (applicant_admin panel and seat page).
 *
 * @var list<array<string, mixed>> $changes TestScheduleChangeModel::detailed() rows
 */
if ($changes === []) : ?>
    <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('TesPenjadwalan.noChanges') ?></p>
<?php return;
endif ?>
<ol class="space-y-4 border-s border-gray-200 ps-4 dark:border-gray-700">
    <?php foreach ($changes as $c) : ?>
        <li class="relative text-sm">
            <span class="absolute -start-[1.3rem] top-1.5 h-2.5 w-2.5 rounded-full border border-white bg-gray-300 dark:border-gray-800 dark:bg-gray-600" aria-hidden="true"></span>
            <p class="font-medium text-gray-900 dark:text-white"><?= lang('Tes.change.' . $c['kind']) ?></p>
            <div class="mt-0.5"><?= view('Modules\Tes\Views\riwayat_jadwal\change', ['change' => $c], ['saveData' => false]) ?></div>
            <?php if (! empty($c['reason'])) : ?><p class="mt-0.5 text-gray-700 dark:text-gray-300">“<?= esc($c['reason']) ?>”</p><?php endif ?>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"><?= esc(tanggal_indo($c['created_at'], true)) ?> · <?= esc($c['changed_by'] === null ? lang('Tes.system') : (string) ($c['staff_name'] ?? lang('Tes.system'))) ?></p>
        </li>
    <?php endforeach ?>
</ol>
