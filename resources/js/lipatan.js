/*
 * Folding cards in the dashboard and on settings pages (D-100): native
 * <details data-lipatan="<id>"> from partials/lipatan_buka. Without JavaScript
 * they still open and close; this script adds:
 * - remembering each card's state per browser (localStorage, per page path),
 * - opening a card that holds a validation error (server-side or the browser's),
 * - "Buka semua" / "Tutup semua" buttons ([data-lipatan-semua="buka|tutup"]).
 */
const key = (details) => `lipatan:${window.location.pathname}:${details.dataset.lipatan}`;

const read = (name) => {
    try {
        return window.localStorage.getItem(name);
    } catch {
        return null;
    }
};

const write = (name, value) => {
    try {
        window.localStorage.setItem(name, value);
    } catch {
        // Private mode or blocked storage: the state is simply not remembered.
    }
};

const hasError = (details) => details.querySelector('[aria-invalid="true"], .text-red-600, [data-field-error]') !== null;

export function initLipatan() {
    const cards = [...document.querySelectorAll('details[data-lipatan]')];

    if (cards.length === 0) {
        return;
    }

    cards.forEach((details) => {
        const saved = read(key(details));

        if (hasError(details)) {
            details.open = true;
        } else if (saved !== null) {
            details.open = saved === '1';
        }

        details.addEventListener('toggle', () => {
            write(key(details), details.open ? '1' : '0');

            // Charts drawn while the card was closed have no width; a resize makes them redraw.
            if (details.open && details.querySelector('[data-chart]')) {
                window.dispatchEvent(new Event('resize'));
            }
        });
    });

    // The browser cannot focus an invalid field inside a closed card: open it first.
    document.addEventListener('invalid', (event) => {
        const details = event.target.closest?.('details[data-lipatan]');

        if (details && !details.open) {
            details.open = true;
        }
    }, true);

    document.querySelectorAll('[data-lipatan-tombol]').forEach((bar) => {
        bar.hidden = false;
    });

    document.querySelectorAll('[data-lipatan-semua]').forEach((button) => {
        button.addEventListener('click', () => {
            const open = button.dataset.lipatanSemua === 'buka';
            const scope = button.closest('[data-lipatan-lingkup]') ?? document;

            scope.querySelectorAll('details[data-lipatan]').forEach((details) => {
                details.open = open;
            });
        });
    });
}
