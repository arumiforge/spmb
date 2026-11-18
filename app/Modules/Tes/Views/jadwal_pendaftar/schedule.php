<?php
/**
 * An applicant's current test schedule as a definition list (staff pages
 * and the applicant_admin panel), or "Belum dijadwalkan".
 *
 * @var array<string, mixed>|null $schedule  TestSchedule::forApplicant()
 * @var array<string, mixed>      $applicant applicants row
 */

use Modules\Tes\Models\TestAssignmentModel;

$dt = 'text-gray-500 dark:text-gray-400';
$dd = 'mt-0.5 font-medium text-gray-900 dark:text-white';

if ($schedule === null) : ?>
    <div class="flex flex-wrap items-center gap-3">
        <?= view('partials/status_badge', ['badge' => status_badge_for('test', 'unscheduled')], ['saveData' => false]) ?>
        <p class="text-sm text-gray-600 dark:text-gray-300"><?= lang($applicant['status'] === 'cancelled' ? 'TesPenjadwalan.cancelledNoSeat' : 'TesPenjadwalan.panelNotScheduled') ?></p>
    </div>
<?php return;
endif;

$seat = model(TestAssignmentModel::class)->forApplicant((int) $applicant['id'])['seat_number'] ?? null;
?>
<?php if ($applicant['status'] === 'cancelled') : ?>
    <p class="mb-3 flex gap-2 text-sm text-red-700 dark:text-red-400"><?= icon('ban', 'h-5 w-5 shrink-0') ?><?= lang('TesPenjadwalan.cancelledNote') ?></p>
<?php endif ?>
<dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
    <div class="sm:col-span-2"><dt class="<?= $dt ?>"><?= lang('Tes.status') ?></dt><dd class="mt-1"><?= view('partials/status_badge', ['badge' => status_badge_for('test', $schedule['status'])], ['saveData' => false]) ?></dd></div>
    <div><dt class="<?= $dt ?>"><?= lang('Tes.date') ?></dt><dd class="<?= $dd ?>"><?= esc($schedule['day']) ?></dd></div>
    <div><dt class="<?= $dt ?>"><?= lang('Tes.time') ?></dt><dd class="<?= $dd ?>"><?= esc($schedule['time']) ?></dd></div>
    <div><dt class="<?= $dt ?>"><?= lang('Tes.room') ?></dt><dd class="<?= $dd ?>"><?= esc(trim(($schedule['session_label'] !== '' ? $schedule['session_label'] . ' · ' : '') . $schedule['room'])) ?></dd></div>
    <?php if ($seat !== null) : ?>
        <div><dt class="<?= $dt ?>"><?= lang('Tes.seat') ?></dt><dd class="<?= $dd ?>"><?= esc((string) $seat) ?></dd></div>
    <?php endif ?>
    <?php if ($schedule['location'] !== '') : ?>
        <div class="sm:col-span-2"><dt class="<?= $dt ?>"><?= lang('Tes.location') ?></dt><dd class="<?= $dd ?>"><?= esc($schedule['location']) ?></dd></div>
    <?php endif ?>
</dl>
