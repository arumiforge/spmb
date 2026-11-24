<?php
/**
 * Visitors on the staff dashboard (slot dashboard), for staff with analitik.view:
 * today, the last 7 days and a 14-day sparkline (plain SVG, no chart script).
 *
 * @var User $user
 */

use CodeIgniter\Shield\Entities\User;
use Modules\Analitik\Libraries\VisitStats;

if (! isset($user) || ! $user->can('analitik.view')) {
    return;
}

$stats  = new VisitStats();
$totals = $stats->totals();
$days   = $stats->daily(14);
$values = array_column($days, 'visitors');
$max    = max(1, ...$values);
$num    = static fn (int $n): string => number_format($n, 0, ',', '.');

// Sparkline geometry: 280 x 56, 4 px padding so the line and the last dot are never clipped.
$w      = 280;
$h      = 56;
$pad    = 4;
$step   = ($w - 2 * $pad) / max(1, count($values) - 1);
$points = [];

foreach ($values as $i => $value) {
    $points[] = [round($pad + $i * $step, 1), round($h - $pad - ($value / $max) * ($h - 2 * $pad), 1)];
}

$line = implode(' ', array_map(static fn (array $p): string => $p[0] . ',' . $p[1], $points));
$area = 'M' . $points[0][0] . ',' . ($h - $pad) . ' L' . implode(' L', array_map(static fn (array $p): string => $p[0] . ',' . $p[1], $points)) . ' L' . end($points)[0] . ',' . ($h - $pad) . ' Z';
$last = end($points);
?>
<section class="rounded-lg border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-800" aria-labelledby="panel-analitik-dasbor">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h2 id="panel-analitik-dasbor" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Analitik.dashTitle') ?></h2>
        <a href="<?= site_url('admin/pengunjung') ?>" class="inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('Analitik.dashOpen') ?><?= icon('arrow-right', 'h-4 w-4') ?></a>
    </div>
    <dl class="grid grid-cols-2 gap-4">
        <div>
            <dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Analitik.today') ?></dt>
            <dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= $num($totals['today']['visitors']) ?></dd>
        </div>
        <div>
            <dt class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Analitik.week') ?></dt>
            <dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= $num($totals['week']['visitors']) ?></dd>
        </div>
    </dl>
    <svg class="mt-4 h-auto w-full text-primary-600 dark:text-primary-400" viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" aria-label="<?= esc(lang('Analitik.dashSpark', [$num(array_sum($values)), $num(max($values))]), 'attr') ?>" xmlns="http://www.w3.org/2000/svg">
        <line x1="<?= $pad ?>" y1="<?= $h - $pad ?>" x2="<?= $w - $pad ?>" y2="<?= $h - $pad ?>" class="text-gray-200 dark:text-gray-700" stroke="currentColor" stroke-width="1"/>
        <path d="<?= $area ?>" fill="currentColor" fill-opacity="0.15"/>
        <polyline points="<?= $line ?>" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
        <circle cx="<?= $last[0] ?>" cy="<?= $last[1] ?>" r="3.5" fill="currentColor"/>
    </svg>
</section>
