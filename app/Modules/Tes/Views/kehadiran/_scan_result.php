<?php
/**
 * Result of a scan or a typed number on the roster: a clear confirmation with
 * an undo button, or why nothing was recorded and what to do next.
 *
 * @var array{state: string, message: string, input?: string, applicant: array{id: int, number: string, name: string}|null, schedule: string|null, sessionId: int, sessionFull: bool} $scan
 *      state: marked | already | elsewhere | unscheduled | cancelled | revoked | notFound | error
 */
$styles = [
    'marked'  => ['icon' => 'check', 'box' => 'border-green-300 bg-green-50 text-green-900 dark:border-green-800 dark:bg-gray-800 dark:text-green-300', 'dot' => 'bg-green-600 text-white'],
    'already' => ['icon' => 'check', 'box' => 'border-blue-300 bg-blue-50 text-blue-900 dark:border-blue-800 dark:bg-gray-800 dark:text-blue-300', 'dot' => 'bg-blue-600 text-white'],
    'move'    => ['icon' => 'warning', 'box' => 'border-yellow-300 bg-yellow-50 text-yellow-900 dark:border-yellow-800 dark:bg-gray-800 dark:text-yellow-300', 'dot' => 'bg-yellow-400 text-yellow-900'],
    'refused' => ['icon' => 'ban', 'box' => 'border-red-300 bg-red-50 text-red-900 dark:border-red-800 dark:bg-gray-800 dark:text-red-300', 'dot' => 'bg-red-600 text-white'],
];
$state     = $scan['state'];
$style     = $styles[$state] ?? (in_array($state, ['elsewhere', 'unscheduled'], true) ? $styles['move'] : $styles['refused']);
$applicant = $scan['applicant'];
$sessionId = (int) $scan['sessionId'];
$canMove   = class_exists(Modules\Tes\Services\Scheduler::class);
$button    = 'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg px-5 py-3 text-base font-semibold focus:outline-none focus:ring-4 sm:w-auto';
?>
<div class="rounded-lg border-2 p-4 <?= $style['box'] ?>" data-scan-state="<?= esc($state, 'attr') ?>">
    <div class="flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full <?= $style['dot'] ?>"><?= icon($style['icon'], 'h-6 w-6') ?></span>
        <div class="min-w-0 flex-1">
            <p class="text-lg font-bold break-words"><?= esc($scan['message']) ?></p>
            <?php if ($applicant !== null) : ?>
                <p class="mt-1 text-sm"><span class="font-mono font-semibold"><?= esc($applicant['number']) ?></span></p>
            <?php endif ?>
            <?php if ($state === 'elsewhere' && $scan['schedule'] !== null) : ?>
                <p class="mt-1 text-sm break-words"><?= lang('TesKehadiran.scheduledAt', [esc($scan['schedule'])]) ?></p>
            <?php endif ?>
        </div>
    </div>

    <?php if ($state === 'marked' && $applicant !== null) : ?>
        <form method="post" action="<?= site_url('admin/tes/kehadiran/catat') ?>" data-attendance class="mt-3">
            <?= csrf_field() ?>
            <input type="hidden" name="applicant_id" value="<?= (int) $applicant['id'] ?>">
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            <input type="hidden" name="status" value="pending">
            <input type="hidden" name="kembali" value="admin/tes/kehadiran/sesi/<?= $sessionId ?>">
            <button type="submit" class="<?= $button ?> border border-gray-300 bg-white text-gray-900 hover:bg-gray-100 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                <?= icon('arrow-right', 'h-5 w-5 rotate-180') ?><?= lang('TesKehadiran.undo') ?>
            </button>
        </form>
    <?php elseif (in_array($state, ['elsewhere', 'unscheduled'], true) && $applicant !== null) : ?>
        <?php if ($canMove) : ?>
            <?php $ask = $scan['sessionFull'] ? lang('TesKehadiran.moveConfirmFull', [$applicant['name']]) : lang('TesKehadiran.moveConfirm', [$applicant['name']]); ?>
            <form method="post" action="<?= site_url('admin/tes/kehadiran/sesi/' . $sessionId . '/pindahkan') ?>" data-attendance data-ask="<?= esc($ask, 'attr') ?>" class="mt-3">
                <?= csrf_field() ?>
                <input type="hidden" name="applicant_id" value="<?= (int) $applicant['id'] ?>">
                <?php if ($scan['sessionFull']) : ?>
                    <input type="hidden" name="lebih_kuota" value="1">
                    <p class="mb-2 text-sm font-medium"><?= lang('TesKehadiran.moveConfirmFull', [esc($applicant['name'])]) ?></p>
                <?php endif ?>
                <button type="submit" class="<?= $button ?> bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-300 dark:focus:ring-primary-800">
                    <?= icon('arrow-right', 'h-5 w-5') ?><?= $state === 'elsewhere' ? lang('TesKehadiran.moveHere') : lang('TesKehadiran.assignHere') ?>
                </button>
            </form>
        <?php else : ?>
            <p class="mt-3 text-sm"><?= lang('TesKehadiran.moveUnavailable') ?></p>
        <?php endif ?>
    <?php endif ?>
</div>
