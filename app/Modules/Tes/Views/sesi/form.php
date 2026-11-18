<?php
/**
 * Add or edit one test session.
 *
 * @var array<string, mixed>|null                    $session
 * @var array<string, mixed>                         $values
 * @var list<array<string, mixed>>                   $rooms
 * @var array<int, string>                           $waves
 * @var int                                          $filled  seats taken
 * @var array{count: int, fields: string}|null       $confirm set when a schedule change needs confirmation
 */
$f = static fn (string $name, string $label, array $extra = []): string => view('partials/form_field', ['field' => $extra + [
    'name'  => $name,
    'label' => $label,
    'value' => $values[$name] ?? '',
]]);
$roomOptions = [];

foreach ($rooms as $r) {
    $roomOptions[(int) $r['id']] = $r['name'] . ' (' . lang('TesJadwal.roomCapacityLabel', [(int) $r['capacity']]) . ')';
}

$waveOptions = ['' => lang('Tes.allWaves')] + $waves;
$action      = $session === null ? url_to('tes.sesi.tambah') : url_to('tes.sesi.ubah', $session['id']);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= esc($action, 'attr') ?>" class="max-w-3xl space-y-6">
    <?= csrf_field() ?>

    <?php if ($confirm !== null) : ?>
        <div class="rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-900 dark:border-yellow-800 dark:bg-gray-800 dark:text-yellow-300" role="alert">
            <p class="flex items-center gap-2 font-semibold"><?= icon('warning', 'h-5 w-5 shrink-0') ?><?= lang('TesJadwal.sessionConfirmTitle') ?></p>
            <p class="mt-2"><?= esc(lang('TesJadwal.sessionConfirmText', [$confirm['count']])) ?></p>
            <p class="mt-1"><?= esc(lang('TesJadwal.sessionChangeFields', [$confirm['fields']])) ?></p>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                <button type="submit" name="confirm" value="1" class="rounded-lg bg-yellow-400 px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-yellow-500 focus:outline-none focus:ring-4 focus:ring-yellow-300 dark:focus:ring-yellow-900"><?= lang('TesJadwal.sessionConfirmButton') ?></button>
                <a href="<?= url_to('tes.sesi.ubah', $session['id']) ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('TesJadwal.cancel') ?></a>
            </div>
        </div>
    <?php elseif ($filled > 0) : ?>
        <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900 dark:bg-gray-800 dark:text-blue-300">
            <?= icon('info', 'h-5 w-5 shrink-0') ?>
            <p><?= esc(lang('TesJadwal.sessionHasParticipants', [$filled])) ?></p>
        </div>
    <?php endif ?>

    <?php if ($rooms === []) : ?>
        <div class="flex items-start gap-3 rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-900 dark:border-yellow-800 dark:bg-gray-800 dark:text-yellow-300">
            <?= icon('warning', 'h-5 w-5 shrink-0') ?>
            <p><?= lang('TesJadwal.sessionNoRooms') ?> <a href="<?= url_to('tes.ruang.tambah') ?>" class="font-medium underline"><?= lang('TesJadwal.roomAdd') ?></a></p>
        </div>
    <?php endif ?>

    <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><?= $f('test_date', lang('TesJadwal.sessionDate'), ['type' => 'date', 'required' => true]) ?></div>
            <?= $f('starts_at', lang('TesJadwal.sessionStart'), ['type' => 'time', 'required' => true]) ?>
            <?= $f('ends_at', lang('TesJadwal.sessionEnd'), ['type' => 'time', 'required' => true]) ?>
            <?= $f('room_id', lang('TesJadwal.sessionRoom'), ['type' => 'select', 'required' => true, 'options' => $roomOptions]) ?>
            <?= $f('quota', lang('TesJadwal.sessionQuota'), ['type' => 'number', 'help' => lang('TesJadwal.sessionQuotaHelp'), 'attrs' => ['min' => (string) max(1, $filled), 'max' => '999', 'inputmode' => 'numeric']]) ?>
            <?= $f('label', lang('TesJadwal.sessionLabel'), ['help' => lang('TesJadwal.sessionLabelHelp'), 'attrs' => ['maxlength' => '60']]) ?>
            <?= $f('wave_id', lang('TesJadwal.sessionWave'), ['type' => 'select', 'options' => $waveOptions, 'help' => lang('TesJadwal.sessionWaveHelp')]) ?>
            <div class="sm:col-span-2"><?= $f('notes', lang('TesJadwal.sessionNotes'), ['type' => 'textarea', 'rows' => 2, 'attrs' => ['maxlength' => '255']]) ?></div>
            <div class="sm:col-span-2">
                <?= $f('is_active', lang('TesJadwal.sessionActive'), ['type' => 'checkbox']) ?>
                <p class="ms-7 mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.sessionActiveHelp') ?></p>
            </div>
        </div>
    </section>
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="<?= url_to('tes.sesi') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('TesJadwal.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('TesJadwal.save') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
