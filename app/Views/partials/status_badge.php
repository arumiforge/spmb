<?php
/**
 * Status badge: always text + colour (never colour alone).
 *
 * @var array{label: string, color?: string} $badge color: green|red|yellow|blue|gray|primary
 */
$colors = [
    'green'   => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    'red'     => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    'yellow'  => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    'blue'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    'gray'    => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    'primary' => 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300',
];
?>
<span class="inline-flex items-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium <?= $colors[$badge['color'] ?? 'gray'] ?? $colors['gray'] ?>"><?= esc($badge['label']) ?></span>
