<?php
/**
 * Session roster (/admin/tes/kehadiran/sesi/{id}): counts, the "Pindai Kartu"
 * panel (camera + text field), participants with Hadir / Tidak hadir, and
 * "mark the rest absent". Works without JavaScript; with it, every form posts
 * with fetch and the page updates in place.
 *
 * @var array{session: array<string, mixed>, rows: list<array<string, mixed>>, query: string, scan: array<string, mixed>|null, ended: bool, canMove: bool} $roster
 */

use Modules\Tes\Controllers\Kehadiran;

$session   = $roster['session'];
$sessionId = (int) $session['id'];
$counts    = Kehadiran::counts($session);
$remaining = $counts['pending'];
$card      = 'rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800';
$input     = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-base text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$tiles     = [
    'filled'  => ['label' => lang('TesKehadiran.countTotal'), 'color' => 'text-gray-900 dark:text-white'],
    'present' => ['label' => lang('TesKehadiran.countPresent'), 'color' => 'text-green-700 dark:text-green-400'],
    'absent'  => ['label' => lang('TesKehadiran.countAbsent'), 'color' => 'text-red-700 dark:text-red-400'],
    'pending' => ['label' => lang('TesKehadiran.countPending'), 'color' => 'text-blue-700 dark:text-blue-400'],
];
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<a href="<?= site_url('admin/tes/sesi/' . $sessionId . '/daftar-hadir') ?>" target="_blank" rel="noopener" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
    <?= icon('clipboard', 'h-4 w-4') ?><?= lang('TesKehadiran.printSheet') ?>
</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<dl class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4" aria-live="polite">
    <?php foreach ($tiles as $key => $tile) : ?>
        <div class="<?= $card ?> p-4">
            <dt class="text-sm text-gray-500 dark:text-gray-400"><?= esc($tile['label']) ?></dt>
            <dd class="mt-1 text-2xl font-bold <?= $tile['color'] ?>" data-count="<?= $key ?>"><?= $counts[$key] ?></dd>
        </div>
    <?php endforeach ?>
</dl>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
    <section class="<?= $card ?> self-start p-4 sm:p-5 lg:sticky lg:top-20 lg:order-2" aria-labelledby="pindai-judul" data-scanner>
        <h2 id="pindai-judul" class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesKehadiran.scanTitle') ?></h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesKehadiran.scanIntro') ?></p>

        <div class="mb-4 hidden" data-camera>
            <div class="mb-3 hidden overflow-hidden rounded-lg bg-black" data-camera-box>
                <video class="aspect-square w-full object-cover" playsinline muted aria-label="<?= esc(lang('TesKehadiran.cameraVideo'), 'attr') ?>" data-camera-video></video>
            </div>
            <button type="button" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800" data-camera-start>
                <?= icon('eye', 'h-5 w-5') ?><?= lang('TesKehadiran.cameraStart') ?>
            </button>
            <button type="button" class="hidden min-h-12 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-3 text-base font-semibold text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700" data-camera-stop>
                <?= icon('x', 'h-5 w-5') ?><?= lang('TesKehadiran.cameraStop') ?>
            </button>
        </div>
        <p class="mb-4 hidden rounded-lg bg-gray-100 p-3 text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-200" data-camera-message></p>

        <form method="post" action="<?= site_url('admin/tes/kehadiran/sesi/' . $sessionId . '/pindai') ?>" data-attendance data-scan-form autocomplete="off">
            <?= csrf_field() ?>
            <label for="kode" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"><?= lang('TesKehadiran.codeLabel') ?></label>
            <input id="kode" name="kode" type="text" required maxlength="300" inputmode="text" autocapitalize="characters" spellcheck="false" aria-describedby="kode-help" class="<?= $input ?> font-mono">
            <p id="kode-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesKehadiran.codeHelp') ?></p>
            <button type="submit" class="mt-3 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-5 py-3 text-base font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 dark:focus:ring-green-800">
                <?= icon('check', 'h-5 w-5') ?><?= lang('TesKehadiran.codeButton') ?>
            </button>
        </form>

        <div class="mt-4 empty:hidden" role="status" aria-live="assertive" data-scan-result><?php if (! empty($roster['scan'])) : ?><?= view('Modules\Tes\Views\kehadiran\_scan_result', ['scan' => $roster['scan']], ['saveData' => false]) ?><?php endif ?></div>
    </section>

    <section class="<?= $card ?> lg:order-1" aria-labelledby="peserta-judul">
        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
            <h2 id="peserta-judul" class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesKehadiran.participants', [$counts['filled']]) ?></h2>
            <form method="get" action="<?= site_url('admin/tes/kehadiran/sesi/' . $sessionId) ?>" role="search" class="flex gap-2 sm:w-80" data-roster-search>
                <div class="relative flex-1">
                    <label for="q" class="sr-only"><?= lang('TesKehadiran.search') ?></label>
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-4 w-4') ?></span>
                    <input type="search" id="q" name="q" value="<?= esc($roster['query'], 'attr') ?>" placeholder="<?= esc(lang('TesKehadiran.search'), 'attr') ?>" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" data-roster-filter>
                </div>
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('TesKehadiran.searchButton') ?></button>
            </form>
        </div>

        <?php if ($roster['rows'] === [] && $roster['query'] === '') : ?>
            <div class="p-4" data-roster-empty>
                <?= view('partials/empty_state', ['empty' => ['title' => lang('TesKehadiran.noParticipants'), 'text' => lang('TesKehadiran.noParticipantsText'), 'image' => 'state/kosong-peserta-sesi', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData')]]) ?>
            </div>
        <?php endif ?>
        <div class="<?= $roster['rows'] === [] && $roster['query'] !== '' ? '' : 'hidden ' ?>p-4" data-roster-nomatch>
            <?= view('partials/empty_state', ['empty' => ['title' => lang('TesKehadiran.noMatch'), 'text' => lang('TesKehadiran.noMatchText'), 'image' => 'state/kosong-pencarian', 'imageAlt' => lang('App.altNoResults')]]) ?>
        </div>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700" data-roster>
            <?php foreach ($roster['rows'] as $row) : ?>
                <?= view('Modules\Tes\Views\kehadiran\_row', ['entry' => ['row' => $row, 'sessionId' => $sessionId]], ['saveData' => false]) ?>
            <?php endforeach ?>
        </ul>
    </section>
</div>

<section class="<?= $card ?> mt-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5" aria-labelledby="sisa-judul">
    <div>
        <h2 id="sisa-judul" class="font-semibold text-gray-900 dark:text-white"><?= lang('TesKehadiran.allAbsent') ?></h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <?= $roster['ended'] ? lang('TesKehadiran.allAbsentHelp') : lang('TesKehadiran.allAbsentWait', [esc(Modules\Tes\Libraries\TestSchedule::timeLabel((string) $session['starts_at'], (string) $session['ends_at']))]) ?>
        </p>
    </div>
    <form method="post" action="<?= site_url('admin/tes/kehadiran/sesi/' . $sessionId . '/tidak-hadir-semua') ?>" data-confirm="<?= esc(lang('TesKehadiran.allAbsentConfirm', [$remaining]), 'attr') ?>">
        <?= csrf_field() ?>
        <button type="submit" <?= $roster['ended'] && $remaining > 0 ? '' : 'disabled' ?> class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg border border-red-600 bg-white px-5 py-3 text-sm font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-200 disabled:cursor-not-allowed disabled:border-gray-300 disabled:text-gray-400 disabled:hover:bg-white sm:w-auto dark:border-red-500 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700 dark:disabled:border-gray-600 dark:disabled:text-gray-500 dark:disabled:hover:bg-gray-800">
            <?= icon('x', 'h-5 w-5') ?><?= lang('TesKehadiran.allAbsent') ?>
        </button>
    </form>
</section>

<div class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex justify-center sm:inset-x-auto sm:end-6" aria-live="polite" role="status">
    <p class="pointer-events-auto hidden max-w-md rounded-lg px-4 py-3 text-sm font-medium shadow-lg" data-toast></p>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= esc(asset_url('scanner.js'), 'attr') ?>" defer></script>
<script<?= csp_script_nonce() !== '' ? ' ' . csp_script_nonce() : '' ?>>
/* Roster without reloads: forms post with fetch, the server answers with the new row, counts and CSRF token. */
(() => {
    const text = <?= json_encode([
        'working'      => lang('TesKehadiran.working'),
        'network'      => lang('TesKehadiran.networkError'),
        'cameraNone'   => lang('TesKehadiran.cameraNone'),
        'cameraDenied' => lang('TesKehadiran.cameraDenied'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const csrfName = <?= json_encode(csrf_token()) ?>;
    const list = document.querySelector('[data-roster]');
    const result = document.querySelector('[data-scan-result]');
    const toast = document.querySelector('[data-toast]');
    const codeInput = document.getElementById('kode');
    let toastTimer = null;
    let busy = false;

    const showToast = (message, ok) => {
        toast.textContent = message;
        toast.className = 'pointer-events-auto max-w-md rounded-lg px-4 py-3 text-sm font-medium shadow-lg '
            + (ok ? 'bg-green-600 text-white' : 'bg-red-600 text-white');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.add('hidden'), 4000);
    };

    const setCsrf = (hash) => {
        if (!hash) return;
        document.querySelectorAll('input[name="' + csrfName + '"]').forEach((input) => { input.value = hash; });
    };

    const fragment = (html) => {
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        return template.content.firstElementChild;
    };

    const update = (data) => {
        setCsrf(data.csrf);
        if (data.counts) {
            Object.entries(data.counts).forEach(([key, value]) => {
                document.querySelectorAll('[data-count="' + key + '"]').forEach((el) => { el.textContent = value; });
            });
        }
        if (data.rowHtml && list) {
            const row = fragment(data.rowHtml);
            const old = document.getElementById('peserta-' + data.applicantId);
            if (old) {
                old.replaceWith(row);
            } else {
                list.appendChild(row);
                document.querySelectorAll('[data-roster-empty]').forEach((el) => el.remove());
            }
            row.classList.add('bg-green-50', 'dark:bg-gray-700');
            window.setTimeout(() => row.classList.remove('bg-green-50', 'dark:bg-gray-700'), 1500);
        }
        if (data.resultHtml !== undefined) {
            result.innerHTML = data.resultHtml;
        }
    };

    const send = async (form) => {
        if (busy) return;
        if (form.dataset.ask && !window.confirm(form.dataset.ask)) return;
        busy = true;
        const buttons = form.querySelectorAll('button');
        buttons.forEach((b) => { b.disabled = true; });
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await response.json();
            const inResult = form.closest('[data-scan-result]') !== null;
            update(data);
            // Scans show their result in the panel; everything else gets a short message.
            if (data.resultHtml === undefined) {
                showToast(data.message, data.ok);
                if (data.ok && inResult) result.innerHTML = '';
            }
            if (data.ok && navigator.vibrate) navigator.vibrate(80);
            if (form.matches('[data-scan-form]')) codeInput.value = '';
        } catch (error) {
            showToast(text.network, false);
        } finally {
            busy = false;
            buttons.forEach((b) => { if (document.body.contains(b)) b.disabled = false; });
            if (form.matches('[data-scan-form]') && window.matchMedia('(pointer: fine)').matches) codeInput.focus();
        }
    };

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-attendance]');
        if (!form) return;
        event.preventDefault();
        send(form);
    });

    // Search filters the roster as you type.
    const filter = document.querySelector('[data-roster-filter]');
    const searchForm = document.querySelector('[data-roster-search]');
    const noMatch = document.querySelector('[data-roster-nomatch]');
    const applyFilter = () => {
        const q = filter.value.trim().toLowerCase();
        let shown = 0;
        document.querySelectorAll('[data-roster-row]').forEach((row) => {
            const hit = q === '' || row.dataset.search.includes(q);
            row.classList.toggle('hidden', !hit);
            if (hit) shown++;
        });
        noMatch.classList.toggle('hidden', shown > 0 || q === '');
    };
    if (filter && searchForm && document.querySelectorAll('[data-roster-row]').length > 0) {
        filter.addEventListener('input', applyFilter);
        searchForm.addEventListener('submit', (event) => { event.preventDefault(); applyFilter(); });
    }

    // USB scanners type into the field: keep it focused on computers (not on phones, to keep the keyboard closed).
    if (window.matchMedia('(pointer: fine)').matches) codeInput.focus();

    // Camera scanning (public/build/scanner.js).
    const camera = document.querySelector('[data-camera]');
    const box = document.querySelector('[data-camera-box]');
    const video = document.querySelector('[data-camera-video]');
    const start = document.querySelector('[data-camera-start]');
    const stopButton = document.querySelector('[data-camera-stop]');
    const cameraMessage = document.querySelector('[data-camera-message]');
    const scanForm = document.querySelector('[data-scan-form]');
    let stop = null;

    const say = (message) => {
        cameraMessage.textContent = message;
        cameraMessage.classList.toggle('hidden', !message);
    };
    const running = (on) => {
        box.classList.toggle('hidden', !on);
        start.classList.toggle('hidden', on);
        start.classList.toggle('inline-flex', !on);
        stopButton.classList.toggle('hidden', !on);
        stopButton.classList.toggle('inline-flex', on);
    };

    window.addEventListener('load', async () => {
        if (!window.SpmbScanner) {
            say(text.cameraNone);
            return;
        }
        let has = false;
        try { has = await window.SpmbScanner.hasCamera(); } catch (e) { has = false; }
        if (!has) {
            say(text.cameraNone);
            return;
        }
        camera.classList.remove('hidden');
    });

    start.addEventListener('click', async () => {
        say('');
        running(true);
        try {
            stop = await window.SpmbScanner.start(video, (scanned) => {
                if (busy) return;
                codeInput.value = window.SpmbScanner.cardCode(scanned);
                send(scanForm);
            });
        } catch (error) {
            running(false);
            say(text.cameraDenied);
        }
    });

    stopButton.addEventListener('click', () => {
        if (stop) stop();
        stop = null;
        running(false);
    });
})();
</script>
<?= $this->endSection() ?>
