<?php
/**
 * Automatic allocation: counts, form (wave, start date), dry-run preview and
 * the confirm button (POST runs Scheduler::allocate()).
 *
 * @var array<int|string, string> $waves     id => name
 * @var array<string, int>        $counts    wave id ('' = none) => eligible without seat
 * @var int                       $waiting
 * @var int                       $freeSeats
 * @var int                       $openCount
 * @var int|null                  $waveId
 * @var string                    $fromDate
 * @var bool                      $dateError
 * @var array<string, mixed>|null $preview   Scheduler::allocate() dry-run report
 */
$num    = static fn (int $n): string => number_format($n, 0, ',', '.');
$card   = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2     = 'mb-4 text-lg font-semibold text-gray-900 dark:text-white';
$input  = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
$button = 'inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium';
$tiles  = [
    ['label' => lang('TesPenjadwalan.waitingTitle'), 'value' => $waiting, 'icon' => 'users', 'note' => lang('TesPenjadwalan.waitingHelp')],
    ['label' => lang('TesPenjadwalan.freeSeatsTitle'), 'value' => $freeSeats, 'icon' => 'grid', 'note' => lang('TesPenjadwalan.freeSeatsHelp', [tanggal_indo($fromDate)])],
    ['label' => lang('TesPenjadwalan.sessionsTitle'), 'value' => $openCount, 'icon' => 'clock', 'note' => lang('TesPenjadwalan.sessionsHelp')],
];
$statuses   = array_map(static fn (string $s): string => lang('Status.applicant.' . $s), array_values(array_diff((array) setting('Tes.eligibleStatuses'), ['cancelled'])));
$last       = array_pop($statuses);
$statusText = $statuses === [] ? (string) $last : implode(', ', $statuses) . ' ' . lang('TesPenjadwalan.or') . ' ' . $last;
$waveLabel  = $waveId === null ? lang('Tes.allWaves') : ($waves[$waveId] ?? lang('Tes.allWaves'));
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= site_url('admin/tes/sesi') ?>" class="<?= $button ?> whitespace-nowrap border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('clock', 'h-4 w-4') ?><?= lang('TesPenjadwalan.manageSessions') ?></a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <?php foreach ($tiles as $i => $tile) : ?>
        <div class="motion-rise rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800" style="--rise-delay: <?= $i * 60 ?>ms">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($tile['label']) ?></p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white"><?= $num($tile['value']) ?></p>
                </div>
                <?= icon($tile['icon'], 'h-7 w-7 shrink-0 text-primary-600 dark:text-primary-400') ?>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400"><?= esc($tile['note']) ?></p>
        </div>
    <?php endforeach ?>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <section class="<?= $card ?> xl:col-span-2">
        <h2 class="<?= $h2 ?>"><?= lang('TesPenjadwalan.stepOne') ?></h2>
        <form method="get" action="<?= url_to('tes.penjadwalan') ?>" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <input type="hidden" name="lihat" value="1">
            <div>
                <label for="gelombang" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Tes.wave') ?></label>
                <select id="gelombang" name="gelombang" class="<?= $input ?>" aria-describedby="gelombang-help">
                    <option value=""><?= lang('Tes.allWaves') ?></option>
                    <?php foreach ($waves as $id => $name) : ?>
                        <option value="<?= esc((string) $id, 'attr') ?>" <?= $waveId === (int) $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach ?>
                </select>
                <p id="gelombang-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesPenjadwalan.waveFieldHelp') ?></p>
            </div>
            <div>
                <label for="mulai" class="mb-2 block text-sm font-medium <?= $dateError ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white' ?>"><?= lang('TesPenjadwalan.fromDate') ?></label>
                <input type="date" id="mulai" name="mulai" value="<?= esc($fromDate, 'attr') ?>" required class="<?= $input ?>" aria-describedby="mulai-help"<?= $dateError ? ' aria-invalid="true"' : '' ?>>
                <p id="mulai-help" class="mt-2 text-sm <?= $dateError ? 'text-red-600 dark:text-red-500' : 'text-gray-500 dark:text-gray-400' ?>"><?= $dateError ? lang('TesPenjadwalan.err.dateInvalid') : lang('TesPenjadwalan.fromDateHelp') ?></p>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="<?= $button ?> w-full bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= icon('eye', 'h-4 w-4') ?><?= lang('TesPenjadwalan.previewButton') ?></button>
            </div>
        </form>
    </section>

    <section class="<?= $card ?>">
        <h2 class="<?= $h2 ?>"><?= lang('TesPenjadwalan.rulesTitle') ?></h2>
        <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
            <?php foreach (lang('TesPenjadwalan.rules') as $rule) : ?>
                <li class="flex gap-2"><span class="mt-0.5 shrink-0 text-primary-600 dark:text-primary-400"><?= icon('check', 'h-4 w-4') ?></span><span><?= esc($rule) ?></span></li>
            <?php endforeach ?>
        </ul>
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400"><?= esc(lang('TesPenjadwalan.eligibleNote', [$statusText, setting('Tes.requireCompleteDocuments') ? lang('TesPenjadwalan.eligibleDocsNote') : ''])) ?></p>
        <?php if ($counts !== []) : ?>
            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white"><?= lang('TesPenjadwalan.waitingPerWave') ?></h3>
            <dl class="divide-y divide-gray-100 text-sm dark:divide-gray-700">
                <?php foreach ($counts as $id => $total) : ?>
                    <div class="flex justify-between gap-3 py-1.5">
                        <dt class="text-gray-600 dark:text-gray-300"><?= esc($id === '' ? lang('TesPenjadwalan.noWaveApplicants') : ($waves[$id] ?? lang('TesPenjadwalan.noWaveApplicants'))) ?></dt>
                        <dd class="font-semibold text-gray-900 dark:text-white"><?= $num($total) ?></dd>
                    </div>
                <?php endforeach ?>
            </dl>
        <?php endif ?>
    </section>
</div>

<?php if ($preview !== null) : ?>
    <section id="hasil" class="mt-6 scroll-mt-24 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="hasil-title">
        <div class="border-b border-gray-200 p-5 sm:px-6 dark:border-gray-700">
            <h2 id="hasil-title" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesPenjadwalan.stepTwo') ?></h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300"><?= esc($waveLabel) ?> · <?= lang('TesPenjadwalan.fromDate') ?> <?= esc(tanggal_indo($fromDate)) ?></p>
        </div>

        <?php if ($preview['eligible'] === 0) : ?>
            <div class="p-4">
                <?= view('partials/empty_state', ['empty' => ['title' => lang('TesPenjadwalan.previewEmpty'), 'text' => lang('TesPenjadwalan.previewEmptyText'), 'image' => 'state/kosong-penjadwalan', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData')]]) ?>
            </div>
        <?php else : ?>
            <div class="space-y-3 p-5 sm:px-6">
                <p class="text-sm text-gray-600 dark:text-gray-300"><?= lang('TesPenjadwalan.previewIntro') ?></p>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-green-100 px-2.5 py-1 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-300"><?= icon('check', 'h-4 w-4') ?><?= esc(lang('TesPenjadwalan.previewPlaced', [$num($preview['placed'])])) ?></span>
                    <?php if ($preview['unplaced'] > 0) : ?>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-yellow-100 px-2.5 py-1 text-sm font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300"><?= icon('warning', 'h-4 w-4') ?><?= esc(lang('TesPenjadwalan.previewUnplaced', [$num($preview['unplaced'])])) ?></span>
                    <?php endif ?>
                </div>
            </div>

            <?php if ($preview['sessions'] !== []) : ?>
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-6 py-3"><?= lang('TesPenjadwalan.colSession') ?></th>
                                <th scope="col" class="px-6 py-3 text-right"><?= lang('TesPenjadwalan.colCount') ?></th>
                                <th scope="col" class="px-6 py-3"><?= lang('TesPenjadwalan.colNumbers') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview['sessions'] as $s) : ?>
                                <tr class="border-b border-gray-100 last:border-0 dark:border-gray-700">
                                    <td class="px-6 py-3">
                                        <?php if ($s['session_label'] !== '') : ?><span class="font-medium text-gray-900 dark:text-white"><?= esc($s['session_label']) ?></span> · <?php endif ?><?= esc($s['label']) ?>
                                    </td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white"><?= $num($s['count']) ?></td>
                                    <td class="whitespace-nowrap px-6 py-3 font-mono"><?= esc($s['first_number']) ?><?= $s['count'] > 1 ? ' – ' . esc($s['last_number']) : '' ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <ul class="divide-y divide-gray-100 border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
                    <?php foreach ($preview['sessions'] as $s) : ?>
                        <li class="p-4 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 text-gray-900 dark:text-white"><?php if ($s['session_label'] !== '') : ?><span class="font-medium"><?= esc($s['session_label']) ?></span><br><?php endif ?><?= esc($s['label']) ?></p>
                                <span class="shrink-0 rounded-md bg-primary-50 px-2 py-0.5 font-semibold text-primary-700 dark:bg-gray-700 dark:text-primary-400"><?= $num($s['count']) ?></span>
                            </div>
                            <p class="mt-1 break-all font-mono text-gray-600 dark:text-gray-400"><?= esc($s['first_number']) ?><?= $s['count'] > 1 ? ' – ' . esc($s['last_number']) : '' ?></p>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>

            <?php if ($preview['unplaced'] > 0) : ?>
                <div class="m-5 rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-900 sm:mx-6 dark:border-yellow-800 dark:bg-gray-800 dark:text-yellow-300" role="status">
                    <p class="flex gap-2 font-medium"><?= icon('warning', 'h-5 w-5 shrink-0') ?><?= lang('TesPenjadwalan.unplacedHint') ?></p>
                    <details class="mt-2">
                        <summary class="cursor-pointer font-medium"><?= lang('TesPenjadwalan.unplacedList') ?> (<?= $num($preview['unplaced']) ?>)</summary>
                        <p class="mt-2 break-words font-mono text-xs leading-relaxed"><?= esc(implode(', ', $preview['unplacedNumbers'])) ?></p>
                    </details>
                </div>
            <?php endif ?>

            <?php if ($preview['placed'] > 0) : ?>
                <form method="post" action="<?= url_to('tes.penjadwalan') ?>" class="border-t border-gray-200 p-5 sm:px-6 dark:border-gray-700" data-confirm="<?= esc(lang('TesPenjadwalan.confirmText', [$num($preview['placed'])]), 'attr') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="gelombang" value="<?= esc((string) ($waveId ?? ''), 'attr') ?>">
                    <input type="hidden" name="mulai" value="<?= esc($fromDate, 'attr') ?>">
                    <button type="submit" class="<?= $button ?> w-full bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800"><?= icon('calendar', 'h-4 w-4') ?><?= lang('TesPenjadwalan.confirmButton') ?></button>
                </form>
            <?php endif ?>
        <?php endif ?>
    </section>
<?php endif ?>
<?= $this->endSection() ?>
