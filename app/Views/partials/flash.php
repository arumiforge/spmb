<?php
/**
 * Toasts for flash messages: message (success), warning, error and errors (list).
 * Success toasts hide themselves; warnings and errors stay until closed.
 */
$toasts = [];

if (session('message') !== null) {
    $toasts[] = ['type' => 'success', 'text' => session('message')];
}

if (session('warning') !== null) {
    $toasts[] = ['type' => 'warning', 'text' => session('warning')];
}

if (session('error') !== null) {
    $toasts[] = ['type' => 'error', 'text' => session('error')];
}

if (session('errors') !== null && ! (isset($formErrorsInline) && $formErrorsInline)) {
    $errors = session('errors');
    $toasts[] = ['type' => 'error', 'text' => is_array($errors) ? lang('App.formHasErrors') : (string) $errors];
}

$styles = [
    'success' => ['icon' => 'check', 'box' => 'text-green-600 dark:text-green-400'],
    'warning' => ['icon' => 'warning', 'box' => 'text-yellow-600 dark:text-yellow-400'],
    'error'   => ['icon' => 'warning', 'box' => 'text-red-600 dark:text-red-400'],
];
?>
<div class="pointer-events-none fixed inset-x-4 top-20 z-50 flex flex-col items-end gap-3 sm:left-auto sm:right-5" aria-live="polite">
    <?php foreach ($toasts as $i => $toast) : ?>
        <div id="toast-<?= $i ?>" role="<?= $toast['type'] === 'success' ? 'status' : 'alert' ?>"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-gray-100 bg-white p-4 text-gray-700 shadow-lg transition-opacity duration-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
             <?= $toast['type'] === 'success' ? 'data-toast-autohide' : '' ?> data-toast="<?= esc($toast['type'], 'attr') ?>">
            <?= icon($styles[$toast['type']]['icon'], 'mt-0.5 h-6 w-6 shrink-0 ' . $styles[$toast['type']]['box']) ?>
            <p class="flex-1 pt-1 text-sm"><?= esc($toast['text']) ?></p>
            <button type="button" class="-m-1.5 ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300 dark:hover:bg-gray-700 dark:hover:text-white" data-dismiss-target="#toast-<?= $i ?>" aria-label="<?= lang('App.close') ?>">
                <?= icon('x', 'h-4 w-4') ?>
            </button>
        </div>
    <?php endforeach ?>
</div>
