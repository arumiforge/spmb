/*
 * Friendly date picker (D-088), in the page's language (<html lang>: id, en or ar, D-109).
 *
 * Every <input type="date"> is enhanced (opt out with data-tanggal="asli"): the original input becomes
 * a hidden field that still carries name and ISO value (YYYY-MM-DD) for the server, and a text field
 * shows the date as "17 Agustus 2014". People can type ("17/08/2014", "17-8-2014", "17 agustus 2014")
 * or pick from a calendar with month and year lists, which makes birth dates quick to choose.
 * Keyboard: arrows move by day/week, PageUp/PageDown by month, Home/End to the week edges,
 * Enter picks, Escape closes. On right-to-left pages the left and right arrows follow the mirrored grid.
 */

const TEXTS = {
    id: {
        months: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        daysShort: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
        days: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        placeholder: 'Contoh: 17/08/2014',
        open: 'Pilih tanggal dari kalender',
        calendar: 'Kalender',
        prev: 'Bulan sebelumnya',
        next: 'Bulan berikutnya',
        month: 'Bulan',
        year: 'Tahun',
        today: 'Hari ini',
        clear: 'Hapus',
        invalid: 'Tulis tanggal seperti 17/08/2014, atau pilih dari kalender.',
        range: (from, until) => `Pilih tanggal${from ? ` mulai ${from}` : ''}${until ? ` sampai ${until}` : ''}.`,
    },
    en: {
        months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        placeholder: 'Example: 17/08/2014',
        open: 'Choose a date from the calendar',
        calendar: 'Calendar',
        prev: 'Previous month',
        next: 'Next month',
        month: 'Month',
        year: 'Year',
        today: 'Today',
        clear: 'Clear',
        invalid: 'Write the date like 17/08/2014 (day/month/year), or choose it from the calendar.',
        range: (from, until) => `Choose a date${from ? ` from ${from}` : ''}${until ? ` until ${until}` : ''}.`,
    },
    ar: {
        months: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
        daysShort: ['أحد', 'اثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'],
        days: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
        placeholder: 'مثال: 17/08/2014',
        open: 'اختر التاريخ من التقويم',
        calendar: 'التقويم',
        prev: 'الشهر السابق',
        next: 'الشهر التالي',
        month: 'الشهر',
        year: 'السنة',
        today: 'اليوم',
        clear: 'مسح',
        invalid: 'اكتب التاريخ بصيغة 17/08/2014 (يوم/شهر/سنة)، أو اختره من التقويم.',
        range: (from, until) => `اختر تاريخًا${from ? ` من ${from}` : ''}${until ? ` حتى ${until}` : ''}.`,
    },
};
const T = TEXTS[(document.documentElement.lang || 'id').slice(0, 2)] || TEXTS.id;
const MONTHS = T.months;
const DAYS_SHORT = T.daysShort;
const DAYS = T.days;
// Typed month names are recognised in the page's language and in Indonesian.
const TYPED_MONTHS = [MONTHS, TEXTS.id.months];
const RTL = document.documentElement.dir === 'rtl';

const pad = (n) => String(n).padStart(2, '0');
const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const fromIso = (value) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
    if (!m) {
        return null;
    }
    const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
    return d.getMonth() === Number(m[2]) - 1 ? d : null;
};
const sameDay = (a, b) => a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
const long = (d) => `${d.getDate()} ${MONTHS[d.getMonth()]} ${d.getFullYear()}`;

/* "17/08/2014", "17-8-2014", "17.8.14", "17 agustus 2014", "17 agu 2014", "2014-08-17" → Date or null. */
export function parseTanggal(text) {
    const value = String(text || '').trim().toLowerCase().replace(/,/g, ' ').replace(/\s+/g, ' ');
    if (value === '') {
        return null;
    }
    const isoDate = fromIso(value);
    if (isoDate) {
        return isoDate;
    }
    let day;
    let month;
    let year;
    let m = /^(\d{1,2})[/\-. ](\d{1,2})[/\-. ](\d{2,4})$/.exec(value);
    if (m) {
        [day, month, year] = [Number(m[1]), Number(m[2]), Number(m[3])];
    } else {
        m = /^(?:[a-z]+ )?(\d{1,2}) ([a-z]+) (\d{2,4})$/.exec(value);
        if (!m) {
            return null;
        }
        const index = TYPED_MONTHS.map((names) => names.findIndex((name) => name.toLowerCase().startsWith(m[2].slice(0, 3)))).find((i) => i >= 0) ?? -1;
        if (index < 0) {
            return null;
        }
        [day, month, year] = [Number(m[1]), index + 1, Number(m[3])];
    }
    if (year < 100) {
        const now = new Date().getFullYear() % 100;
        year += year > now + 10 ? 1900 : 2000;
    }
    const d = new Date(year, month - 1, day);
    return d.getFullYear() === year && d.getMonth() === month - 1 && d.getDate() === day ? d : null;
}

let uid = 0;

function enhance(original) {
    const id = original.id || `tanggal-${++uid}`;
    const min = fromIso(original.min);
    const max = fromIso(original.max);
    const birth = original.dataset.tanggal === 'lahir' || /lahir|birth/i.test(original.name);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // The visible field takes over the id (so the <label for> still points at what people type in).
    const field = document.createElement('input');
    field.type = 'text';
    field.id = id;
    field.className = `${original.className} pe-12`;
    field.autocomplete = 'off';
    field.inputMode = 'numeric';
    field.placeholder = original.placeholder || T.placeholder;
    field.required = original.required;
    field.disabled = original.disabled;
    ['aria-describedby', 'aria-invalid', 'title'].forEach((name) => {
        if (original.hasAttribute(name)) {
            field.setAttribute(name, original.getAttribute(name));
        }
    });
    original.removeAttribute('id');
    original.type = 'hidden';
    original.required = false;

    const wrap = document.createElement('div');
    wrap.className = 'tanggal';
    original.before(wrap);
    wrap.append(field, original);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'absolute inset-y-0 end-0 flex w-12 items-center justify-center rounded-e-lg text-gray-500 hover:text-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-400 dark:hover:text-white';
    button.setAttribute('aria-label', T.open);
    button.setAttribute('aria-haspopup', 'dialog');
    button.setAttribute('aria-expanded', 'false');
    button.innerHTML = '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>';
    wrap.append(button);

    const panel = document.createElement('div');
    panel.className = 'tanggal-panel';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'false');
    panel.setAttribute('aria-label', T.calendar);
    wrap.append(panel);

    let selected = fromIso(original.value);
    let view = selected ? new Date(selected) : new Date(birth ? today.getFullYear() - 12 : today.getFullYear(), birth ? 6 : today.getMonth(), 1);
    let focusDay = selected ? new Date(selected) : null;

    const allowed = (d) => (!min || d >= min) && (!max || d <= max);

    const show = () => {
        field.value = selected ? long(selected) : field.value;
        field.title = selected ? `${DAYS[selected.getDay()]}, ${long(selected)}` : '';
    };

    const commit = (d, close = true) => {
        selected = d;
        original.value = d ? iso(d) : '';
        field.value = d ? long(d) : '';
        field.setCustomValidity('');
        show();
        original.dispatchEvent(new Event('change', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        if (close) {
            closePanel(true);
        }
    };

    const yearRange = () => {
        const from = min ? min.getFullYear() : today.getFullYear() - (birth ? 25 : 100);
        const to = max ? max.getFullYear() : today.getFullYear() + (birth ? 0 : 10);
        return [Math.min(from, view.getFullYear()), Math.max(to, view.getFullYear())];
    };

    const render = () => {
        const year = view.getFullYear();
        const month = view.getMonth();
        const [fromYear, toYear] = yearRange();
        const first = new Date(year, month, 1);
        const start = new Date(year, month, 1 - first.getDay());
        if (!focusDay || focusDay.getMonth() !== month || focusDay.getFullYear() !== year) {
            focusDay = selected && selected.getMonth() === month && selected.getFullYear() === year ? new Date(selected) : new Date(year, month, Math.min(today.getDate(), 28));
        }

        let years = '';
        for (let y = toYear; y >= fromYear; y--) {
            years += `<option value="${y}"${y === year ? ' selected' : ''}>${y}</option>`;
        }
        let days = DAYS_SHORT.map((d, i) => `<div class="tanggal-hari" aria-hidden="true" title="${DAYS[i]}">${d}</div>`).join('');
        for (let i = 0; i < 42; i++) {
            const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
            const classes = ['tanggal-hari-btn'];
            if (d.getMonth() !== month) classes.push('is-luar');
            if (sameDay(d, today)) classes.push('is-hari-ini');
            if (sameDay(d, selected)) classes.push('is-pilih');
            const focus = sameDay(d, focusDay);
            days += `<button type="button" class="${classes.join(' ')}" data-hari="${iso(d)}" tabindex="${focus ? 0 : -1}"${allowed(d) ? '' : ' disabled'}`
                + `${sameDay(d, selected) ? ' aria-pressed="true"' : ''} aria-label="${DAYS[d.getDay()]}, ${long(d)}">${d.getDate()}</button>`;
        }

        panel.innerHTML = `
            <div class="tanggal-kepala">
                <button type="button" class="tanggal-geser" data-geser="-1" aria-label="${T.prev}"><svg class="rtl:-scale-x-100" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>
                <select data-bulan aria-label="${T.month}">${MONTHS.map((name, i) => `<option value="${i}"${i === month ? ' selected' : ''}>${name}</option>`).join('')}</select>
                <select data-tahun aria-label="${T.year}">${years}</select>
                <button type="button" class="tanggal-geser" data-geser="1" aria-label="${T.next}"><svg class="rtl:-scale-x-100" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>
            </div>
            <div class="tanggal-grid" role="group" aria-label="${MONTHS[month]} ${year}">${days}</div>
            <div class="tanggal-kaki">
                <button type="button" data-hari-ini${allowed(today) ? '' : ' disabled'}>${T.today}</button>
                <button type="button" data-hapus>${T.clear}</button>
            </div>`;
    };

    const focusGrid = () => panel.querySelector('.tanggal-hari-btn[tabindex="0"]')?.focus();

    const onOutside = (event) => {
        if (!wrap.contains(event.target)) {
            closePanel(false);
        }
    };

    function openPanel(focus = false) {
        if (!panel.hidden || field.disabled) {
            return;
        }
        if (selected) {
            view = new Date(selected.getFullYear(), selected.getMonth(), 1);
        }
        render();
        panel.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        const rect = wrap.getBoundingClientRect();
        panel.classList.toggle('is-atas', window.innerHeight - rect.bottom < 380 && rect.top > 400);
        document.addEventListener('pointerdown', onOutside);
        if (focus) {
            focusGrid();
        }
    }

    function closePanel(returnFocus) {
        if (panel.hidden) {
            return;
        }
        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        document.removeEventListener('pointerdown', onOutside);
        if (returnFocus) {
            field.focus();
        }
    }

    const moveView = (months) => {
        view = new Date(view.getFullYear(), view.getMonth() + months, 1);
        focusDay = null;
        render();
    };

    button.addEventListener('click', () => (panel.hidden ? openPanel(true) : closePanel(true)));
    field.addEventListener('click', () => openPanel(false));
    field.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' && event.altKey) {
            event.preventDefault();
            openPanel(true);
        } else if (event.key === 'Escape') {
            closePanel(false);
        }
    });
    field.addEventListener('input', () => {
        const d = parseTanggal(field.value);
        original.value = d && allowed(d) ? iso(d) : '';
        field.setCustomValidity('');
    });
    field.addEventListener('blur', () => {
        if (field.value.trim() === '') {
            selected = null;
            original.value = '';
            return;
        }
        const d = parseTanggal(field.value);
        if (!d) {
            field.setCustomValidity(T.invalid);
        } else if (!allowed(d)) {
            field.setCustomValidity(T.range(min ? long(min) : '', max ? long(max) : ''));
        } else {
            selected = d;
            original.value = iso(d);
            field.value = long(d);
            field.setCustomValidity('');
            view = new Date(d.getFullYear(), d.getMonth(), 1);
        }
    });

    panel.addEventListener('change', (event) => {
        if (event.target.matches('[data-bulan], [data-tahun]')) {
            view = new Date(Number(panel.querySelector('[data-tahun]').value), Number(panel.querySelector('[data-bulan]').value), 1);
            focusDay = null;
            const keep = event.target.matches('[data-bulan]') ? '[data-bulan]' : '[data-tahun]';
            render();
            panel.querySelector(keep)?.focus();
        }
    });
    panel.addEventListener('click', (event) => {
        const day = event.target.closest('[data-hari]');
        if (day && !day.disabled) {
            commit(fromIso(day.dataset.hari));
        } else if (event.target.closest('[data-geser]')) {
            const dir = Number(event.target.closest('[data-geser]').dataset.geser);
            moveView(dir);
            panel.querySelector(`[data-geser="${dir}"]`)?.focus();
        } else if (event.target.closest('[data-hari-ini]')) {
            commit(new Date(today));
        } else if (event.target.closest('[data-hapus]')) {
            commit(null);
        }
    });
    panel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closePanel(true);
            return;
        }
        const day = event.target.closest('[data-hari]');
        if (!day) {
            return;
        }
        const current = fromIso(day.dataset.hari);
        const moves = { ArrowLeft: RTL ? 1 : -1, ArrowRight: RTL ? -1 : 1, ArrowUp: -7, ArrowDown: 7, Home: -current.getDay(), End: 6 - current.getDay() };
        let next = null;
        if (event.key in moves) {
            next = new Date(current.getFullYear(), current.getMonth(), current.getDate() + moves[event.key]);
        } else if (event.key === 'PageUp' || event.key === 'PageDown') {
            next = new Date(current.getFullYear(), current.getMonth() + (event.key === 'PageUp' ? -1 : 1), current.getDate());
        }
        if (next) {
            event.preventDefault();
            focusDay = next;
            if (next.getMonth() !== view.getMonth() || next.getFullYear() !== view.getFullYear()) {
                view = new Date(next.getFullYear(), next.getMonth(), 1);
            }
            render();
            focusGrid();
        }
    });
    wrap.addEventListener('focusout', (event) => {
        if (!panel.hidden && event.relatedTarget && !wrap.contains(event.relatedTarget)) {
            closePanel(false);
        }
    });

    // The server may send back an old value after a failed submit.
    if (selected) {
        field.value = long(selected);
        show();
    }
}

export function initTanggal(root = document) {
    root.querySelectorAll('input[type="date"]:not([data-tanggal="asli"])').forEach(enhance);
}
