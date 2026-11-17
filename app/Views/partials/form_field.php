<?php
/**
 * One labelled form field with help text and its validation error.
 *
 * @var array{
 *     name: string, label: string, type?: string, value?: mixed, help?: string,
 *     required?: bool, options?: array<string, string>, attrs?: array<string, string>,
 *     placeholder?: string, id?: string
 * } $field
 * type: text|email|password|tel|url|number|color|textarea|select|file|checkbox
 */
$type     = $field['type'] ?? 'text';
$name     = $field['name'];
$id       = $field['id'] ?? 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
$errors   = session('errors');
$error    = is_array($errors) ? ($errors[rtrim($name, '[]')] ?? null) : null;
$value    = old(rtrim($name, '[]'), $field['value'] ?? '');
$required = ! empty($field['required']);
$describe = trim((! empty($field['help']) ? $id . '-help ' : '') . ($error !== null ? $id . '-error' : ''));

$base = $error !== null
    ? 'block w-full rounded-lg border border-red-500 bg-red-50 p-2.5 text-sm text-red-900 placeholder-red-700 focus:border-red-500 focus:ring-red-500 dark:border-red-500 dark:bg-gray-700 dark:text-red-400 dark:placeholder-red-500'
    : 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500';

$attrs = '';

foreach (($field['attrs'] ?? []) as $attr => $attrValue) {
    $attrs .= ' ' . esc($attr, 'attr') . '="' . esc($attrValue, 'attr') . '"';
}

if ($describe !== '') {
    $attrs .= ' aria-describedby="' . esc($describe, 'attr') . '"';
}

if ($error !== null) {
    $attrs .= ' aria-invalid="true"';
}

if ($required) {
    $attrs .= ' required';
}

if (! empty($field['placeholder'])) {
    $attrs .= ' placeholder="' . esc($field['placeholder'], 'attr') . '"';
}
?>
<div>
    <?php if ($type === 'checkbox') : ?>
        <div class="flex items-start gap-3">
            <input type="hidden" name="<?= esc($name, 'attr') ?>" value="0">
            <input id="<?= esc($id, 'attr') ?>" type="checkbox" name="<?= esc($name, 'attr') ?>" value="1" <?= (string) $value === '1' ? 'checked' : '' ?>
                   class="mt-0.5 h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"<?= $attrs ?>>
            <label for="<?= esc($id, 'attr') ?>" class="text-sm font-medium text-gray-900 dark:text-gray-300"><?= esc($field['label']) ?></label>
        </div>
    <?php else : ?>
        <label for="<?= esc($id, 'attr') ?>" class="mb-2 block text-sm font-medium <?= $error !== null ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white' ?>">
            <?= esc($field['label']) ?>
            <?php if (! $required) : ?><span class="font-normal text-gray-500 dark:text-gray-400">(<?= lang('App.optional') ?>)</span><?php endif ?>
        </label>
        <?php if ($type === 'textarea') : ?>
            <textarea id="<?= esc($id, 'attr') ?>" name="<?= esc($name, 'attr') ?>" rows="<?= (int) ($field['rows'] ?? 3) ?>" class="<?= $base ?>"<?= $attrs ?>><?= esc((string) $value) ?></textarea>
        <?php elseif ($type === 'select') : ?>
            <select id="<?= esc($id, 'attr') ?>" name="<?= esc($name, 'attr') ?>" class="<?= $base ?>"<?= $attrs ?>>
                <?php foreach ($field['options'] ?? [] as $optValue => $optLabel) : ?>
                    <option value="<?= esc((string) $optValue, 'attr') ?>" <?= (string) $optValue === (string) $value ? 'selected' : '' ?>><?= esc($optLabel) ?></option>
                <?php endforeach ?>
            </select>
        <?php elseif ($type === 'file') : ?>
            <input id="<?= esc($id, 'attr') ?>" type="file" name="<?= esc($name, 'attr') ?>" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:placeholder-gray-400"<?= $attrs ?>>
        <?php elseif ($type === 'color') : ?>
            <div class="flex items-center gap-3">
                <input id="<?= esc($id, 'attr') ?>" type="color" name="<?= esc($name, 'attr') ?>" value="<?= esc((string) $value, 'attr') ?>" class="h-11 w-16 cursor-pointer rounded-lg border border-gray-300 bg-gray-50 p-1 dark:border-gray-600 dark:bg-gray-700"<?= $attrs ?>>
                <span class="font-mono text-sm text-gray-600 dark:text-gray-300"><?= esc((string) $value) ?></span>
            </div>
        <?php elseif ($type === 'password') : ?>
            <div class="relative">
                <input id="<?= esc($id, 'attr') ?>" type="password" name="<?= esc($name, 'attr') ?>" class="<?= $base ?> pe-11"<?= $attrs ?>>
                <button type="button" data-password-toggle="<?= esc($id, 'attr') ?>" aria-pressed="false" class="absolute inset-y-0 end-0 flex items-center px-3 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    <span class="sr-only"><?= lang('App.showPassword') ?></span><?= icon('eye', 'h-5 w-5') ?>
                </button>
            </div>
        <?php else : ?>
            <input id="<?= esc($id, 'attr') ?>" type="<?= esc($type, 'attr') ?>" name="<?= esc($name, 'attr') ?>" value="<?= esc((string) $value, 'attr') ?>" class="<?= $base ?>"<?= $attrs ?>>
        <?php endif ?>
    <?php endif ?>
    <?php if (! empty($field['help'])) : ?>
        <p id="<?= esc($id, 'attr') ?>-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= esc($field['help']) ?></p>
    <?php endif ?>
    <?php if ($error !== null) : ?>
        <p id="<?= esc($id, 'attr') ?>-error" class="mt-2 text-sm text-red-600 dark:text-red-500"><?= esc($error) ?></p>
    <?php endif ?>
</div>
