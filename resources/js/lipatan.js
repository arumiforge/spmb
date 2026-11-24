/*
 * Folding cards in the dashboard and on settings pages (D-100): native
 * <details data-lipatan="<id>"> from partials/lipatan_buka. Without JavaScript
 * they still open and close; this script adds:
 * - remembering each card's state per browser (localStorage, per page path),
 * - opening a card that holds a validation error (server-side or the browser's),
 * - "Buka semua" / "Tutup semua" buttons ([data-lipatan-semua="buka|tutup"]),
 * - the "Isi halaman" rail beside settings forms from 2xl (partials/tata_isi_rail, D-102):
 *   one link per card, opening and scrolling to it, highlighting the card in view.
 */
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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

    initRail();
}

function initRail() {
    document.querySelectorAll('[data-isi-halaman]').forEach((rail) => {
        const scope = rail.closest('[data-lipatan-lingkup]') ?? document;
        const list = rail.querySelector('[data-isi-halaman-daftar]');
        const cards = [...scope.querySelectorAll('details[data-lipatan]')];

        if (!list || cards.length < 2) {
            return;
        }

        const links = new Map();

        cards.forEach((details) => {
            details.id ||= `bagian-${details.dataset.lipatan}`;
            const title = details.querySelector('summary h2, summary h3')?.textContent.trim() ?? details.dataset.lipatan;
            const item = document.createElement('li');
            const link = document.createElement('a');
            link.href = `#${details.id}`;
            link.className = 'flex items-center gap-2 border-s-2 border-transparent py-1 ps-3 text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white';
            link.textContent = title;

            if (hasError(details)) {
                const dot = document.createElement('span');
                dot.className = 'h-2 w-2 shrink-0 rounded-full bg-red-600';
                dot.setAttribute('role', 'img');
                dot.setAttribute('aria-label', rail.dataset.errorLabel || 'perlu diperbaiki');
                link.append(dot);
            }

            link.addEventListener('click', (event) => {
                event.preventDefault();
                details.open = true;
                details.scrollIntoView({ behavior: reducedMotion() ? 'auto' : 'smooth', block: 'start' });
                details.querySelector('summary')?.focus({ preventScroll: true });
                history.replaceState(null, '', `#${details.id}`);
            });

            item.append(link);
            list.append(item);
            links.set(details, link);
        });

        rail.hidden = false;

        if ('IntersectionObserver' in window) {
            const visible = new Set();
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => (entry.isIntersecting ? visible.add(entry.target) : visible.delete(entry.target)));
                const current = cards.find((card) => visible.has(card));
                links.forEach((link, card) => link.setAttribute('aria-current', card === current ? 'true' : 'false'));
            }, { rootMargin: '-80px 0px -55% 0px' });

            cards.forEach((card) => observer.observe(card));
        }
    });
}
