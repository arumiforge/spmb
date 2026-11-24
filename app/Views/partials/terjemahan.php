<?php
/**
 * Optional English and Arabic versions of school-entered texts (D-109), under the Indonesian fields of an
 * admin form: a native fold "Terjemahan (tidak wajib)" with one field per language that is switched on.
 * Field names are "<name>_en" and "<name>_ar"; empty means the website shows the Indonesian text.
 * The fold opens when something is filled in or has an error. Nothing shows when both languages are off.
 *
 * @var array{
 *     fields: list<array{name: string, label: string, type?: string, values?: array{en?: string, ar?: string}, maxlength?: int, rows?: int, help?: string}>,
 *     id?: string,
 *     class?: string
 * } $translation
 */
use App\Libraries\Locales;

$languages = array_values(array_diff(Locales::enabled(), [Locales::DEFAULT]));

if ($languages === []) {
    return;
}

$errors = session('errors');
$errors = is_array($errors) ? $errors : [];
$open   = false;

foreach ($translation['fields'] as $field) {
    foreach ($languages as $locale) {
        if (trim((string) old($field['name'] . '_' . $locale, $field['values'][$locale] ?? '')) !== '' || isset($errors[$field['name'] . '_' . $locale])) {
            $open = true;
        }
    }
}

$labels = ['en' => 'App.translationEnglish', 'ar' => 'App.translationArabic'];
?>
<details class="terjemahan group rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900 <?= esc($translation['class'] ?? '') ?>"<?= isset($translation['id']) ? ' id="' . esc($translation['id'], 'attr') . '"' : '' ?><?= $open ? ' open' : '' ?> data-terjemahan>
    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-white dark:hover:bg-gray-800 [&::-webkit-details-marker]:hidden">
        <?= icon('globe', 'h-4 w-4 shrink-0 text-primary-600 dark:text-primary-400') ?>
        <span><?= lang('App.translationTitle') ?></span>
        <?= icon('chevron-down', 'ms-auto h-4 w-4 shrink-0 text-gray-500 transition-transform group-open:rotate-180') ?>
    </summary>
    <div class="space-y-4 border-t border-gray-200 px-4 pb-4 pt-3 dark:border-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400"><?= lang('App.translationIntro') ?></p>
        <?php foreach ($translation['fields'] as $field) : ?>
            <div class="grid grid-cols-1 gap-4<?= ($field['type'] ?? 'text') === 'textarea' ? '' : ' md:grid-cols-2' ?>">
                <?php foreach ($languages as $locale) : ?>
                    <?php
                    $attrs = ['lang' => $locale, 'dir' => Locales::direction($locale)];

                    if (isset($field['maxlength'])) {
                        $attrs['maxlength'] = (string) $field['maxlength'];
                    }
                    ?>
                    <?= view('partials/form_field', ['field' => [
                        'name'  => $field['name'] . '_' . $locale,
                        'type'  => $field['type'] ?? 'text',
                        'label' => lang($labels[$locale], [$field['label']]),
                        'value' => (string) ($field['values'][$locale] ?? ''),
                        'rows'  => $field['rows'] ?? 3,
                        'help'  => $field['help'] ?? null,
                        'attrs' => $attrs,
                    ]], ['saveData' => false]) ?>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>
    </div>
</details>
