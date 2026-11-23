<?php
/**
 * Test rooms with capacity, location and the number of sessions held in each.
 *
 * @var list<array<string, mixed>> $rooms
 */
$button = 'inline-flex items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium';
$plain  = 'border-gray-200 text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700';
$danger = 'border-red-200 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-gray-700';
$badge  = static fn (array $r): string => view('partials/status_badge', ['badge' => (int) $r['is_active'] === 1
    ? ['label' => lang('TesJadwal.active'), 'color' => 'green']
    : ['label' => lang('TesJadwal.inactive'), 'color' => 'gray']]);
$actions = static function (array $r, bool $wide) use ($button, $plain, $danger): string {
    $grow = $wide ? '' : ' flex-1';
    $html = '<a href="' . url_to('tes.ruang.ubah', $r['id']) . '" class="' . $button . $grow . ' ' . $plain . '">' . icon('pencil', 'h-4 w-4') . lang('TesJadwal.edit') . '</a>';
    $html .= '<form method="post" class="' . trim($grow) . '" action="' . url_to((int) $r['is_active'] === 1 ? 'tes.ruang.nonaktifkan' : 'tes.ruang.aktifkan', $r['id']) . '">' . csrf_field()
        . '<button type="submit" class="' . $button . ($wide ? '' : ' w-full') . ' ' . $plain . '">' . lang((int) $r['is_active'] === 1 ? 'TesJadwal.deactivate' : 'TesJadwal.activate') . '</button></form>';

    if ($r['sessions'] === 0) {
        $html .= '<form method="post" class="' . trim($grow) . '" action="' . url_to('tes.ruang.hapus', $r['id']) . '" data-confirm="' . esc(lang('TesJadwal.roomDeleteConfirm', [$r['name']]), 'attr') . '">' . csrf_field()
            . '<button type="submit" class="' . $button . ($wide ? '' : ' w-full') . ' ' . $danger . '">' . lang('TesJadwal.delete') . '</button></form>';
    }

    return $html;
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= url_to('tes.ruang.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
    <?= icon('plus', 'h-4 w-4') ?><?= lang('TesJadwal.roomAdd') ?>
</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($rooms === []) : ?>
    <?= view('partials/empty_state', ['empty' => [
        'title'    => lang('TesJadwal.roomEmptyTitle'),
        'text'     => lang('TesJadwal.roomEmptyText'),
        'image'    => 'state/kosong-ruang', 'fallback' => 'state/kosong-data',
        'imageAlt' => lang('App.altNoData'),
        'action'   => ['label' => lang('TesJadwal.roomAdd'), 'url' => 'admin/tes/ruang/tambah'],
    ]]) ?>
<?php else : ?>
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="relative hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= lang('TesJadwal.roomName') ?></th>
                        <th scope="col" class="px-4 py-3 text-right"><?= lang('TesJadwal.roomCapacity') ?></th>
                        <th scope="col" class="px-4 py-3 text-right"><?= lang('TesJadwal.roomSessions') ?></th>
                        <th scope="col" class="px-4 py-3 text-right"><?= lang('TesJadwal.roomOrder') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('TesJadwal.colStatus') ?></th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only"><?= lang('TesJadwal.actions') ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $r) : ?>
                        <tr class="border-b border-gray-100 last:border-0 dark:border-gray-700">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 dark:text-white"><?= esc($r['name']) ?></p>
                                <?php if (! empty($r['location'])) : ?><p class="text-gray-500 dark:text-gray-400"><?= esc($r['location']) ?></p><?php endif ?>
                            </td>
                            <td class="px-4 py-3 text-right"><?= (int) $r['capacity'] ?></td>
                            <td class="px-4 py-3 text-right"><?= $r['sessions'] ?></td>
                            <td class="px-4 py-3 text-right"><?= (int) $r['sort_order'] ?></td>
                            <td class="px-4 py-3"><?= $badge($r) ?></td>
                            <td class="px-4 py-3"><div class="flex justify-end gap-2"><?= $actions($r, true) ?></div></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
            <?php foreach ($rooms as $r) : ?>
                <li class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white"><?= esc($r['name']) ?></p>
                            <?php if (! empty($r['location'])) : ?><p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($r['location']) ?></p><?php endif ?>
                        </div>
                        <?= $badge($r) ?>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        <?= lang('TesJadwal.roomCapacityLabel', [(int) $r['capacity']]) ?> · <?= lang('TesJadwal.calendarSessions', [$r['sessions']]) ?>
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2"><?= $actions($r, false) ?></div>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.roomDeactivateHint') ?></p>
<?php endif ?>
<?= $this->endSection() ?>
