<?php
/**
 * Participants list with filters (query string, shareable).
 *
 * @var list<array<string, mixed>> $rows
 * @var Pager                      $pager
 * @var array<string, string>      $filters
 * @var array<int|string, string>  $waves    id => name
 * @var array<int|string, string>  $sessions id => label
 * @var bool                       $canAtur
 */

use CodeIgniter\Pager\Pager;
use Modules\Tes\Libraries\TestSchedule;

$input    = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$filtered = array_filter($filters, static fn (string $v): bool => $v !== '') !== [];
// The date and lists stay folded on phones unless one of them is in use.
$folded = array_filter(array_diff_key($filters, ['q' => true]), static fn (string $v): bool => $v !== '') === [];
$waveName = static fn (array $r): string => $r['wave_id'] === null ? lang('TesPenjadwalan.noWaveApplicants') : ($waves[$r['wave_id']] ?? lang('TesPenjadwalan.noWaveApplicants'));
$status   = static fn (array $r): string => $r['test_status'] ?? 'unscheduled';
$badge    = static fn (array $r): string => view('partials/status_badge', ['badge' => status_badge_for('test', $status($r))]);
$schedule = static fn (array $r): array => $r['session_id'] === null ? [] : [
    'day'  => TestSchedule::dayLabel((string) $r['test_date']),
    'time' => TestSchedule::timeLabel((string) $r['starts_at'], (string) $r['ends_at']),
    'room' => trim(($r['session_label'] ? $r['session_label'] . ' · ' : '') . $r['room_name']),
];
$select = static function (string $name, string $all, array $options) use ($filters, $input): string {
    $html = '<label for="' . $name . '" class="sr-only">' . esc($all) . '</label><select id="' . $name . '" name="' . $name . '" class="' . $input . '"><option value="">' . esc($all) . '</option>';

    foreach ($options as $value => $label) {
        $html .= '<option value="' . esc((string) $value, 'attr') . '"' . ((string) $value === $filters[$name] ? ' selected' : '') . '>' . esc($label) . '</option>';
    }

    return $html . '</select>';
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <form method="get" action="<?= url_to('tes.peserta') ?>" class="grid grid-cols-1 gap-3 border-b border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))_auto] dark:border-gray-700" role="search">
        <div class="relative sm:col-span-2 lg:col-span-4 2xl:col-span-1">
            <label for="q" class="sr-only"><?= lang('TesPenjadwalan.searchLabel') ?></label>
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-4 w-4') ?></span>
            <input type="search" id="q" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="<?= lang('TesPenjadwalan.searchLabel') ?>" class="<?= $input ?> ps-9">
        </div>
        <?php // Phones: the date and lists fold behind one button so the results start higher up the screen. ?>
        <button type="button" data-collapse-toggle="peserta-saring" aria-controls="peserta-saring" aria-expanded="<?= $folded ? 'false' : 'true' ?>"
                class="flex w-full items-center justify-between rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 sm:col-span-2 md:hidden dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
            <span><?= lang('TesPenjadwalan.filterToggle') ?></span><?= icon('chevron-down', 'h-4 w-4') ?>
        </button>
        <div id="peserta-saring" class="<?= $folded ? 'hidden ' : '' ?>sm:col-span-2 md:contents">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:contents">
                <div>
                    <label for="tanggal" class="sr-only"><?= lang('TesPenjadwalan.filterDate') ?></label>
                    <input type="date" id="tanggal" name="tanggal" value="<?= esc($filters['tanggal'], 'attr') ?>" title="<?= lang('TesPenjadwalan.filterDate') ?>" placeholder="<?= lang('TesPenjadwalan.filterDate') ?>" class="<?= $input ?>">
                </div>
                <div><?= $select('sesi', lang('TesPenjadwalan.filterSession'), $sessions) ?></div>
                <div><?= $select('status', lang('TesPenjadwalan.filterStatus'), lang('Status.test')) ?></div>
                <div><?= $select('gelombang', lang('TesPenjadwalan.filterWave'), $waves + ['tanpa' => lang('TesPenjadwalan.noWaveApplicants')]) ?></div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 sm:col-span-2 lg:col-span-4 2xl:col-span-1">
            <?php if ($filtered) : ?>
                <a href="<?= url_to('tes.peserta') ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.reset') ?></a>
            <?php endif ?>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700"><?= lang('TesPenjadwalan.filter') ?></button>
        </div>
    </form>

    <?php if ($rows === []) : ?>
        <div class="p-4">
            <?= view('partials/empty_state', ['empty' => $filtered
                ? ['title' => lang('TesPenjadwalan.emptySearchTitle'), 'text' => lang('TesPenjadwalan.emptySearchText'), 'icon' => 'search', 'image' => 'state/kosong-pencarian', 'imageAlt' => lang('App.altNoResults')]
                : ['title' => lang('TesPenjadwalan.emptyTitle'), 'text' => lang('TesPenjadwalan.emptyText'), 'icon' => 'users', 'image' => 'state/kosong-peserta', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData'), 'action' => $canAtur ? ['label' => lang('TesPenjadwalan.toAllocation'), 'url' => 'admin/tes/penjadwalan'] : null],
            ]) ?>
        </div>
    <?php else : ?>
        <div class="relative hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colNumber') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colName') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colSchedule') ?></th>
                        <th scope="col" class="px-4 py-3 text-center"><?= lang('TesPenjadwalan.colSeat') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesPenjadwalan.colStatus') ?></th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only"><?= lang('TesPenjadwalan.manageSeat') ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r) : ?>
                        <?php $s = $schedule($r); ?>
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-gray-900 dark:text-white"><?= esc($r['registration_number']) ?></td>
                            <td class="px-4 py-3">
                                <a href="<?= url_to('pendaftar.detail', $r['id']) ?>" class="font-medium text-gray-900 hover:text-primary-700 hover:underline dark:text-white dark:hover:text-primary-400"><?= esc($r['full_name']) ?></a>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?= esc($waveName($r)) ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($s === []) : ?>
                                    <span class="text-gray-400 dark:text-gray-500"><?= lang('TesPenjadwalan.none') ?></span>
                                <?php else : ?>
                                    <span class="whitespace-nowrap text-gray-900 dark:text-white"><?= esc($s['day']) ?></span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= esc($s['time']) ?> · <?= esc($s['room']) ?></p>
                                <?php endif ?>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white"><?= $r['seat_number'] === null ? '' : esc((string) $r['seat_number']) ?></td>
                            <td class="px-4 py-3"><?= $badge($r) ?></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right"><a href="<?= url_to('tes.jadwal-pendaftar', $r['id']) ?>" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesPenjadwalan.manageSeat') ?></a></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <?php // Cards in two columns from sm until the table takes over at lg, so tablets have no empty right half. ?>
        <ul class="grid grid-cols-1 sm:grid-cols-2 lg:hidden">
            <?php foreach ($rows as $r) : ?>
                <?php $s = $schedule($r); ?>
                <li class="border-b border-gray-100 p-4 last:border-b-0 sm:odd:border-r sm:[&:nth-last-child(2):nth-child(odd)]:border-b-0 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="<?= url_to('pendaftar.detail', $r['id']) ?>" class="block truncate font-medium text-gray-900 hover:underline dark:text-white"><?= esc($r['full_name']) ?></a>
                            <p class="font-mono text-sm text-gray-600 dark:text-gray-300"><?= esc($r['registration_number']) ?></p>
                        </div>
                        <?= $badge($r) ?>
                    </div>
                    <?php if ($s !== []) : ?>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300"><?= esc($s['day']) ?> · <?= esc($s['time']) ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($s['room']) ?><?= $r['seat_number'] === null ? '' : ' · ' . esc(lang('TesPenjadwalan.seatNumber', [$r['seat_number']])) ?></p>
                    <?php endif ?>
                    <a href="<?= url_to('tes.jadwal-pendaftar', $r['id']) ?>" class="mt-2 inline-block text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesPenjadwalan.manageSeat') ?></a>
                </li>
            <?php endforeach ?>
        </ul>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 p-4 sm:flex-row dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('TesPenjadwalan.total', [number_format($pager->getTotal(), 0, ',', '.')]) ?></p>
            <?= $pager->links() ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
