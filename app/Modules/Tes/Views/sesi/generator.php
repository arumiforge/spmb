<?php
/**
 * Session generator: form (days, slots, rooms, wave, label) and its preview.
 *
 * @var array<string, mixed>        $form      date_from, date_to, weekdays, slots, rooms, wave_id, label
 * @var array<string, mixed>|null   $preview   SessionGenerator::preview() without errors
 * @var array<string, string>       $genErrors
 * @var list<array<string, mixed>>  $rooms     active rooms
 * @var array<int, string>          $waves
 * @var int                         $duration  default session length (minutes)
 */

use Modules\Tes\Libraries\SessionGenerator;

$input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
$label = 'mb-2 block text-sm font-medium text-gray-900 dark:text-white';
$card  = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2    = 'mb-4 text-lg font-semibold text-gray-900 dark:text-white';
$check = 'h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700';
$err   = static fn (string $key): string => isset($genErrors[$key])
    ? '<p class="mt-2 text-sm text-red-600 dark:text-red-500" id="err-' . $key . '">' . esc($genErrors[$key]) . '</p>'
    : '';
$fmt  = static fn (int $n): string => number_format($n, 0, ',', '.');
$cap  = 20;
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="space-y-6">
<?php if ($preview !== null) : ?>
    <section class="<?= $card ?>" aria-labelledby="preview-title">
        <h2 id="preview-title" class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesJadwal.genPreviewTitle') ?></h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.genPreviewIntro') ?></p>

        <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <?php foreach ([
                'genStatDays'     => count($preview['days']),
                'genStatSessions' => $preview['count'],
                'genStatSeats'    => $preview['seats'],
                'genStatSkipped'  => count($preview['skippedHolidays']) + count($preview['skippedOverlaps']),
            ] as $key => $value) : ?>
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                    <dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.' . $key) ?></dt>
                    <dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= $fmt((int) $value) ?></dd>
                </div>
            <?php endforeach ?>
        </dl>

        <?php if ($preview['count'] === 0) : ?>
            <div class="mt-4 flex items-start gap-3 rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-900 dark:border-yellow-800 dark:bg-gray-800 dark:text-yellow-300">
                <?= icon('warning', 'h-5 w-5 shrink-0') ?>
                <p><strong><?= lang('TesJadwal.genNothingTitle') ?>.</strong> <?= lang('TesJadwal.genNothing') ?></p>
            </div>
        <?php else : ?>
            <div class="mt-4 max-h-80 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                    <thead class="sticky top-0 bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-4 py-2"><?= lang('TesJadwal.genColDay') ?></th>
                            <th scope="col" class="px-4 py-2 text-right"><?= lang('TesJadwal.genColSessions') ?></th>
                            <th scope="col" class="px-4 py-2 text-right"><?= lang('TesJadwal.genColSeats') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview['days'] as $day) : ?>
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="px-4 py-2 text-gray-900 dark:text-white"><?= esc($day['day']) ?></td>
                                <td class="px-4 py-2 text-right"><?= $day['sessions'] ?></td>
                                <td class="px-4 py-2 text-right"><?= $fmt($day['seats']) ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>

        <?php if ($preview['skippedHolidays'] !== []) : ?>
            <h3 class="mb-2 mt-5 text-sm font-semibold text-gray-900 dark:text-white"><?= lang('TesJadwal.genSkippedHolidays') ?></h3>
            <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <?php foreach ($preview['skippedHolidays'] as $h) : ?>
                    <li><?= esc($h['day']) ?><?= $h['note'] !== '' ? ' · ' . esc($h['note']) : '' ?></li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <?php if ($preview['skippedOverlaps'] !== []) : ?>
            <h3 class="mb-2 mt-5 text-sm font-semibold text-gray-900 dark:text-white"><?= lang('TesJadwal.genSkippedOverlaps') ?></h3>
            <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <?php foreach (array_slice($preview['skippedOverlaps'], 0, $cap) as $o) : ?>
                    <li><?= esc(lang('TesJadwal.genOverlapLine', [$o['day'], $o['time'], $o['room'], $o['conflict']])) ?></li>
                <?php endforeach ?>
                <?php if (count($preview['skippedOverlaps']) > $cap) : ?>
                    <li><?= lang('TesJadwal.genMore', [count($preview['skippedOverlaps']) - $cap]) ?></li>
                <?php endif ?>
            </ul>
        <?php endif ?>

        <?php if ($preview['count'] > 0) : ?>
            <?php $in = $preview['input']; ?>
            <form method="post" action="<?= url_to('tes.sesi.buat-banyak') ?>" class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end" data-confirm="<?= esc(lang('TesJadwal.genCreateConfirm', [$preview['count']]), 'attr') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="date_from" value="<?= esc($in['date_from'], 'attr') ?>">
                <input type="hidden" name="date_to" value="<?= esc($in['date_to'], 'attr') ?>">
                <?php foreach ($in['weekdays'] as $d) : ?><input type="hidden" name="weekdays[]" value="<?= (int) $d ?>"><?php endforeach ?>
                <?php foreach ($in['slots'] as $slot) : ?>
                    <input type="hidden" name="slot_start[]" value="<?= esc(substr($slot['start'], 0, 5), 'attr') ?>">
                    <input type="hidden" name="slot_end[]" value="<?= esc(substr($slot['end'], 0, 5), 'attr') ?>">
                <?php endforeach ?>
                <?php foreach ($in['rooms'] as $r) : ?><input type="hidden" name="rooms[]" value="<?= (int) $r ?>"><?php endforeach ?>
                <input type="hidden" name="wave_id" value="<?= esc((string) ($in['wave_id'] ?? ''), 'attr') ?>">
                <input type="hidden" name="label" value="<?= esc($in['label'], 'attr') ?>">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                    <?= icon('check', 'h-4 w-4') ?><?= lang('TesJadwal.genCreate', [$fmt($preview['count'])]) ?>
                </button>
            </form>
        <?php endif ?>
    </section>
<?php endif ?>

<?php if ($rooms === []) : ?>
    <div class="flex items-start gap-3 rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-900 dark:border-yellow-800 dark:bg-gray-800 dark:text-yellow-300">
        <?= icon('warning', 'h-5 w-5 shrink-0') ?>
        <p><?= lang('TesJadwal.sessionNoRooms') ?> <a href="<?= url_to('tes.ruang.tambah') ?>" class="font-medium underline"><?= lang('TesJadwal.roomAdd') ?></a></p>
    </div>
<?php endif ?>

<form method="post" action="<?= url_to('tes.sesi.pratinjau') ?>" class="form-lebar" data-session-generator data-duration="<?= (int) $duration ?>">
    <?= csrf_field() ?>

    <section class="<?= $card ?>">
        <h2 class="<?= $h2 ?>"><?= lang('TesJadwal.genStepDays') ?></h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="date_from" class="<?= $label ?>"><?= lang('TesJadwal.genDateFrom') ?></label>
                <input type="date" id="date_from" name="date_from" required value="<?= esc((string) $form['date_from'], 'attr') ?>" class="<?= $input ?>">
                <?= $err('date_from') ?>
            </div>
            <div>
                <label for="date_to" class="<?= $label ?>"><?= lang('TesJadwal.genDateTo') ?></label>
                <input type="date" id="date_to" name="date_to" required value="<?= esc((string) $form['date_to'], 'attr') ?>" class="<?= $input ?>">
                <?= $err('date_to') ?>
            </div>
            <fieldset class="sm:col-span-2">
                <legend class="<?= $label ?>"><?= lang('TesJadwal.genWeekdays') ?></legend>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                    <?php foreach (lang('TesJadwal.weekdayNames') as $n => $name) : ?>
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-900 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:border-gray-600 dark:text-gray-300 dark:has-[:checked]:bg-gray-700">
                            <input type="checkbox" name="weekdays[]" value="<?= (int) $n ?>" <?= in_array((int) $n, $form['weekdays'], true) ? 'checked' : '' ?> class="<?= $check ?>">
                            <?= esc($name) ?>
                        </label>
                    <?php endforeach ?>
                </div>
                <?= $err('weekdays') ?>
            </fieldset>
        </div>
    </section>

    <section class="<?= $card ?>">
        <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesJadwal.genStepSlots') ?></h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= esc(lang('TesJadwal.genSlotsHelp', [$duration])) ?></p>
        <ol class="space-y-3" data-slots>
            <?php foreach ($form['slots'] as $i => $slot) : ?>
                <li class="grid grid-cols-[1fr_1fr_auto] items-end gap-3" data-slot>
                    <div>
                        <label class="<?= $label ?>" for="slot-start-<?= $i ?>"><span data-slot-title><?= lang('TesJadwal.genSlotN', [$i + 1]) ?></span> · <?= lang('TesJadwal.genSlotStart') ?></label>
                        <input type="time" id="slot-start-<?= $i ?>" name="slot_start[]" value="<?= esc($slot['start'], 'attr') ?>" class="<?= $input ?>" data-slot-start>
                    </div>
                    <div>
                        <label class="<?= $label ?>" for="slot-end-<?= $i ?>"><?= lang('TesJadwal.genSlotEnd') ?></label>
                        <input type="time" id="slot-end-<?= $i ?>" name="slot_end[]" value="<?= esc($slot['end'], 'attr') ?>" class="<?= $input ?>" data-slot-end>
                    </div>
                    <button type="button" class="mb-0.5 rounded-lg border border-gray-200 p-2.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white" data-slot-remove>
                        <span class="sr-only"><?= lang('TesJadwal.genRemoveSlot') ?></span><?= icon('x', 'h-4 w-4') ?>
                    </button>
                </li>
            <?php endforeach ?>
        </ol>
        <?= $err('slots') ?>
        <button type="button" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" data-slot-add data-max="<?= SessionGenerator::MAX_SLOTS ?>">
            <?= icon('plus', 'h-4 w-4') ?><?= lang('TesJadwal.genAddSlot') ?>
        </button>
    </section>

    <section class="<?= $card ?>">
        <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesJadwal.genStepRooms') ?></h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.genRoomsHelp') ?></p>
        <fieldset>
            <legend class="sr-only"><?= lang('TesJadwal.genStepRooms') ?></legend>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <?php foreach ($rooms as $r) : ?>
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 px-3 py-2.5 text-sm has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:border-gray-600 dark:has-[:checked]:bg-gray-700">
                        <input type="checkbox" name="rooms[]" value="<?= (int) $r['id'] ?>" <?= in_array((int) $r['id'], $form['rooms'], true) ? 'checked' : '' ?> class="mt-0.5 <?= $check ?>">
                        <span>
                            <span class="block font-medium text-gray-900 dark:text-white"><?= esc($r['name']) ?></span>
                            <span class="block text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.roomCapacityLabel', [(int) $r['capacity']]) ?><?= ($r['location'] ?? '') !== '' ? ' · ' . esc($r['location']) : '' ?></span>
                        </span>
                    </label>
                <?php endforeach ?>
            </div>
        </fieldset>
        <?= $err('rooms') ?>
    </section>

    <section class="<?= $card ?>">
        <h2 class="<?= $h2 ?>"><?= lang('TesJadwal.genStepOther') ?></h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="wave_id" class="<?= $label ?>"><?= lang('TesJadwal.sessionWave') ?></label>
                <select id="wave_id" name="wave_id" class="<?= $input ?>" aria-describedby="wave-help">
                    <option value=""><?= lang('Tes.allWaves') ?></option>
                    <?php foreach ($waves as $id => $name) : ?>
                        <option value="<?= $id ?>" <?= (string) ($form['wave_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach ?>
                </select>
                <p id="wave-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesJadwal.sessionWaveHelp') ?></p>
                <?= $err('wave_id') ?>
            </div>
            <div>
                <label for="label" class="<?= $label ?>"><?= lang('TesJadwal.genLabel') ?></label>
                <input type="text" id="label" name="label" maxlength="60" value="<?= esc((string) $form['label'], 'attr') ?>" class="<?= $input ?>" aria-describedby="label-help">
                <p id="label-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= esc(lang('TesJadwal.genLabelHelp')) ?></p>
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="<?= url_to('tes.sesi') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('TesJadwal.cancel') ?></a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= icon('eye', 'h-4 w-4') ?><?= lang('TesJadwal.genPreview') ?></button>
    </div>
</form>
</div>

<script<?= csp_script_nonce() !== '' ? ' ' . csp_script_nonce() : '' ?>>
/* Time slots: add/remove rows, and fill the end time from the default session length. */
(() => {
    const form = document.querySelector('[data-session-generator]');
    if (!form) return;
    const list = form.querySelector('[data-slots]');
    const add = form.querySelector('[data-slot-add]');
    const duration = parseInt(form.dataset.duration, 10) || 120;
    const title = <?= json_encode(lang('TesJadwal.genSlotN', ['{n}']), JSON_UNESCAPED_UNICODE) ?>;
    const plus = (time) => {
        const [h, m] = time.split(':').map((v) => parseInt(v, 10));
        if (Number.isNaN(h) || Number.isNaN(m)) return '';
        const total = Math.min(23 * 60 + 59, h * 60 + m + duration);
        return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
    };
    const renumber = () => {
        const rows = list.querySelectorAll('[data-slot]');
        rows.forEach((row, i) => {
            row.querySelector('[data-slot-title]').textContent = title.replace('{n}', String(i + 1));
            row.querySelector('[data-slot-remove]').hidden = rows.length === 1;
        });
        add.hidden = rows.length >= parseInt(add.dataset.max, 10);
    };
    list.addEventListener('change', (event) => {
        if (!event.target.matches('[data-slot-start]')) return;
        const end = event.target.closest('[data-slot]').querySelector('[data-slot-end]');
        if (end.value === '' || end.dataset.auto === '1') {
            end.value = plus(event.target.value);
            end.dataset.auto = '1';
        }
    });
    list.addEventListener('input', (event) => {
        if (event.target.matches('[data-slot-end]')) event.target.dataset.auto = '0';
    });
    list.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-slot-remove]');
        if (!remove) return;
        remove.closest('[data-slot]').remove();
        renumber();
    });
    add.addEventListener('click', () => {
        const rows = list.querySelectorAll('[data-slot]');
        const last = rows[rows.length - 1];
        const row = last.cloneNode(true);
        const id = 'slot-' + Date.now();
        const start = row.querySelector('[data-slot-start]');
        const end = row.querySelector('[data-slot-end]');
        start.id = id + '-start';
        end.id = id + '-end';
        row.querySelectorAll('label')[0].htmlFor = start.id;
        row.querySelectorAll('label')[1].htmlFor = end.id;
        const previousEnd = last.querySelector('[data-slot-end]').value;
        start.value = previousEnd;
        end.value = previousEnd ? plus(previousEnd) : '';
        end.dataset.auto = '1';
        list.appendChild(row);
        renumber();
        start.focus();
    });
    renumber();
})();
</script>
<?= $this->endSection() ?>
