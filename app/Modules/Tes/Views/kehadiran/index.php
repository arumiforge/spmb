<?php
/**
 * Attendance start page (/admin/tes/kehadiran): the sessions of one day
 * (today by default) with their counts, and the other test days.
 *
 * @var array{date: string, today: string, sessions: list<array<string, mixed>>, days: array<string, int>, waves: array<int, string>} $attendance
 */

use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Services\Attendance;

$date    = $attendance['date'];
$isToday = $date === $attendance['today'];
$card    = 'rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800';
$chip    = 'inline-flex min-h-10 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium focus:outline-none focus:ring-4 focus:ring-primary-200 dark:focus:ring-primary-800';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
    <section aria-labelledby="sesi-judul">
        <h2 id="sesi-judul" class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            <?= $isToday ? lang('TesKehadiran.today') . ' · ' . esc(TestSchedule::dayLabel($date)) : lang('TesKehadiran.onDate', [esc(TestSchedule::dayLabel($date))]) ?>
        </h2>

        <?php if ($attendance['sessions'] === []) : ?>
            <?= view('partials/empty_state', ['empty' => [
                'title'    => lang('TesKehadiran.noSessions'),
                'text'     => $isToday ? lang('TesKehadiran.noToday') : lang('TesKehadiran.noOnDate'),
                'image'    => 'state/kosong-data',
                'imageAlt' => lang('App.altNoData'),
            ]]) ?>
        <?php else : ?>
            <ul class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
                <?php foreach ($attendance['sessions'] as $session) : ?>
                    <?php
                    $pct   = $session['filled'] > 0 ? (int) round($session['present'] / $session['filled'] * 100) : 0;
                    $state = null;

                    if ($isToday) {
                        $state = Attendance::hasEnded($session)
                            ? ['label' => lang('TesKehadiran.sessionEnded'), 'color' => 'gray']
                            : (Attendance::isRunning($session) ? ['label' => lang('TesKehadiran.sessionNow'), 'color' => 'green'] : ['label' => lang('TesKehadiran.sessionLater'), 'color' => 'blue']);
                    }
                    ?>
                    <li class="<?= $card ?> flex flex-col p-5">
                        <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xl font-bold text-gray-900 dark:text-white"><?= esc(TestSchedule::timeLabel((string) $session['starts_at'], (string) $session['ends_at'])) ?></p>
                                <p class="font-medium break-words text-gray-700 dark:text-gray-300"><?= esc($session['room_name']) ?><?= ($session['label'] ?? '') !== '' ? ' · ' . esc($session['label']) : '' ?></p>
                            </div>
                            <?php if ($state !== null) : ?>
                                <?= view('partials/status_badge', ['badge' => $state]) ?>
                            <?php endif ?>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?= esc($session['wave_id'] === null ? lang('Tes.allWaves') : ($attendance['waves'][(int) $session['wave_id']] ?? lang('Tes.wave'))) ?>
                            <?php if (($session['room_location'] ?? '') !== '') : ?>· <?= esc($session['room_location']) ?><?php endif ?>
                        </p>
                        <?php if (! $session['is_active']) : ?>
                            <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-400"><?= lang('TesKehadiran.inactive') ?></p>
                        <?php endif ?>

                        <div class="mt-4">
                            <div class="flex items-baseline justify-between text-sm">
                                <span class="font-medium text-gray-900 dark:text-white"><?= lang('TesKehadiran.presentOf', [$session['present'], $session['filled']]) ?></span>
                                <span class="text-gray-500 dark:text-gray-400"><?= lang('TesKehadiran.countAbsent') ?>: <?= (int) $session['absent'] ?></span>
                            </div>
                            <div class="mt-2 h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= esc(lang('TesKehadiran.countPresent'), 'attr') ?>">
                                <div class="h-2.5 rounded-full bg-green-600" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                            <a href="<?= site_url('admin/tes/kehadiran/sesi/' . (int) $session['id']) ?>" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                                <?= icon('check', 'h-5 w-5') ?><?= lang('TesKehadiran.openRoster') ?>
                            </a>
                            <a href="<?= site_url('admin/tes/sesi/' . (int) $session['id'] . '/daftar-hadir') ?>" target="_blank" rel="noopener" title="<?= esc(lang('TesKehadiran.printSheet'), 'attr') ?>" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                <?= icon('clipboard', 'h-5 w-5') ?><span class="sm:sr-only"><?= lang('TesKehadiran.printSheet') ?></span>
                            </a>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <aside class="<?= $card ?> self-start p-5" aria-labelledby="hari-judul">
        <h2 id="hari-judul" class="mb-4 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesKehadiran.otherDay') ?></h2>
        <form method="get" action="<?= site_url('admin/tes/kehadiran') ?>" class="flex flex-col gap-3">
            <div>
                <label for="tanggal" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('TesKehadiran.pickDate') ?></label>
                <input type="date" id="tanggal" name="tanggal" value="<?= esc($date, 'attr') ?>" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-base text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= icon('calendar', 'h-4 w-4') ?><?= lang('TesKehadiran.show') ?></button>
        </form>
        <?php if (! $isToday) : ?>
            <a href="<?= site_url('admin/tes/kehadiran') ?>" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesKehadiran.backToToday') ?></a>
        <?php endif ?>

        <?php if ($attendance['days'] !== []) : ?>
            <h3 class="mt-6 mb-3 text-sm font-semibold text-gray-900 dark:text-white"><?= lang('TesKehadiran.testDays') ?></h3>
            <ul class="flex flex-wrap gap-2">
                <?php foreach ($attendance['days'] as $day => $count) : ?>
                    <?php $current = $day === $date; ?>
                    <li>
                        <a href="<?= site_url('admin/tes/kehadiran?tanggal=' . $day) ?>" <?= $current ? 'aria-current="date"' : '' ?>
                           class="<?= $chip ?> <?= $current ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700' ?>">
                            <?= esc(tanggal_indo($day)) ?>
                            <span class="rounded-full px-1.5 text-xs <?= $current ? 'bg-white/20' : 'bg-gray-100 dark:bg-gray-700' ?>"><?= (int) $count ?></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </aside>
</div>
<?= $this->endSection() ?>
