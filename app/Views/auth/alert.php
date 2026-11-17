<?php
/**
 * Inline alert inside auth cards.
 *
 * @var array{type: string, text: string} $alert type: error|success|warning|info
 */
$alertStyles = [
    'error'   => 'border-red-300 bg-red-50 text-red-800 dark:border-red-800 dark:bg-gray-800 dark:text-red-400',
    'success' => 'border-green-300 bg-green-50 text-green-800 dark:border-green-800 dark:bg-gray-800 dark:text-green-400',
    'warning' => 'border-yellow-300 bg-yellow-50 text-yellow-800 dark:border-yellow-800 dark:bg-gray-800 dark:text-yellow-300',
    'info'    => 'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-gray-800 dark:text-blue-400',
];
?>
<div class="mb-4 flex items-start gap-3 rounded-lg border p-4 text-sm <?= $alertStyles[$alert['type']] ?? $alertStyles['info'] ?>" role="<?= $alert['type'] === 'error' ? 'alert' : 'status' ?>">
    <?= icon($alert['type'] === 'success' ? 'check' : ($alert['type'] === 'info' ? 'info' : 'warning'), 'h-5 w-5 shrink-0') ?>
    <div><?= esc($alert['text']) ?></div>
</div>
