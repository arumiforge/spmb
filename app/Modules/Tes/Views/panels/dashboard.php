<?php
/**
 * Today's test sessions on the staff dashboard (slot dashboard), for staff
 * with tes.kehadiran; with tes.atur also the number of applicants without a seat.
 *
 * @var CodeIgniter\Shield\Entities\User $user
 */

use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestSessionModel;

if (! isset($user) || ! $user->can('tes.kehadiran')) {
    return;
}

$sessions    = model(TestSessionModel::class)->withStats(['date' => date('Y-m-d')]);
$unscheduled = null;

if ($user->can('tes.atur')) {
    $unscheduled = db_connect()->table('applicants ap')
        ->join('test_assignments a', 'a.applicant_id = ap.id', 'left')
        ->where('ap.academic_year', (string) setting('Pendaftaran.academicYear'))
        ->where('ap.deleted_at', null)
        ->where('ap.status <>', 'cancelled')
        ->where('a.id', null)
        ->countAllResults();
}
?>
<?= lipatan_buka(['id' => 'tes-hari-ini', 'title' => lang('TesKehadiran.dashTitle'), 'icon' => 'clipboard-check', 'class' => 'shadow-xs']) ?>
    <div class="mb-4 flex justify-end">
        <a href="<?= site_url('admin/tes/kehadiran') ?>" class="inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesKehadiran.dashOpen') ?><?= icon('arrow-right', 'h-4 w-4') ?></a>
    </div>
    <?php if ($sessions === []) : ?>
        <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('TesKehadiran.dashNone') ?></p>
    <?php else : ?>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($sessions as $session) : ?>
                <?php $pct = $session['filled'] > 0 ? (int) round($session['present'] / $session['filled'] * 100) : 0; ?>
                <li>
                    <a href="<?= site_url('admin/tes/kehadiran/sesi/' . (int) $session['id']) ?>" class="block py-3 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-3 text-sm">
                            <span class="font-medium text-gray-900 dark:text-white"><?= esc(TestSchedule::timeLabel((string) $session['starts_at'], (string) $session['ends_at'])) ?> · <?= esc($session['room_name']) ?></span>
                            <span class="text-gray-700 dark:text-gray-300"><?= lang('TesKehadiran.presentOf', [$session['present'], $session['filled']]) ?></span>
                        </div>
                        <div class="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= esc($session['room_name'], 'attr') ?>">
                            <div class="h-2 rounded-full bg-green-600" style="width: <?= $pct ?>%"></div>
                        </div>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <?php if ($unscheduled !== null) : ?>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-700">
            <span class="text-gray-700 dark:text-gray-300"><?= lang('TesKehadiran.dashUnscheduled') ?>: <strong class="text-gray-900 dark:text-white"><?= number_format($unscheduled, 0, ',', '.') ?></strong></span>
            <?php if ($unscheduled > 0) : ?>
                <a href="<?= site_url('admin/tes/penjadwalan') ?>" class="font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesKehadiran.dashUnscheduledLink') ?></a>
            <?php endif ?>
        </div>
    <?php endif ?>
<?= lipatan_tutup() ?>
