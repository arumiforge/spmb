/*
 * Address picker of the registration forms (D-111, Modules/Referensi/Views/partials/region_picker.php).
 *
 * [data-wilayah] holds the four selects (province → regency/city → district → village, posted as
 * province_code … village_code), a "Cari desa atau kelurahan" search that fills all four at once, and
 * the old text fields. Without JavaScript only the text fields show and the selects stay disabled.
 * Here the selects are switched on and the text fields off ("pilih" mode); the button below switches
 * to typing the address ("ketik" mode) for a place that is not in the list. A switched-off control is
 * not posted, so the server gets either codes or text, never both.
 *
 * Options come from GET /wilayah/daftar?induk=<code> (cached here and by the browser); the search
 * from GET /wilayah/cari?q=<text>.
 */

const cache = new Map();

function fetchJson(url, signal) {
    return fetch(url, { headers: { Accept: 'application/json' }, signal }).then((response) => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return response.json();
    });
}

function initPicker(root) {
    const t = root.dataset;
    const pick = root.querySelector('[data-wilayah-pilih]');
    const type = root.querySelector('[data-wilayah-ketik]');
    const toggle = root.querySelector('[data-wilayah-ganti]');
    const toggleText = root.querySelector('[data-wilayah-ganti-teks]');
    const selects = [...root.querySelectorAll('select[data-wilayah-tingkat]')].sort((a, b) => Number(a.dataset.wilayahTingkat) - Number(b.dataset.wilayahTingkat));
    const search = root.querySelector('[data-wilayah-cari-input]');
    const list = root.querySelector('[data-wilayah-cari-daftar]');
    const status = root.querySelector('[data-wilayah-status]');
    if (!pick || !type || selects.length === 0 || !window.fetch) {
        return;
    }

    const textFields = [...type.querySelectorAll('input, select, textarea')];
    let mode = t.wilayahMode === 'ketik' ? 'ketik' : 'pilih';

    const children = (parent) => {
        const key = parent || '';

        if (!cache.has(key)) {
            const url = `${t.urlDaftar}${key ? `?induk=${encodeURIComponent(key)}` : ''}`;
            const request = fetchJson(url).catch((error) => {
                cache.delete(key);
                throw error;
            });
            cache.set(key, request);
        }

        return cache.get(key);
    };

    const say = (text) => {
        if (status) {
            status.textContent = text;
        }
    };

    /* ---------- Selects ---------- */
    const placeholder = (select, waiting) => {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = waiting ? select.dataset.pilihDulu || select.dataset.pilih : select.dataset.pilih;

        return option;
    };

    const fill = (select, rows, value = '') => {
        const options = [placeholder(select, false)];

        rows.forEach((row) => {
            const option = document.createElement('option');
            option.value = row.code;
            option.textContent = row.label;
            options.push(option);
        });

        select.replaceChildren(...options);
        select.value = value;
        select.disabled = mode !== 'pilih';
    };

    const reset = (select) => {
        select.replaceChildren(placeholder(select, true));
        select.value = '';
        select.disabled = true;
    };

    const chosenPath = () => {
        const parts = selects.filter((s) => s.value !== '').map((s) => s.selectedOptions[0].textContent.trim());

        return parts.reverse().join(', ');
    };

    /* Loads the options of the select after index i for the chosen value; clears the ones below. */
    const loadBelow = (i, keep = '') => {
        selects.slice(i + 2).forEach(reset);

        const next = selects[i + 1];
        const parent = selects[i].value;

        if (!next) {
            return Promise.resolve();
        }

        if (!parent) {
            reset(next);

            return Promise.resolve();
        }

        reset(next);
        next.firstElementChild.textContent = t.teksMemuat;

        return children(parent)
            .then((rows) => fill(next, rows, keep))
            .catch(() => {
                reset(next);
                say(t.teksGagal);
            });
    };

    selects.forEach((select, i) => {
        select.addEventListener('change', () => {
            loadBelow(i).then(() => say(chosenPath() ? t.teksTerpilih.replace('{0}', chosenPath()) : ''));
        });
    });

    /* Sets every level from a search result: loads each list on the way down. */
    const applyResult = async (result) => {
        const codes = selects.map((select) => {
            const key = ['province', 'regency', 'district', 'village'][Number(select.dataset.wilayahTingkat) - 1];

            return result.codes[key] || '';
        });

        try {
            fill(selects[0], await children(''), codes[0]);

            for (let i = 1; i < selects.length; i++) {
                if (!codes[i - 1]) {
                    reset(selects[i]);
                    continue;
                }

                fill(selects[i], await children(codes[i - 1]), codes[i]);
            }
        } catch {
            say(t.teksGagal);

            return;
        }

        say(t.teksTerpilih.replace('{0}', chosenPath()));

        // A district was chosen: the village is next.
        const firstEmpty = selects.find((select) => !select.disabled && select.value === '');

        if (firstEmpty) {
            firstEmpty.focus();
        }
    };

    /* ---------- Search ---------- */
    let items = [];
    let active = -1;
    let timer = null;
    let controller = null;

    const close = () => {
        list.hidden = true;
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');
        active = -1;
    };

    const highlight = (i) => {
        items.forEach((li, n) => li.setAttribute('aria-selected', n === i ? 'true' : 'false'));
        active = i;

        if (items[i]) {
            search.setAttribute('aria-activedescendant', items[i].id);
            items[i].scrollIntoView({ block: 'nearest' });
        }
    };

    const kindOf = (row) => (row.level === 3 ? t.jenisKecamatan : row.kind === 'kelurahan' ? t.jenisKelurahan : t.jenisDesa);

    const render = (rows) => {
        list.replaceChildren();
        items = [];

        if (rows.length === 0) {
            const li = document.createElement('li');
            li.className = 'px-3 py-2 text-gray-500 dark:text-gray-300';
            li.textContent = t.teksKosong;
            list.append(li);
        }

        rows.forEach((row, n) => {
            const li = document.createElement('li');
            li.id = `wilayah-cari-pilihan-${n}`;
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', 'false');
            li.className = 'cursor-pointer px-3 py-2 text-gray-900 hover:bg-primary-50 aria-selected:bg-primary-100 dark:text-white dark:hover:bg-gray-600 dark:aria-selected:bg-gray-600';
            li.textContent = row.name;

            const small = document.createElement('span');
            small.className = 'block text-xs text-gray-500 dark:text-gray-300';
            small.textContent = `${kindOf(row)} · ${row.path.split(', ').slice(1).join(', ')}`;
            li.append(small);

            li.addEventListener('mousedown', (event) => {
                event.preventDefault();
                search.value = row.path;
                close();
                applyResult(row);
            });
            list.append(li);
            items.push(li);
        });

        list.hidden = false;
        search.setAttribute('aria-expanded', 'true');
        say(rows.length ? t.teksHasil.replace('{0}', String(rows.length)) : '');
        highlight(-1);
    };

    const find = () => {
        const q = search.value.trim();

        if (q.length < 3) {
            close();

            return;
        }

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        fetchJson(`${t.urlCari}?q=${encodeURIComponent(q)}`, controller.signal)
            .then((rows) => {
                if (document.activeElement === search) {
                    render(Array.isArray(rows) ? rows : []);
                }
            })
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    say(t.teksGagal);
                }
            });
    };

    if (search && list) {
        search.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(find, 250);
        });
        search.addEventListener('keydown', (event) => {
            if (list.hidden) {
                if (event.key === 'ArrowDown' && search.value.trim().length >= 3) {
                    event.preventDefault();
                    find();
                } else if (event.key === 'Enter') {
                    // Enter in the search box never sends the form.
                    event.preventDefault();
                    find();
                }

                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                highlight(Math.min(active + 1, items.length - 1));
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                highlight(Math.max(active - 1, 0));
            } else if (event.key === 'Enter') {
                event.preventDefault();

                if (active >= 0) {
                    items[active].dispatchEvent(new MouseEvent('mousedown'));
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close();
            }
        });
        search.addEventListener('blur', close);
    }

    /* ---------- Modes ---------- */
    const apply = () => {
        const picking = mode === 'pilih';
        pick.hidden = !picking;
        type.hidden = picking;
        textFields.forEach((field) => {
            field.disabled = picking;
        });

        if (search) {
            search.disabled = !picking;
        }

        selects.forEach((select, i) => {
            // A level waits until the one above it is chosen.
            select.disabled = !picking || (i > 0 && selects[i - 1].value === '');
        });

        if (toggleText) {
            toggleText.textContent = picking ? t.teksKetik : t.teksDaftar;
        }
    };

    if (toggle) {
        toggle.hidden = false;
        toggle.addEventListener('click', () => {
            if (mode === 'pilih') {
                // Carry the chosen names into the empty text fields.
                selects.forEach((select) => {
                    const key = ['province', 'regency', 'district', 'village'][Number(select.dataset.wilayahTingkat) - 1];
                    const field = type.querySelector(`[name="${key}"]`);

                    if (field && field.value.trim() === '' && select.value !== '') {
                        field.value = select.selectedOptions[0].textContent.trim();
                    }
                });
            }

            mode = mode === 'pilih' ? 'ketik' : 'pilih';
            apply();
            const first = mode === 'pilih' ? search || selects[0] : textFields[0];

            if (first) {
                first.focus();
            }
        });
    }

    apply();
}

export function initWilayah(root = document) {
    root.querySelectorAll('[data-wilayah]:not([data-wilayah-siap])').forEach((picker) => {
        picker.dataset.wilayahSiap = '1';
        initPicker(picker);
    });
}
