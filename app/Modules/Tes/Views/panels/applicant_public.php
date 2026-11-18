<?php

use Modules\Tes\Libraries\TestSchedule;

/**
 * Test schedule on the applicant's own page (/cek-status/data, D-062): day,
 * time, room, location, status and the instructions when Tes.scheduleVisible
 * is on; otherwise a note that the schedule will be announced. Nothing for
 * cancelled applicants.
 *
 * @var array<string, mixed>|null $applicant
 */
$applicant ??= null;

if (! is_array($applicant) || empty($applicant['id']) || ($applicant['status'] ?? '') === 'cancelled') {
    return;
}

$reader   = new TestSchedule();
$visible  = $reader->isVisibleToApplicants();
$schedule = $visible ? $reader->forApplicant((int) $applicant['id']) : null;
$dt       = 'text-sm text-gray-500 dark:text-gray-400';
$dd       = 'mt-0.5 font-semibold text-gray-900 dark:text-white';
$notes    = trim((string) setting('Tes.instructions'));
?>
<section id="jadwal-tes" class="scroll-mt-24 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="jadwal-tes-title">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 p-4 sm:px-5 dark:border-gray-700">
        <h2 id="jadwal-tes-title" class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white"><?= icon('calendar', 'h-5 w-5 text-primary-600 dark:text-primary-400') ?><?= lang('Tes.schedule') ?></h2>
        <?= view('partials/status_badge', ['badge' => status_badge_for('test', $schedule['status'] ?? 'unscheduled')], ['saveData' => false]) ?>
    </div>
    <div class="p-4 sm:p-5">
        <?php if ($schedule === null) : ?>
            <p class="flex gap-2 text-sm text-gray-700 dark:text-gray-300"><?= icon('clock', 'h-5 w-5 shrink-0 text-gray-400') ?><?= lang($visible ? 'TesPenjadwalan.publicNotYet' : 'Tes.scheduleLater') ?></p>
        <?php else : ?>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300"><?= lang('TesPenjadwalan.publicIntro') ?></p>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="<?= $dt ?>"><?= lang('Tes.date') ?></dt><dd class="<?= $dd ?>"><?= esc($schedule['day']) ?></dd></div>
                <div><dt class="<?= $dt ?>"><?= lang('Tes.time') ?></dt><dd class="<?= $dd ?>"><?= esc($schedule['time']) ?></dd></div>
                <div><dt class="<?= $dt ?>"><?= lang('Tes.room') ?></dt><dd class="<?= $dd ?>"><?= esc($schedule['room']) ?></dd></div>
                <?php if ($schedule['location'] !== '') : ?>
                    <div><dt class="<?= $dt ?>"><?= lang('Tes.location') ?></dt><dd class="<?= $dd ?>"><?= esc($schedule['location']) ?></dd></div>
                <?php endif ?>
            </dl>
            <?php if ($notes !== '') : ?>
                <div class="mt-4 rounded-lg bg-primary-50 p-4 text-sm text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                    <p class="mb-1 font-semibold text-gray-900 dark:text-white"><?= lang('Tes.instructions') ?></p>
                    <p class="whitespace-pre-line"><?= esc($notes) ?></p>
                </div>
            <?php endif ?>
        <?php endif ?>
    </div>
</section>
