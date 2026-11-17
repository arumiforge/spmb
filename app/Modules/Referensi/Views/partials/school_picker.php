<?php
/**
 * Previous-school picker for registration forms (public and staff).
 *
 * Posts previous_school_id (hidden, list entry) and previous_school_name
 * (free text). Without JavaScript it is a plain text field; with JavaScript it
 * suggests schools from GET /sekolah-asal/cari as an ARIA combobox. Read the
 * posted pair with SchoolModel::fromPicker($id, $name).
 *
 * view('Modules\Referensi\Views\partials\school_picker', ['picker' => [
 *     'id' => ?int, 'name' => ?string, 'error' => ?string, 'required' => bool,
 * ]])
 *
 * @var array{id?: int|null, name?: string|null, error?: string|null, required?: bool} $picker
 */

use Modules\Referensi\Models\SchoolModel;

$pickedId = old('previous_school_id', $picker['id'] ?? null);
$pickedId = is_numeric($pickedId) && (int) $pickedId > 0 ? (int) $pickedId : null;
$text     = old('previous_school_name', $picker['name'] ?? null);
$label    = $pickedId !== null ? model(SchoolModel::class)->label($pickedId) : null;

if ($label === null) {
    $pickedId = null;
} elseif ($text === null || $text === '') {
    $text = $label;
}

$text     = (string) $text;
$error    = $picker['error'] ?? null;
$required = ! empty($picker['required']);
$describe = 'school-picker-help school-picker-state' . ($error !== null ? ' school-picker-error' : '');
$field    = $error !== null
    ? 'block w-full rounded-lg border border-red-500 bg-red-50 p-2.5 ps-9 text-sm text-red-900 placeholder-red-700 focus:border-red-500 focus:ring-red-500 dark:border-red-500 dark:bg-gray-700 dark:text-red-400 dark:placeholder-red-500'
    : 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500';
?>
<div data-school-picker data-url="<?= esc(site_url('sekolah-asal/cari'), 'attr') ?>"
     data-not-listed="<?= esc(lang('Referensi.pickerNotListed'), 'attr') ?>"
     data-not-listed-hint="<?= esc(lang('Referensi.pickerNotListedHint'), 'attr') ?>"
     data-no-result="<?= esc(lang('Referensi.pickerNoResult'), 'attr') ?>"
     data-searching="<?= esc(lang('Referensi.pickerSearching'), 'attr') ?>"
     data-chosen="<?= esc(lang('Referensi.pickerChosen'), 'attr') ?>"
     data-free-text="<?= esc(lang('Referensi.pickerFreeText'), 'attr') ?>"
     data-results="<?= esc(lang('Referensi.pickerResults'), 'attr') ?>">
    <label for="previous_school_name" class="mb-2 block text-sm font-medium <?= $error !== null ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white' ?>">
        <?= lang('Referensi.pickerLabel') ?>
        <?php if (! $required) : ?><span class="font-normal text-gray-500 dark:text-gray-400">(<?= lang('App.optional') ?>)</span><?php endif ?>
    </label>
    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('school', 'h-4 w-4') ?></span>
        <input type="text" id="previous_school_name" name="previous_school_name" value="<?= esc($text, 'attr') ?>"
               maxlength="150" autocomplete="off" spellcheck="false" placeholder="<?= esc(lang('Referensi.pickerPlaceholder'), 'attr') ?>"
               role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="school-picker-list"
               aria-describedby="<?= $describe ?>"<?= $error !== null ? ' aria-invalid="true"' : '' ?><?= $required ? ' required' : '' ?>
               class="<?= $field ?>" data-picker-input>
        <input type="hidden" name="previous_school_id" value="<?= $pickedId ?? '' ?>" data-picker-id>
        <ul id="school-picker-list" role="listbox" aria-label="<?= esc(lang('Referensi.pickerLabel'), 'attr') ?>" hidden data-picker-list
            class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 text-sm shadow-lg dark:border-gray-600 dark:bg-gray-700"></ul>
    </div>
    <p id="school-picker-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.pickerHelp') ?></p>
    <p id="school-picker-state" class="mt-1 text-sm text-gray-700 dark:text-gray-300" aria-live="polite" data-picker-state><?= $pickedId !== null ? esc(lang('Referensi.pickerChosen', [$label])) : '' ?></p>
    <?php if ($error !== null) : ?>
        <p id="school-picker-error" class="mt-2 text-sm text-red-600 dark:text-red-500"><?= esc($error) ?></p>
    <?php endif ?>
</div>
<script>
(() => {
    const root = document.querySelector('[data-school-picker]:not([data-ready])');
    if (!root || !window.fetch) return;
    root.dataset.ready = '1';
    const input = root.querySelector('[data-picker-input]');
    const hidden = root.querySelector('[data-picker-id]');
    const list = root.querySelector('[data-picker-list]');
    const state = root.querySelector('[data-picker-state]');
    const t = root.dataset;
    let items = [], active = -1, timer = null, controller = null;

    const option = (id, main, sub, value) => {
        const li = document.createElement('li');
        li.id = 'school-picker-opt-' + list.children.length;
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', 'false');
        li.className = 'cursor-pointer px-3 py-2 text-gray-900 hover:bg-primary-50 aria-selected:bg-primary-100 dark:text-white dark:hover:bg-gray-600 dark:aria-selected:bg-gray-600';
        li.textContent = main;
        if (sub) {
            const small = document.createElement('span');
            small.className = 'block text-xs text-gray-500 dark:text-gray-300';
            small.textContent = sub;
            li.appendChild(small);
        }
        li.addEventListener('mousedown', (e) => { e.preventDefault(); choose(id, value); });
        list.appendChild(li);
        items.push(li);
    };
    const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; };
    const highlight = (i) => {
        items.forEach((li, n) => li.setAttribute('aria-selected', n === i ? 'true' : 'false'));
        active = i;
        if (items[i]) { input.setAttribute('aria-activedescendant', items[i].id); items[i].scrollIntoView({ block: 'nearest' }); }
    };
    const choose = (id, value) => {
        hidden.value = id || '';
        if (value !== null) input.value = value;
        state.textContent = id ? t.chosen.replace('{0}', value) : t.freeText;
        close();
    };
    const render = (rows) => {
        list.innerHTML = '';
        items = [];
        if (rows.length === 0) {
            const li = document.createElement('li');
            li.className = 'px-3 py-2 text-gray-500 dark:text-gray-300';
            li.textContent = t.noResult;
            list.appendChild(li);
        }
        rows.forEach((r) => option(r.id, r.name, r.label.slice(r.name.length).replace(/^ — /, ''), r.label));
        option(null, t.notListed, t.notListedHint, null);
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        state.textContent = rows.length ? t.results.replace('{0}', rows.length) : '';
        highlight(-1);
    };
    const search = () => {
        const q = input.value.trim();
        if (q.length < 2) { close(); return; }
        if (controller) controller.abort();
        controller = new AbortController();
        fetch(t.url + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' }, signal: controller.signal })
            .then((r) => (r.ok ? r.json() : []))
            .then((rows) => { if (document.activeElement === input) render(Array.isArray(rows) ? rows : []); })
            .catch(() => {});
    };

    input.addEventListener('input', () => {
        hidden.value = '';
        state.textContent = '';
        clearTimeout(timer);
        timer = setTimeout(search, 250);
    });
    input.addEventListener('keydown', (e) => {
        if (list.hidden) {
            if (e.key === 'ArrowDown' && input.value.trim().length >= 2) { e.preventDefault(); search(); }
            return;
        }
        if (e.key === 'ArrowDown') { e.preventDefault(); highlight(Math.min(active + 1, items.length - 1)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(Math.max(active - 1, 0)); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); items[active].dispatchEvent(new MouseEvent('mousedown')); }
        else if (e.key === 'Escape') { e.preventDefault(); close(); }
    });
    input.addEventListener('blur', () => {
        close();
        if (!hidden.value && input.value.trim() !== '') state.textContent = t.freeText;
    });
})();
</script>
