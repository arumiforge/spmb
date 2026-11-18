<?php
/**
 * One schedule change as "from → to" lines, for the change log and the
 * applicant panel. Row from TestScheduleChangeModel::detailed().
 *
 * @var array<string, mixed> $change
 */

use Modules\Tes\Libraries\TestSchedule;

$label = static function (array $c, string $side): ?string {
    if ($c[$side . '_session_id'] === null) {
        return null;
    }

    if ($c[$side . '_date'] === null) {
        return lang('TesPenjadwalan.deletedSession');
    }

    return TestSchedule::sessionLabel([
        'test_date' => $c[$side . '_date'],
        'starts_at' => $c[$side . '_start'],
        'ends_at'   => $c[$side . '_end'],
        'room_name' => $c[$side . '_room'],
    ]);
};
$from = $label($change, 'from');
$to   = $label($change, 'to');
?>
<?php if ($from !== null) : ?>
    <span class="block <?= $to !== null ? 'text-gray-500 line-through decoration-gray-400 dark:text-gray-400' : 'text-gray-900 dark:text-white' ?>"><?= esc($from) ?></span>
<?php endif ?>
<?php if ($to !== null) : ?>
    <span class="flex items-start gap-1 text-gray-900 dark:text-white"><?php if ($from !== null) : ?><?= icon('arrow-right', 'mt-0.5 h-4 w-4 shrink-0 text-gray-400') ?><span class="sr-only">→</span><?php endif ?><span><?= esc($to) ?></span></span>
<?php endif ?>
