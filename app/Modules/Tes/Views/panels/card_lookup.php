<?php
/**
 * Test schedule and attendance on the staff card lookup /admin/kartu (slot
 * card_lookup). Staff with tes.kehadiran can record attendance here too.
 *
 * @var array<string, mixed> $applicant applicants row
 */

use Modules\Tes\Controllers\Kehadiran;
use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestAssignmentModel;

if (empty($applicant['id'])) {
    return;
}

$user     = auth()->user();
$schedule = (new TestSchedule())->forApplicant((int) $applicant['id']);
$status   = $schedule['status'] ?? 'unscheduled';
$canMark  = $user !== null && $user->can('tes.kehadiran') && $schedule !== null && $applicant['status'] !== 'cancelled';
$row      = $schedule === null ? null : model(TestAssignmentModel::class)->forApplicant((int) $applicant['id']);
$back     = 'admin/kartu?q=' . rawurlencode((string) $applicant['registration_number']);
?>
<section class="border-t border-gray-200 p-5 dark:border-gray-700" aria-labelledby="panel-tes-cari">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 id="panel-tes-cari" class="font-semibold text-gray-900 dark:text-white"><?= lang('TesKehadiran.panelTitle') ?></h2>
        <?= view('partials/status_badge', ['badge' => status_badge_for('test', $status)]) ?>
    </div>
    <?php if ($schedule === null) : ?>
        <p class="text-sm text-gray-600 dark:text-gray-300"><?= lang('TesKehadiran.panelNoSchedule') ?></p>
    <?php else : ?>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
            <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Tes.date') ?></dt><dd class="font-medium text-gray-900 dark:text-white"><?= esc($schedule['day']) ?></dd></div>
            <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Tes.time') ?></dt><dd class="font-medium text-gray-900 dark:text-white"><?= esc($schedule['time']) ?></dd></div>
            <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Tes.room') ?></dt><dd class="font-medium break-words text-gray-900 dark:text-white"><?= esc($schedule['room']) ?><?= $schedule['location'] !== '' ? ' · ' . esc($schedule['location']) : '' ?></dd></div>
            <div><dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Tes.seat') ?></dt><dd class="font-medium text-gray-900 dark:text-white"><?= $row === null || $row['seat_number'] === null ? '–' : (int) $row['seat_number'] ?></dd></div>
        </dl>
        <?php if ($status === 'present' && ! empty($row['attended_at'])) : ?>
            <p class="mt-3 text-sm font-medium text-green-700 dark:text-green-400"><?= lang('TesKehadiran.panelAttended', [Kehadiran::clock((string) $row['attended_at'])]) ?></p>
        <?php endif ?>
        <?php if ($user !== null && $user->can('tes.kehadiran')) : ?>
            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <?php if ($canMark && $status !== 'present') : ?>
                    <form method="post" action="<?= site_url('admin/tes/kehadiran/catat') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="applicant_id" value="<?= (int) $applicant['id'] ?>">
                        <input type="hidden" name="status" value="present">
                        <input type="hidden" name="kembali" value="<?= esc($back, 'attr') ?>">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 sm:w-auto dark:focus:ring-green-800">
                            <?= icon('check', 'h-4 w-4') ?><?= lang('TesKehadiran.panelRecord') ?>
                        </button>
                    </form>
                <?php endif ?>
                <a href="<?= site_url('admin/tes/kehadiran/sesi/' . $schedule['session_id']) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesKehadiran.panelOpenRoster') ?><?= icon('arrow-right', 'h-4 w-4') ?></a>
            </div>
        <?php endif ?>
    <?php endif ?>
</section>
