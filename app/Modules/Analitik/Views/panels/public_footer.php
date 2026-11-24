<?php
/**
 * Small visitor line for the public footer (slot public_footer), shown while
 * setting('Analitik.showInFooter') is on. Numbers are cached for five minutes
 * (VisitStats::footer()), so most page loads run no query for this.
 */

use Modules\Analitik\Libraries\VisitStats;

if (! setting('Analitik.showInFooter')) {
    return;
}

try {
    $numbers = (new VisitStats())->footer();
} catch (Throwable $e) {
    log_message('error', 'Analitik: footer numbers unavailable: {message}', ['message' => $e->getMessage()]);

    return;
}

$num = static fn (int $n): string => number_format($n, 0, ',', '.');
?>
<p class="inline-flex flex-wrap items-center justify-center gap-x-1.5 gap-y-0.5 text-xs text-gray-500 dark:text-gray-400">
    <?= icon('chart-line', 'h-3.5 w-3.5 shrink-0') ?>
    <span><?= lang('Analitik.footerToday') ?> <strong class="font-semibold text-gray-700 tabular-nums dark:text-gray-300"><?= $num($numbers['today']) ?></strong></span>
    <span aria-hidden="true">·</span>
    <span><?= lang('Analitik.footerMonth') ?> <strong class="font-semibold text-gray-700 tabular-nums dark:text-gray-300"><?= $num($numbers['month']) ?></strong></span>
    <span aria-hidden="true">·</span>
    <span><?= lang('Analitik.footerTotal') ?> <strong class="font-semibold text-gray-700 tabular-nums dark:text-gray-300"><?= $num($numbers['total']) ?></strong></span>
</p>
