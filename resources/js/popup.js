/*
 * Home page popup (Situs module panel public_home): <dialog data-popup data-popup-versi="3"
 * data-popup-frekuensi="once|daily|always" [data-popup-pratinjau]>.
 *
 * Opens shortly after the page has loaded unless the visitor already closed this version:
 * - once:   not again until the school saves the popup (a new version),
 * - daily:  not again on the same day,
 * - always: on every visit to the home page.
 * The choice is kept in localStorage ("spmb-popup"); without storage the popup simply shows.
 * The admin preview always opens and remembers nothing. Esc, the close buttons and a click on
 * the dimmed background close it; showModal() keeps keyboard focus inside the dialog and
 * focus returns to where it was.
 */
const KEY = 'spmb-popup';

const read = () => {
    try {
        return JSON.parse(window.localStorage.getItem(KEY) || 'null');
    } catch {
        return null;
    }
};

const write = (value) => {
    try {
        window.localStorage.setItem(KEY, JSON.stringify(value));
    } catch {
        // Private mode or blocked storage: the popup shows again next time.
    }
};

const today = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

function shouldShow(version, frequency) {
    if (frequency === 'always') {
        return true;
    }

    const seen = read();

    if (!seen || seen.v !== version) {
        return true;
    }

    return frequency === 'daily' && seen.d !== today();
}

export function initPopup() {
    const dialog = document.querySelector('dialog[data-popup]');

    if (!dialog) {
        return;
    }

    const preview = dialog.hasAttribute('data-popup-pratinjau');
    const version = Number(dialog.dataset.popupVersi) || 0;
    const frequency = dialog.dataset.popupFrekuensi || 'once';

    if (!preview && !shouldShow(version, frequency)) {
        return;
    }

    const remember = () => {
        if (!preview) {
            write({ v: version, d: today() });
        }
    };

    const close = () => {
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
            remember();
        }
    };

    dialog.addEventListener('close', remember);
    dialog.addEventListener('click', (event) => {
        // A click on the backdrop lands on the <dialog> itself, outside its content box.
        if (event.target === dialog) {
            const box = dialog.getBoundingClientRect();
            const inside = event.clientX >= box.left && event.clientX <= box.right && event.clientY >= box.top && event.clientY <= box.bottom;

            if (!inside) {
                close();
            }
        }
    });
    dialog.querySelectorAll('[data-popup-tutup]').forEach((button) => button.addEventListener('click', close));
    dialog.querySelector('[data-popup-tombol]')?.addEventListener('click', remember);

    const open = () => {
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
        // Start on the dialog's close button rather than the first link in the text.
        dialog.querySelector('[data-popup-tutup]')?.focus();
    };

    if (document.readyState === 'complete') {
        window.setTimeout(open, 400);
    } else {
        window.addEventListener('load', () => window.setTimeout(open, 400), { once: true });
    }
}
