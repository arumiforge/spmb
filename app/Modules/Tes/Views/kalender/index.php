<?php
/**
 * Test calendar: month grid on wide screens, a list of days on phones, and the holidays.
 *
 * @var string                                  $month      Y-m
 * @var string                                  $monthLabel "Juni 2027"
 * @var string                                  $prev
 * @var string                                  $next
 * @var string                                  $thisMonth
 * @var list<list<string>>                      $weeks      dates, Monday first
 * @var array<string, array<string, mixed>>     $days       date => [sessions, filled, quota]
 * @var array<string, array<string, mixed>>     $holidays   date => row (this month)
 * @var list<array<string, mixed>>              $upcoming   holidays from this month on
 * @var string                                  $today
 * @var array{days: int, sessions: int, filled: int, quota: int} $summary
 */

use Modules\Tes\Libraries\TestSchedule;

$nav    = 'inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white p-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700';
$hm     = static fn (string $t): string => str_replace(':', '.', substr($t, 0, 5));
$fmt    = static fn (int $n): string => number_format($n, 0, ',', '.');
$isFull = static fn (array $d): bool => $d['quota'] > 0 && $d['filled'] >= $d['quota'];
$listed = array_values(array_unique(array_merge(array_keys($days), array_keys($holidays))));
sort($listed);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= url_to('tes.sesi.buat-banyak') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
    <?= icon('grid', 'h-4 w-4') ?><?= lang('TesJadwal.sessionGenerate') ?>
</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <section class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="month-title">
        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
            <div class="flex items-center gap-2">
                <a href="<?= url_to('tes.kalender') ?>?bulan=<?= esc($prev, 'url') ?>" class="<?= $nav ?>" title="<?= lang('TesJadwal.calendarPrev') ?>">
                    <span class="sr-only"><?= lang('TesJadwal.calendarPrev') ?></span><span class="rotate-180"><?= icon('chevron', 'h-4 w-4') ?></span>
                </a>
                <h2 id="month-title" class="min-w-36 text-center text-lg font-semibold text-gray-900 dark:text-white"><?= esc($monthLabel) ?></h2>
                <a href="<?= url_to('tes.kalender') ?>?bulan=<?= esc($next, 'url') ?>" class="<?= $nav ?>" title="<?= lang('TesJadwal.calendarNext') ?>">
                    <span class="sr-only"><?= lang('TesJadwal.calendarNext') ?></span><?= icon('chevron', 'h-4 w-4') ?>
                </a>
                <?php if ($month !== $thisMonth) : ?>
                    <a href="<?= url_to('tes.kalender') ?>" class="ms-1 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesJadwal.calendarToday') ?></a>
                <?php endif ?>
            </div>
            <form method="get" action="<?= url_to('tes.kalender') ?>" class="flex gap-2">
                <label for="bulan" class="sr-only"><?= lang('TesJadwal.calendarMonth') ?></label>
                <input type="month" id="bulan" name="bulan" value="<?= esc($month, 'attr') ?>" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:w-44 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"><?= lang('TesJadwal.filter') ?></button>
            </form>
        </div>

        <div class="flex flex-col gap-2 border-b border-gray-200 px-4 py-3 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700 dark:text-gray-400">
            <p><?= lang('TesJadwal.calendarSummary', [$summary['days'], $summary['sessions'], $fmt($summary['filled']), $fmt($summary['quota'])]) ?></p>
            <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs">
                <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-primary-100 ring-1 ring-primary-300 dark:bg-primary-900 dark:ring-primary-700"></span><?= lang('TesJadwal.calendarLegendTest') ?></li>
                <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-yellow-100 ring-1 ring-yellow-300 dark:bg-yellow-900 dark:ring-yellow-700"></span><?= lang('TesJadwal.calendarLegendFull') ?></li>
                <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-red-100 ring-1 ring-red-300 dark:bg-red-900 dark:ring-red-700"></span><?= lang('TesJadwal.calendarLegendHoliday') ?></li>
            </ul>
        </div>

        <!-- Month grid (tablet and wider) -->
        <div class="hidden md:block">
            <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold uppercase text-gray-600 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300" aria-hidden="true">
                <?php foreach (lang('TesJadwal.weekdaysShort') as $wd) : ?><div class="py-2"><?= esc($wd) ?></div><?php endforeach ?>
            </div>
            <div class="grid grid-cols-7">
                <?php foreach ($weeks as $week) : ?>
                    <?php foreach ($week as $date) : ?>
                        <?php
                        $inMonth = substr($date, 0, 7) === $month;
                        $day     = $inMonth ? ($days[$date] ?? null) : null;
                        $holiday = $inMonth ? ($holidays[$date] ?? null) : null;
                        $cell    = match (true) {
                            ! $inMonth                         => 'bg-gray-50 text-gray-400 dark:bg-gray-900/40 dark:text-gray-600',
                            $holiday !== null                  => 'bg-red-50 dark:bg-red-950/40',
                            $day !== null && $isFull($day)     => 'bg-yellow-50 dark:bg-yellow-950/30',
                            $day !== null                      => 'bg-primary-50 dark:bg-primary-950/30',
                            default                            => 'bg-white dark:bg-gray-800',
                        };
                        ?>
                        <div class="min-h-28 border-b border-e border-gray-200 p-2 text-sm [&:nth-child(7n)]:border-e-0 dark:border-gray-700 <?= $cell ?>">
                            <?php if ($inMonth) : ?>
                                <div class="flex items-center justify-between gap-1">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full font-semibold <?= $date === $today ? 'bg-primary-600 text-white' : 'text-gray-900 dark:text-white' ?>"><?= (int) substr($date, 8) ?></span>
                                    <?php if ($holiday === null && $date >= $today) : ?>
                                        <a href="<?= url_to('tes.sesi.tambah') ?>?tanggal=<?= $date ?>" class="rounded p-1 text-gray-400 hover:bg-white hover:text-primary-700 dark:hover:bg-gray-700 dark:hover:text-white" title="<?= lang('TesJadwal.calendarAddSession') ?>">
                                            <span class="sr-only"><?= lang('TesJadwal.calendarAddSession') ?> <?= esc(TestSchedule::dayLabel($date)) ?></span><?= icon('plus', 'h-3.5 w-3.5') ?>
                                        </a>
                                    <?php endif ?>
                                </div>
                                <?php if ($holiday !== null) : ?>
                                    <p class="mt-1 text-xs font-medium text-red-700 dark:text-red-400"><?= lang('TesJadwal.holiday') ?></p>
                                    <?php if (($holiday['note'] ?? '') !== '') : ?><p class="text-xs text-red-700/80 dark:text-red-400/80"><?= esc($holiday['note']) ?></p><?php endif ?>
                                <?php elseif ($day !== null) : ?>
                                    <a href="<?= url_to('tes.sesi') ?>?tanggal=<?= $date ?>" class="mt-1 block rounded hover:underline">
                                        <span class="block text-xs font-medium text-gray-900 dark:text-white"><?= lang('TesJadwal.calendarSessions', [count($day['sessions'])]) ?></span>
                                        <span class="block text-xs text-gray-600 dark:text-gray-400"><?= lang('TesJadwal.calendarSeats', [$day['filled'], $day['quota']]) ?></span>
                                    </a>
                                    <ul class="mt-1 hidden space-y-0.5 text-[11px] leading-tight text-gray-600 xl:block dark:text-gray-400">
                                        <?php foreach (array_slice($day['sessions'], 0, 3) as $s) : ?>
                                            <li class="truncate"><?= esc($hm((string) $s['starts_at'])) ?> · <?= esc($s['room_name']) ?> · <?= $s['filled'] ?>/<?= (int) $s['quota'] ?></li>
                                        <?php endforeach ?>
                                        <?php if (count($day['sessions']) > 3) : ?><li><?= lang('TesJadwal.genMore', [count($day['sessions']) - 3]) ?></li><?php endif ?>
                                    </ul>
                                <?php endif ?>
                            <?php else : ?>
                                <span class="flex h-7 w-7 items-center justify-center"><?= (int) substr($date, 8) ?></span>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                <?php endforeach ?>
            </div>
        </div>

        <!-- List of days (phones) -->
        <div class="md:hidden">
            <?php if ($listed === []) : ?>
                <p class="p-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.calendarNoSessions') ?></p>
            <?php else : ?>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($listed as $date) : ?>
                        <?php $day = $days[$date] ?? null; $holiday = $holidays[$date] ?? null; ?>
                        <li class="p-4 <?= $holiday !== null ? 'bg-red-50 dark:bg-red-950/40' : '' ?>">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-medium text-gray-900 dark:text-white"><?= esc(TestSchedule::dayLabel($date)) ?></p>
                                <?php if ($holiday !== null) : ?>
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('TesJadwal.holiday'), 'color' => 'red']]) ?>
                                <?php elseif ($day !== null && $isFull($day)) : ?>
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('TesJadwal.sessionFull'), 'color' => 'yellow']]) ?>
                                <?php endif ?>
                            </div>
                            <?php if ($holiday !== null) : ?>
                                <?php if (($holiday['note'] ?? '') !== '') : ?><p class="mt-1 text-sm text-red-700 dark:text-red-400"><?= esc($holiday['note']) ?></p><?php endif ?>
                            <?php else : ?>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?= lang('TesJadwal.calendarSessions', [count($day['sessions'])]) ?> · <?= lang('TesJadwal.calendarSeats', [$day['filled'], $day['quota']]) ?></p>
                                <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                    <?php foreach ($day['sessions'] as $s) : ?>
                                        <li class="flex justify-between gap-2">
                                            <span class="min-w-0 truncate"><?= esc(TestSchedule::timeLabel((string) $s['starts_at'], (string) $s['ends_at'])) ?> · <?= esc($s['room_name']) ?></span>
                                            <span class="shrink-0 font-medium"><?= $s['filled'] ?>/<?= (int) $s['quota'] ?></span>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                                <a href="<?= url_to('tes.sesi') ?>?tanggal=<?= $date ?>" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesJadwal.sessionsTitle') ?><?= icon('arrow-right', 'h-4 w-4') ?></a>
                            <?php endif ?>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2" aria-labelledby="holidays-title">
        <div class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
            <h2 id="holidays-title" class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesJadwal.holidaysTitle') ?></h2>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.holidaysIntro') ?></p>
            <form method="post" action="<?= url_to('tes.kalender.libur') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <?= view('partials/form_field', ['field' => ['name' => 'date', 'label' => lang('TesJadwal.holidayDate'), 'type' => 'date', 'required' => true]]) ?>
                <?= view('partials/form_field', ['field' => ['name' => 'note', 'label' => lang('TesJadwal.holidayNote'), 'help' => lang('TesJadwal.holidayNoteHelp'), 'attrs' => ['maxlength' => '100']]]) ?>
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800">
                    <?= icon('plus', 'h-4 w-4') ?><?= lang('TesJadwal.holidayAdd') ?>
                </button>
            </form>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <?php if ($upcoming === []) : ?>
                <p class="p-5 text-sm text-gray-500 sm:p-6 dark:text-gray-400"><?= lang('TesJadwal.holidaysEmpty') ?></p>
            <?php else : ?>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($upcoming as $h) : ?>
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= esc(TestSchedule::dayLabel((string) $h['date'])) ?></p>
                                <?php if (($h['note'] ?? '') !== '') : ?><p class="truncate text-sm text-gray-500 dark:text-gray-400"><?= esc($h['note']) ?></p><?php endif ?>
                            </div>
                            <form method="post" action="<?= url_to('tes.kalender.libur.hapus', $h['id']) ?>" data-confirm="<?= esc(lang('TesJadwal.holidayDeleteConfirm', [TestSchedule::dayLabel((string) $h['date'])]), 'attr') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-gray-700"><?= lang('TesJadwal.delete') ?></button>
                            </form>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
