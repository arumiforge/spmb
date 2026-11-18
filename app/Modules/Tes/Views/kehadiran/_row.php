<?php
/**
 * One participant on the session roster, with big Hadir / Tidak hadir buttons
 * (plain forms; the roster script posts them with fetch and swaps this row).
 *
 * @var array{row: array<string, mixed>, sessionId: int} $entry
 *      row: TestAssignmentModel::forSession() row
 */

use Modules\Tes\Controllers\Kehadiran;

$row       = $entry['row'];
$sessionId = (int) $entry['sessionId'];
$status    = (string) $row['status'];
$action    = site_url('admin/tes/kehadiran/catat');
$back      = 'admin/tes/kehadiran/sesi/' . $sessionId;
$gender    = lang('Status.gender.' . $row['gender']);
$search    = mb_strtolower($row['registration_number'] . ' ' . $row['full_name']);
$base      = 'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-base font-semibold focus:outline-none focus:ring-4';
$styles    = [
    'present' => [
        true  => 'bg-green-600 text-white ring-2 ring-green-600 ring-offset-2 hover:bg-green-700 focus:ring-green-300 dark:ring-offset-gray-800 dark:focus:ring-green-800',
        false => 'border border-green-600 bg-white text-green-700 hover:bg-green-50 focus:ring-green-200 dark:border-green-500 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700 dark:focus:ring-green-900',
    ],
    'absent' => [
        true  => 'bg-red-600 text-white ring-2 ring-red-600 ring-offset-2 hover:bg-red-700 focus:ring-red-300 dark:ring-offset-gray-800 dark:focus:ring-red-900',
        false => 'border border-red-600 bg-white text-red-700 hover:bg-red-50 focus:ring-red-200 dark:border-red-500 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700 dark:focus:ring-red-900',
    ],
];
$button = static function (string $value, string $label, string $icon) use ($action, $back, $row, $sessionId, $status, $base, $styles): string {
    $active = $status === $value;

    return '<form method="post" action="' . esc($action, 'attr') . '" data-attendance>'
        . csrf_field()
        . '<input type="hidden" name="applicant_id" value="' . (int) $row['applicant_id'] . '">'
        . '<input type="hidden" name="session_id" value="' . $sessionId . '">'
        . '<input type="hidden" name="status" value="' . esc($value, 'attr') . '">'
        . '<input type="hidden" name="kembali" value="' . esc($back, 'attr') . '">'
        . '<button type="submit" aria-pressed="' . ($active ? 'true' : 'false') . '" class="' . $base . ' ' . $styles[$value][$active] . '">'
        . icon($icon, 'h-5 w-5') . esc($label)
        . '</button></form>';
};
?>
<li id="peserta-<?= (int) $row['applicant_id'] ?>" data-roster-row data-search="<?= esc($search, 'attr') ?>" class="flex flex-col gap-3 p-4 md:flex-row md:items-center md:gap-4">
    <div class="flex min-w-0 flex-1 items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-200" title="<?= esc(lang('Tes.seat'), 'attr') ?>">
            <?= $row['seat_number'] === null ? '–' : (int) $row['seat_number'] ?>
        </span>
        <div class="min-w-0">
            <p class="font-mono text-sm font-semibold text-primary-700 dark:text-primary-400"><?= esc($row['registration_number']) ?></p>
            <p class="text-base font-semibold break-words text-gray-900 dark:text-white"><?= esc($row['full_name']) ?></p>
            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                <span><?= esc($gender) ?></span>
                <?= view('partials/status_badge', ['badge' => status_badge_for('test', $status)]) ?>
                <?php if ($status === 'present' && ! empty($row['attended_at'])) : ?>
                    <span><?= lang('TesKehadiran.presentAt', [Kehadiran::clock((string) $row['attended_at'])]) ?></span>
                <?php endif ?>
            </p>
        </div>
    </div>
    <div class="flex flex-col gap-2 md:w-80 md:shrink-0">
        <div class="grid grid-cols-2 gap-2">
            <?= $button('present', lang('TesKehadiran.present'), 'check') ?>
            <?= $button('absent', lang('TesKehadiran.absent'), 'x') ?>
        </div>
        <?php if (in_array($status, ['present', 'absent'], true)) : ?>
            <form method="post" action="<?= esc($action, 'attr') ?>" data-attendance class="self-end">
                <?= csrf_field() ?>
                <input type="hidden" name="applicant_id" value="<?= (int) $row['applicant_id'] ?>">
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                <input type="hidden" name="status" value="pending">
                <input type="hidden" name="kembali" value="<?= esc($back, 'attr') ?>">
                <button type="submit" title="<?= esc(lang('TesKehadiran.undoHint'), 'attr') ?>" class="inline-flex min-h-10 items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                    <?= icon('arrow-right', 'h-4 w-4 rotate-180') ?><?= lang('TesKehadiran.undo') ?>
                </button>
            </form>
        <?php endif ?>
    </div>
</li>
