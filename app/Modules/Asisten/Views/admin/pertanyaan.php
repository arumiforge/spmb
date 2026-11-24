<?php
/**
 * Website → Pertanyaan pengunjung (asisten.atur): counts per answer kind, the most common questions
 * Tanya Jawab did not answer ("Jadikan tanya jawab"), the latest questions and the retention setting.
 *
 * @var int                                                                     $range
 * @var string                                                                  $kind
 * @var array<string, int>                                                      $counts
 * @var list<array{question: string, count: int, last: string, kinds: list<string>}> $top
 * @var list<array<string, mixed>>                                              $rows
 * @var CodeIgniter\Pager\Pager                                                 $pager
 * @var bool                                                                    $canQa
 * @var string                                                                  $retention
 */

use Modules\Asisten\Controllers\Pertanyaan;

$num    = static fn (int $n): string => number_format($n, 0, ',', '.');
$card   = 'rounded-lg border border-gray-200 bg-white p-5 shadow-xs sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2     = 'text-lg font-semibold text-gray-900 dark:text-white';
$btn    = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$colors = ['chip' => 'blue', 'qa' => 'green', 'ai' => 'primary', 'ai_unknown' => 'yellow', 'none' => 'gray', 'limit' => 'yellow', 'error' => 'red'];
$badge  = static fn (string $k): string => view('partials/status_badge', ['badge' => ['label' => lang('Asisten.kind.' . $k), 'color' => $colors[$k] ?? 'gray']], ['saveData' => false]);
$total  = array_sum($counts);
$tiles  = [
    'qa'   => ['label' => lang('Asisten.tileQa'), 'value' => $counts['qa']],
    'chip' => ['label' => lang('Asisten.tileChip'), 'value' => $counts['chip']],
    'ai'   => ['label' => lang('Asisten.tileAi'), 'value' => $counts['ai'] + $counts['ai_unknown']],
    'open' => ['label' => lang('Asisten.tileOpen'), 'value' => $counts['ai_unknown'] + $counts['none'] + $counts['limit'] + $counts['error']],
];
$query = static fn (array $params): string => site_url('admin/asisten/pertanyaan') . '?' . http_build_query(array_filter($params, static fn ($v): bool => $v !== '' && $v !== null));
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600 dark:text-gray-300"><?= lang('Asisten.logTotal', [$num($total), $range]) ?></p>
        <nav aria-label="<?= esc(lang('Asisten.rangeLabel'), 'attr') ?>" class="inline-flex rounded-lg shadow-xs">
            <?php foreach (Pertanyaan::RANGES as $j => $days) : ?>
                <?php $active = $days === $range; ?>
                <a href="<?= esc($query(['rentang' => $days, 'jenis' => $kind]), 'attr') ?>"<?= $active ? ' aria-current="page"' : '' ?>
                   class="inline-flex min-h-10 items-center border px-4 text-sm font-medium focus:z-10 focus:ring-2 focus:ring-primary-500 focus:outline-none <?= $j === 0 ? 'rounded-s-lg' : '' ?> <?= $j === count(Pertanyaan::RANGES) - 1 ? 'rounded-e-lg' : '' ?> <?= $j > 0 ? '-ms-px' : '' ?> <?= $active ? 'z-10 border-primary-600 bg-primary-600 text-white dark:border-primary-500' : 'border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700' ?>"><?= lang('Asisten.rangeDays', [$days]) ?></a>
            <?php endforeach ?>
        </nav>
    </div>

    <h2 class="sr-only"><?= lang('Asisten.tilesLabel') ?></h2>
    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <?php foreach ($tiles as $tile) : ?>
            <div class="min-w-0 rounded-lg border border-gray-200 bg-white p-4 shadow-xs sm:p-5 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($tile['label']) ?></p>
                <p class="mt-2 text-2xl font-bold text-gray-900 tabular-nums sm:text-3xl dark:text-white"><?= $num($tile['value']) ?></p>
            </div>
        <?php endforeach ?>
    </div>

    <?php if ($total === 0) : ?>
        <?= view('partials/empty_state', ['empty' => [
            'title' => lang('Asisten.logEmptyTitle'), 'text' => lang('Asisten.logEmptyText'), 'icon' => 'inbox',
            'image' => 'state/kosong-pertanyaan', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData'),
        ]]) ?>
    <?php else : ?>
        <section class="<?= $card ?>" aria-labelledby="asisten-teratas">
            <h2 id="asisten-teratas" class="<?= $h2 ?>"><?= lang('Asisten.topTitle') ?></h2>
            <p class="mt-1 mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.topIntro') ?></p>
            <?php if ($top === []) : ?>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.topEmpty') ?></p>
            <?php else : ?>
                <ol class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($top as $item) : ?>
                        <li class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center">
                            <div class="min-w-0 flex-1">
                                <p class="break-words font-medium text-gray-900 dark:text-white" dir="auto"><?= esc($item['question']) ?></p>
                                <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="tabular-nums"><?= lang('Asisten.askedTimes', [$num($item['count'])]) ?></span>
                                    <span aria-hidden="true">·</span>
                                    <span><?= esc(tanggal_indo($item['last'], true)) ?></span>
                                    <?php foreach ($item['kinds'] as $k) : ?><?= $badge($k) ?><?php endforeach ?>
                                </p>
                            </div>
                            <?php if ($canQa) : ?>
                                <a href="<?= esc(site_url('admin/tanya-jawab/tambah') . '?' . http_build_query(['pertanyaan' => mb_substr($item['question'], 0, 200)]), 'attr') ?>" class="<?= $btn ?> shrink-0"><?= icon('plus', 'h-4 w-4') ?><?= lang('Asisten.makeQa') ?></a>
                            <?php endif ?>
                        </li>
                    <?php endforeach ?>
                </ol>
            <?php endif ?>
        </section>

        <section class="<?= $card ?>" aria-labelledby="asisten-terbaru">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 id="asisten-terbaru" class="<?= $h2 ?>"><?= lang('Asisten.recentTitle') ?></h2>
                <form method="get" action="<?= site_url('admin/asisten/pertanyaan') ?>" class="flex items-center gap-2">
                    <input type="hidden" name="rentang" value="<?= $range ?>">
                    <label for="f-jenis" class="text-sm text-gray-700 dark:text-gray-300"><?= lang('Asisten.filterKind') ?></label>
                    <select id="f-jenis" name="jenis" class="rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value=""><?= lang('Asisten.filterAll') ?></option>
                        <?php foreach (array_keys($colors) as $k) : ?>
                            <option value="<?= $k ?>"<?= $kind === $k ? ' selected' : '' ?>><?= lang('Asisten.kind.' . $k) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button type="submit" class="<?= $btn ?>"><?= lang('Asisten.filterApply') ?></button>
                </form>
            </div>
            <?php if ($rows === []) : ?>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.recentEmpty') ?></p>
            <?php else : ?>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($rows as $row) : ?>
                        <li class="py-3">
                            <p class="break-words text-gray-900 dark:text-white" dir="auto"><?= esc($row['question']) ?></p>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <?= $badge((string) $row['answered_by']) ?>
                                <span><?= esc(tanggal_indo($row['created_at'], true)) ?></span>
                                <span aria-hidden="true">·</span>
                                <span><?= esc(strtoupper((string) $row['locale'])) ?></span>
                                <?php if ($row['provider_name'] !== null) : ?>
                                    <span aria-hidden="true">·</span><span><?= esc($row['provider_name']) ?><?= $row['tokens_in'] !== null ? ' (' . $num((int) $row['tokens_in'] + (int) $row['tokens_out']) . ' token)' : '' ?></span>
                                <?php endif ?>
                                <?php if ($row['site_question_id'] !== null && $canQa) : ?>
                                    <span aria-hidden="true">·</span><a class="text-primary-700 underline hover:no-underline dark:text-primary-400" href="<?= url_to('situs.tanya-jawab.ubah', (int) $row['site_question_id']) ?>"><?= lang('Asisten.seeQa') ?></a>
                                <?php endif ?>
                            </p>
                        </li>
                    <?php endforeach ?>
                </ul>
                <div class="mt-4 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.recentCount', [$num($pager->getTotal())]) ?></p>
                    <?= $pager->links() ?>
                </div>
            <?php endif ?>
        </section>
    <?php endif ?>

    <section class="<?= $card ?>" aria-labelledby="asisten-simpan">
        <h2 id="asisten-simpan" class="<?= $h2 ?> mb-1"><?= lang('Asisten.retentionTitle') ?></h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.retentionIntro') ?></p>
        <form method="post" action="<?= url_to('asisten.pertanyaan.pengaturan') ?>" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <?= csrf_field() ?>
            <div class="sm:w-64">
                <?= view('partials/form_field', ['field' => [
                    'name' => 'retentionDays', 'label' => lang('Asisten.fieldRetention'), 'type' => 'number', 'value' => $retention, 'required' => true,
                    'attrs' => ['min' => (string) Pertanyaan::RETENTION_MIN, 'max' => (string) Pertanyaan::RETENTION_MAX, 'step' => '1', 'inputmode' => 'numeric'],
                ]]) ?>
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Asisten.save') ?></button>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
