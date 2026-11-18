<?php

use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestScheduleChangeModel;

/**
 * Test schedule section on the staff applicant page (/admin/pendaftar/<id>):
 * session, status, the last 5 changes and a button to give or move a seat
 * (tes.kehadiran). Renders nothing without an applicant.
 *
 * @var array<string, mixed>|null $applicant
 */
$applicant ??= null;

if (! is_array($applicant) || empty($applicant['id'])) {
    return;
}

$id       = (int) $applicant['id'];
$schedule = (new TestSchedule())->forApplicant($id);
$changes  = model(TestScheduleChangeModel::class, false)->latestForApplicant($id);
$user     = auth()->user();
$canSeat  = $user !== null && $user->can('tes.kehadiran') && ($applicant['status'] !== 'cancelled' || $schedule !== null);
?>
<section id="jadwal-tes" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="jadwal-tes-title">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 sm:px-5 dark:border-gray-700">
        <h2 id="jadwal-tes-title" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesPenjadwalan.panelTitle') ?></h2>
        <?php if ($canSeat) : ?>
            <a href="<?= url_to('tes.jadwal-pendaftar', $id) ?>" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('calendar', 'h-4 w-4') ?><?= lang($schedule === null ? 'TesPenjadwalan.giveSeat' : 'TesPenjadwalan.moveSeat') ?></a>
        <?php endif ?>
    </div>
    <div class="space-y-5 p-4 sm:p-5">
        <?= view('Modules\Tes\Views\jadwal_pendaftar\schedule', ['schedule' => $schedule, 'applicant' => $applicant], ['saveData' => false]) ?>
        <?php if ($changes !== []) : ?>
            <div>
                <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white"><?= lang('TesPenjadwalan.recentChanges') ?></h3>
                <?= view('Modules\Tes\Views\jadwal_pendaftar\changes', ['changes' => $changes], ['saveData' => false]) ?>
            </div>
        <?php endif ?>
    </div>
</section>
