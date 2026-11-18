<?php
/**
 * Test sessions grouped by date, with filters and seat counts (withStats()).
 *
 * @var list<array<string, mixed>>          $sessions
 * @var array<string, array<string, mixed>> $days     date => [day, sessions, filled, quota]
 * @var array<string, string>               $filters  tanggal, gelombang, ruang, status, lewat
 * @var bool                                $filtered
 * @var bool                                $hasAny
 * @var array<int, string>                  $waves
 * @var list<array<string, mixed>>          $rooms
 * @var string                              $today
 */

use Modules\Tes\Libraries\TestSchedule;

$input  = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
$label  = 'mb-2 block text-sm font-medium text-gray-900 dark:text-white';
$button = 'inline-flex items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium';
$plain  = 'border-gray-200 text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700';
$danger = 'border-red-200 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-gray-700';
$wave   = static fn (array $s): string => $s['wave_id'] === null ? lang('Tes.allWaves') : ($waves[(int) $s['wave_id']] ?? '–');
$badges = static function (array $s) use ($today): string {
    $html = '';

    if ($s['test_date'] < $today) {
        $html .= view('partials/status_badge', ['badge' => ['label' => lang('TesJadwal.sessionPast'), 'color' => 'gray']]);
    } elseif ((int) $s['is_active'] !== 1) {
        $html .= view('partials/status_badge', ['badge' => ['label' => lang('TesJadwal.inactive'), 'color' => 'gray']]);
    } elseif ($s['free'] === 0) {
        $html .= view('partials/status_badge', ['badge' => ['label' => lang('TesJadwal.sessionFull'), 'color' => 'yellow']]);
    } else {
        $html .= view('partials/status_badge', ['badge' => ['label' => lang('TesJadwal.active'), 'color' => 'green']]);
    }

    return $html;
};
$bar = static function (array $s): string {
    $pct   = (int) $s['quota'] > 0 ? min(100, (int) round($s['filled'] * 100 / (int) $s['quota'])) : 0;
    $color = $s['free'] === 0 ? 'bg-yellow-400' : 'bg-primary-600';

    return '<div class="flex items-center gap-2"><span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">' . $s['filled'] . '/' . (int) $s['quota'] . '</span>'
        . '<div class="h-1.5 w-16 rounded-full bg-gray-200 dark:bg-gray-700" aria-hidden="true"><div class="h-1.5 rounded-full ' . $color . '" style="width: ' . $pct . '%"></div></div></div>';
};
$actions = static function (array $s, bool $wide) use ($button, $plain, $danger): string {
    $grow  = $wide ? '' : ' flex-1';
    $full  = $wide ? '' : ' w-full';
    $title = trim(($s['label'] ?? '') . ' ' . TestSchedule::sessionLabel($s));
    $html  = '<a href="' . url_to('tes.sesi.ubah', $s['id']) . '" class="' . $button . $grow . ' ' . $plain . '">' . icon('pencil', 'h-4 w-4') . lang('TesJadwal.edit') . '</a>';
    $html .= '<form method="post" class="' . trim($grow) . '" action="' . url_to((int) $s['is_active'] === 1 ? 'tes.sesi.nonaktifkan' : 'tes.sesi.aktifkan', $s['id']) . '">' . csrf_field()
        . '<button type="submit" class="' . $button . $full . ' ' . $plain . '">' . lang((int) $s['is_active'] === 1 ? 'TesJadwal.deactivate' : 'TesJadwal.activate') . '</button></form>';

    if ($s['filled'] === 0) {
        $html .= '<form method="post" class="' . trim($grow) . '" action="' . url_to('tes.sesi.hapus', $s['id']) . '" data-confirm="' . esc(lang('TesJadwal.sessionDeleteConfirm', [$title]), 'attr') . '">' . csrf_field()
            . '<button type="submit" class="' . $button . $full . ' ' . $danger . '">' . lang('TesJadwal.delete') . '</button></form>';
    }

    return $html;
};
$totalFilled = array_sum(array_column($sessions, 'filled'));
$totalQuota  = array_sum(array_map('intval', array_column($sessions, 'quota')));
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= url_to('tes.sesi.buat-banyak') ?>" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
    <?= icon('grid', 'h-4 w-4') ?><?= lang('TesJadwal.sessionGenerate') ?>
</a>
<a href="<?= url_to('tes.sesi.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
    <?= icon('plus', 'h-4 w-4') ?><?= lang('TesJadwal.sessionAdd') ?>
</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (! $hasAny) : ?>
    <?= view('partials/empty_state', ['empty' => [
        'title'    => lang('TesJadwal.sessionEmptyTitle'),
        'text'     => lang($rooms === [] ? 'TesJadwal.sessionNoRooms' : 'TesJadwal.sessionEmptyText'),
        'image'    => 'state/kosong-data',
        'imageAlt' => lang('App.altNoData'),
        'action'   => $rooms === []
            ? ['label' => lang('TesJadwal.roomAdd'), 'url' => 'admin/tes/ruang/tambah']
            : ['label' => lang('TesJadwal.sessionGenerate'), 'url' => 'admin/tes/sesi/buat-banyak'],
    ]]) ?>
<?php else : ?>
<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <form method="get" action="<?= url_to('tes.sesi') ?>" class="grid grid-cols-1 gap-4 border-b border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end dark:border-gray-700">
        <div>
            <label for="tanggal" class="<?= $label ?>"><?= lang('TesJadwal.filterDate') ?></label>
            <input type="date" id="tanggal" name="tanggal" value="<?= esc($filters['tanggal'], 'attr') ?>" class="<?= $input ?>">
        </div>
        <div>
            <label for="gelombang" class="<?= $label ?>"><?= lang('TesJadwal.filterWave') ?></label>
            <select id="gelombang" name="gelombang" class="<?= $input ?>">
                <option value=""><?= lang('TesJadwal.filterAllWaves') ?></option>
                <option value="none" <?= $filters['gelombang'] === 'none' ? 'selected' : '' ?>><?= lang('TesJadwal.filterWaveNone') ?></option>
                <?php foreach ($waves as $id => $name) : ?>
                    <option value="<?= $id ?>" <?= $filters['gelombang'] === (string) $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div>
            <label for="ruang" class="<?= $label ?>"><?= lang('TesJadwal.filterRoom') ?></label>
            <select id="ruang" name="ruang" class="<?= $input ?>">
                <option value=""><?= lang('TesJadwal.filterAllRooms') ?></option>
                <?php foreach ($rooms as $r) : ?>
                    <option value="<?= (int) $r['id'] ?>" <?= $filters['ruang'] === (string) $r['id'] ? 'selected' : '' ?>><?= esc($r['name']) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div>
            <label for="status" class="<?= $label ?>"><?= lang('TesJadwal.filterActive') ?></label>
            <select id="status" name="status" class="<?= $input ?>">
                <?php foreach (lang('TesJadwal.filterActiveOptions') as $value => $text) : ?>
                    <option value="<?= esc((string) $value, 'attr') ?>" <?= $filters['status'] === (string) $value ? 'selected' : '' ?>><?= esc($text) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 lg:flex-none"><?= lang('TesJadwal.filter') ?></button>
            <?php if ($filtered || $filters['lewat'] === '1') : ?>
                <a href="<?= url_to('tes.sesi') ?>" class="flex-1 rounded-lg border border-gray-200 px-4 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 lg:flex-none dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('TesJadwal.reset') ?></a>
            <?php endif ?>
        </div>
        <div class="sm:col-span-2 lg:col-span-5">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="lewat" value="1" <?= $filters['lewat'] === '1' ? 'checked' : '' ?> class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                <?= lang('TesJadwal.filterPast') ?>
            </label>
        </div>
    </form>

    <?php if ($sessions === []) : ?>
        <div class="p-4">
            <?= view('partials/empty_state', ['empty' => [
                'title'    => lang('TesJadwal.sessionNoMatchTitle'),
                'text'     => lang('TesJadwal.sessionNoMatchText'),
                'image'    => 'state/kosong-pencarian',
                'imageAlt' => lang('App.altNoData'),
            ]]) ?>
        </div>
    <?php else : ?>
        <p class="border-b border-gray-200 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
            <?= lang('TesJadwal.sessionCount', [count($sessions), number_format($totalFilled, 0, ',', '.'), number_format($totalQuota, 0, ',', '.')]) ?>
        </p>

        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= lang('TesJadwal.colTime') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesJadwal.colRoom') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesJadwal.colWave') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesJadwal.colFilled') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesJadwal.colStatus') ?></th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only"><?= lang('TesJadwal.actions') ?></span></th>
                    </tr>
                </thead>
                <?php foreach ($days as $date => $day) : ?>
                    <tbody>
                        <tr class="border-b border-gray-200 bg-gray-50/60 dark:border-gray-700 dark:bg-gray-900/40">
                            <th scope="rowgroup" colspan="6" class="px-4 py-2 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                <?= esc($day['day']) ?>
                                <span class="ms-2 font-normal text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.calendarSessions', [count($day['sessions'])]) ?> · <?= lang('TesJadwal.calendarSeats', [$day['filled'], $day['quota']]) ?></span>
                            </th>
                        </tr>
                        <?php foreach ($day['sessions'] as $s) : ?>
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="font-medium text-gray-900 dark:text-white"><?= esc(TestSchedule::timeLabel((string) $s['starts_at'], (string) $s['ends_at'])) ?></p>
                                    <?php if (($s['label'] ?? '') !== '') : ?><p class="text-gray-500 dark:text-gray-400"><?= esc($s['label']) ?></p><?php endif ?>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-gray-900 dark:text-white"><?= esc($s['room_name']) ?></p>
                                    <?php if (($s['room_location'] ?? '') !== '') : ?><p class="text-gray-500 dark:text-gray-400"><?= esc($s['room_location']) ?></p><?php endif ?>
                                </td>
                                <td class="px-4 py-3"><?= esc($wave($s)) ?></td>
                                <td class="px-4 py-3"><?= $bar($s) ?></td>
                                <td class="px-4 py-3"><?= $badges($s) ?></td>
                                <td class="px-4 py-3"><div class="flex justify-end gap-2"><?= $actions($s, true) ?></div></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                <?php endforeach ?>
            </table>
        </div>

        <div class="lg:hidden">
            <?php foreach ($days as $date => $day) : ?>
                <h2 class="border-b border-gray-200 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:bg-gray-900/40 dark:text-white">
                    <?= esc($day['day']) ?>
                    <span class="block text-xs font-normal text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.calendarSessions', [count($day['sessions'])]) ?> · <?= lang('TesJadwal.calendarSeats', [$day['filled'], $day['quota']]) ?></span>
                </h2>
                <ul class="divide-y divide-gray-100 border-b border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                    <?php foreach ($day['sessions'] as $s) : ?>
                        <li class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white"><?= esc(TestSchedule::timeLabel((string) $s['starts_at'], (string) $s['ends_at'])) ?></p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400"><?= esc(trim(($s['label'] ?? '') . ' · ' . $s['room_name'], ' ·')) ?></p>
                                </div>
                                <?= $badges($s) ?>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-600 dark:text-gray-400">
                                <?= $bar($s) ?>
                                <span><?= esc($wave($s)) ?></span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2"><?= $actions($s, false) ?></div>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?php endif ?>
<?= $this->endSection() ?>
