<?php
/**
 * Address picker for registration forms (D-111): province → regency/city →
 * district → village selects that load their options (GET /wilayah/daftar),
 * plus a "Cari desa atau kelurahan" search (GET /wilayah/cari) that fills all
 * four at once (resources/js/wilayah.js).
 *
 * Progressive enhancement: without JavaScript only the old text fields show
 * ($region['fallback'], rendered by the caller) and the selects stay disabled,
 * so nothing but text is posted. With JavaScript the selects post
 * province_code … village_code and the text fields are switched off; "ketik
 * sendiri" switches back to the text fields. Read the post with RegionInput.
 *
 * view('Modules\Referensi\Views\partials\region_picker', ['region' => [
 *     'fields'   => list<string>  text fields on the form (province, regency, district, village),
 *     'required' => array<string, bool>,
 *     'labels'   => array<string, string>,
 *     'values'   => array<string, mixed>  saved row (text and *_code columns) or [],
 *     'fallback' => string  HTML of the text fields,
 * ]])
 *
 * @var array{fields: list<string>, required: array<string, bool>, labels: array<string, string>, values: array<string, mixed>, fallback: string} $region
 */

use Modules\Referensi\Libraries\RegionInput;
use Modules\Referensi\Libraries\RegionMatcher;
use Modules\Referensi\Models\RegionModel;

$model  = model(RegionModel::class);
$levels = RegionModel::LEVELS;
$byKey  = array_flip($levels);
$values = $region['values'];
$errors = session('errors');
$errors = is_array($errors) ? $errors : [];

// Selects from the province down to the deepest field on the form (parents are needed to choose).
$deepest = 0;

foreach ($region['fields'] as $field) {
    $deepest = max($deepest, $byKey[$field] ?? 0);
}

$shown = array_slice($levels, 0, $deepest, true);

// A level is required when it or a level below it is.
$required = [];
$need     = false;

foreach (array_reverse($shown, true) as $level => $key) {
    $need           = $need || ! empty($region['required'][$key]);
    $required[$key] = $need;
}

// Chosen codes: the posted ones after a round trip, else the saved ones, else the school's own region on a fresh form.
$posted = false;
$codes  = [];

foreach ($shown as $key) {
    $old = old(RegionInput::CODE_FIELDS[$key]);

    if ($old !== null) {
        $posted = true;
    }

    $codes[$key] = (string) ($old ?? ($values[RegionInput::CODE_FIELDS[$key]] ?? ''));
}

// Typed text without codes, posted after a round trip or saved earlier: start with the text fields.
$typedPost  = false;
$typedSaved = false;

foreach ($region['fields'] as $key) {
    $typedPost = $typedPost || old($key) !== null;

    if (trim((string) ($values[$key] ?? '')) !== '' && (string) ($values[RegionInput::CODE_FIELDS[$key]] ?? '') === '') {
        $typedSaved = true;
    }
}

$mode  = ! $posted && ($typedPost || $typedSaved) ? 'ketik' : 'pilih';

// A saved address typed by hand: preselect what matches the list exactly, so "Pilih dari daftar" starts there.
// When every typed level matches, the list is shown straight away.
if (! $posted && ! $typedPost && $typedSaved && array_filter($codes) === []) {
    $match = (new RegionMatcher())->match(array_intersect_key($values, RegionInput::CODE_FIELDS), false);

    foreach ($shown as $key) {
        $codes[$key] = (string) ($match['codes'][$key] ?? '');
    }

    if ($match['level'] > 0 && $match['unmatched'] === []) {
        $mode = 'pilih';
    }
}
$fresh = ! $posted && ! $typedPost && array_filter(array_keys(RegionInput::CODE_FIELDS), static fn (string $k): bool => trim((string) ($values[$k] ?? '')) !== '' || trim((string) ($values[RegionInput::CODE_FIELDS[$k]] ?? '')) !== '') === [];

if ($fresh && ($home = (string) setting('School.regionCode')) !== '' && ($chain = $model->chain($home)) !== null) {
    foreach ($chain as $level => $row) {
        if (isset($shown[$level]) && $level <= 2) {
            $codes[$levels[$level]] = $row['code'];
        }
    }
}

// Keep only a consistent chain: each code must lie inside the one above it.
$parent = null;

foreach ($shown as $level => $key) {
    $code = $codes[$key];

    if ($code !== '' && (RegionModel::levelOf($code) !== $level || ($parent !== null && RegionModel::parentOf($code) !== $parent) || ($parent === null && $level > 1))) {
        $code = '';
    }

    $codes[$key] = $code;
    $parent      = $code === '' ? null : $code;

    if ($parent === null) {
        // Everything below an empty level is empty too.
        foreach ($shown as $l => $k) {
            if ($l > $level) {
                $codes[$k] = '';
            }
        }

        break;
    }
}

// Options per level: provinces always, the children of the chosen parent below.
$options = [];
$parent  = null;

foreach ($shown as $level => $key) {
    $options[$key] = $level === 1 ? $model->children(null) : ($parent !== null ? $model->children($parent) : []);
    $parent        = $codes[$key] !== '' ? $codes[$key] : null;
}

$chosen = '';

foreach (array_reverse($shown, true) as $level => $key) {
    if ($codes[$key] !== '' && ($chain = $model->chain($codes[$key])) !== null) {
        $chosen = RegionModel::pathOf($chain);

        break;
    }
}

$input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500';
$bad   = 'block w-full rounded-lg border border-red-500 bg-red-50 p-2.5 text-sm text-red-900 focus:border-red-500 focus:ring-red-500 dark:border-red-500 dark:bg-gray-700 dark:text-red-400';
$t     = static fn (string $key): string => esc(lang('Referensi.' . $key), 'attr');
?>
<div class="sm:col-span-2" data-wilayah data-wilayah-mode="<?= $mode ?>"
     data-url-daftar="<?= esc(base_url('wilayah/daftar'), 'attr') ?>"
     data-url-cari="<?= esc(base_url('wilayah/cari'), 'attr') ?>"
     data-teks-memuat="<?= $t('regionLoading') ?>"
     data-teks-gagal="<?= $t('regionLoadFailed') ?>"
     data-teks-kosong="<?= $t('regionSearchNoResult') ?>"
     data-teks-hasil="<?= $t('regionSearchResults') ?>"
     data-teks-terpilih="<?= $t('regionChosen') ?>"
     data-teks-ketik="<?= $t('regionTypeOwn') ?>"
     data-teks-daftar="<?= $t('regionUseList') ?>"
     data-jenis-desa="<?= esc(lang('Referensi.regionKinds.desa'), 'attr') ?>"
     data-jenis-kelurahan="<?= esc(lang('Referensi.regionKinds.kelurahan'), 'attr') ?>"
     data-jenis-kecamatan="<?= esc(lang('Referensi.regionKinds.district'), 'attr') ?>">

    <div data-wilayah-pilih hidden>
        <div class="bidang" data-wilayah-cari>
            <label for="wilayah-cari" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                <?= lang('Referensi.regionSearchLabel') ?> <span class="font-normal text-gray-500 dark:text-gray-400">(<?= lang('App.optional') ?>)</span>
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-4 w-4') ?></span>
                <input type="search" id="wilayah-cari" autocomplete="off" spellcheck="false" maxlength="100" disabled
                       placeholder="<?= $t('regionSearchPlaceholder') ?>" role="combobox" aria-autocomplete="list" aria-expanded="false"
                       aria-controls="wilayah-cari-daftar" aria-describedby="wilayah-cari-help wilayah-cari-status"
                       class="<?= $input ?> ps-9" data-wilayah-cari-input data-ringkas-lewati>
                <ul id="wilayah-cari-daftar" role="listbox" aria-label="<?= $t('regionSearchLabel') ?>" hidden data-wilayah-cari-daftar
                    class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 text-sm shadow-lg dark:border-gray-600 dark:bg-gray-700"></ul>
            </div>
            <p id="wilayah-cari-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.regionSearchHelp') ?></p>
            <p id="wilayah-cari-status" class="mt-1 text-sm text-gray-700 dark:text-gray-300" aria-live="polite" data-wilayah-status><?= $chosen !== '' ? esc(lang('Referensi.regionChosen', [$chosen])) : '' ?></p>
        </div>

        <p class="my-4 flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400" aria-hidden="true">
            <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span><?= lang('Referensi.regionOr') ?><span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
        </p>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?php foreach ($shown as $level => $key) : ?>
                <?php
                $name  = RegionInput::CODE_FIELDS[$key];
                $id    = 'f-' . $name;
                $error = $errors[$key] ?? $errors[$name] ?? null;
                $first = lang('Referensi.regionChooseFirst.' . $key);
                $empty = $options[$key] === [] && $level > 1;
                ?>
                <div class="bidang">
                    <label for="<?= $id ?>" class="mb-2 block text-sm font-medium <?= $error !== null ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white' ?>">
                        <?= esc($region['labels'][$key]) ?>
                        <?php if (! $required[$key]) : ?><span class="font-normal text-gray-500 dark:text-gray-400">(<?= lang('App.optional') ?>)</span><?php endif ?>
                    </label>
                    <select id="<?= $id ?>" name="<?= $name ?>" disabled data-wilayah-tingkat="<?= $level ?>"
                            data-pilih="<?= $t('regionChoose.' . $key) ?>" data-pilih-dulu="<?= esc($first, 'attr') ?>"
                            class="<?= $error !== null ? $bad : $input ?>"<?= $required[$key] ? ' required' : '' ?><?= $error !== null ? ' aria-invalid="true" aria-describedby="' . $id . '-error"' : '' ?>>
                        <option value=""><?= esc($empty ? $first : lang('Referensi.regionChoose.' . $key)) ?></option>
                        <?php foreach ($options[$key] as $option) : ?>
                            <option value="<?= esc($option['code'], 'attr') ?>"<?= $option['code'] === $codes[$key] ? ' selected' : '' ?>><?= esc($option['label']) ?></option>
                        <?php endforeach ?>
                    </select>
                    <?php if ($error !== null) : ?>
                        <p id="<?= $id ?>-error" class="mt-2 text-sm text-red-600 dark:text-red-500"><?= esc($error) ?></p>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    </div>

    <div data-wilayah-ketik>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Referensi.regionTypeNote') ?></p>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2"><?= $region['fallback'] ?></div>
    </div>

    <button type="button" hidden data-wilayah-ganti
            class="mt-4 inline-flex items-center gap-2 rounded-lg text-sm font-medium text-primary-700 underline-offset-4 hover:underline focus:outline-none focus:ring-4 focus:ring-primary-300 dark:text-primary-400 dark:focus:ring-primary-800">
        <?= icon('pencil', 'h-4 w-4') ?><span data-wilayah-ganti-teks><?= lang('Referensi.regionTypeOwn') ?></span>
    </button>
</div>
