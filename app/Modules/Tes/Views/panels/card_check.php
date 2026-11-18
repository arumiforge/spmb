<?php
/**
 * Test section on the card check page /kartu/<token> (slot card_check), for
 * signed-in staff with tes.kehadiran: schedule, status and "Catat Hadir".
 *
 * @var int    $applicantId
 * @var string $cardState   valid | cancelled
 */

use Modules\Tes\Controllers\Kehadiran;
use Modules\Tes\Libraries\TestSchedule;

$user = auth()->loggedIn() ? auth()->user() : null;

if (empty($applicantId) || $user === null || ! $user->can('tes.kehadiran')) {
    return;
}

$schedule = (new TestSchedule())->forApplicant((int) $applicantId);
$status   = $schedule['status'] ?? 'unscheduled';
$attended = null;

if ($status === 'present') {
    $row      = model(Modules\Tes\Models\TestAssignmentModel::class)->forApplicant((int) $applicantId);
    $attended = Kehadiran::clock((string) ($row['attended_at'] ?? ''));
}
?>
<section class="mt-6 rounded-lg border border-gray-200 p-4 text-start dark:border-gray-700" aria-labelledby="panel-tes-cek">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 id="panel-tes-cek" class="font-semibold text-gray-900 dark:text-white"><?= lang('TesKehadiran.panelTitle') ?></h2>
        <?= view('partials/status_badge', ['badge' => status_badge_for('test', $status)]) ?>
    </div>
    <?php if ($schedule === null) : ?>
        <p class="text-sm text-gray-600 dark:text-gray-300"><?= lang('TesKehadiran.panelNoSchedule') ?></p>
    <?php else : ?>
        <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('TesKehadiran.panelSchedule') ?></p>
        <p class="font-medium break-words text-gray-900 dark:text-white"><?= esc($schedule['label']) ?></p>
        <?php if ($attended !== null && $attended !== '') : ?>
            <p class="mt-2 text-sm font-medium text-green-700 dark:text-green-400"><?= lang('TesKehadiran.panelAttended', [$attended]) ?></p>
        <?php elseif ($cardState === 'valid') : ?>
            <form method="post" action="<?= site_url('admin/tes/kehadiran/catat') ?>" class="mt-4">
                <?= csrf_field() ?>
                <input type="hidden" name="applicant_id" value="<?= (int) $applicantId ?>">
                <input type="hidden" name="status" value="present">
                <input type="hidden" name="kembali" value="<?= esc(uri_string(), 'attr') ?>">
                <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-5 py-3 text-base font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 dark:focus:ring-green-800">
                    <?= icon('check', 'h-5 w-5') ?><?= lang('TesKehadiran.panelRecord') ?>
                </button>
            </form>
        <?php endif ?>
        <a href="<?= site_url('admin/tes/kehadiran/sesi/' . $schedule['session_id']) ?>" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesKehadiran.panelOpenRoster') ?><?= icon('arrow-right', 'h-4 w-4') ?></a>
    <?php endif ?>
</section>
