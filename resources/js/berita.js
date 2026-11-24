/*
 * News ticker under the public header ("Berita singkat", Situs module panel public_top).
 *
 * Markup: [data-berita][data-berita-jeda=ms] > [data-berita-daftar] > [data-berita-item].is-tampil,
 * optional [data-berita-kontrol] with previous / pause-play / next buttons.
 *
 * One item shows at a time and the next one fades in every data-berita-jeda ms. Rotation pauses while
 * the pointer or keyboard focus is inside the bar, while the tab is hidden, and after the pause button.
 * With prefers-reduced-motion the bar does not rotate by itself and changes without animation; the
 * buttons still step through the items. aria-live is "off" while rotating and "polite" otherwise, so a
 * screen reader only announces an item the visitor asked for.
 */
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initTicker(bar) {
    const list = bar.querySelector('[data-berita-daftar]');
    const items = [...bar.querySelectorAll('[data-berita-item]')];
    const controls = bar.querySelector('[data-berita-kontrol]');

    if (!list || items.length < 2 || !controls) {
        return;
    }

    const toggle = controls.querySelector('[data-berita-jeda-tombol]');
    const delay = Math.max(3000, Number(bar.dataset.beritaJeda) || 5000);
    let index = Math.max(0, items.findIndex((item) => item.classList.contains('is-tampil')));
    let stopped = reducedMotion(); // Paused with the button (or reduced motion).
    let hovering = false;
    let timer = null;

    const show = (next) => {
        items[index].classList.remove('is-tampil');
        index = (next + items.length) % items.length;
        items[index].classList.add('is-tampil');
    };

    const running = () => !stopped && !hovering && !document.hidden;

    const updateLive = () => {
        list.setAttribute('aria-live', stopped ? 'polite' : 'off');
    };

    const updateToggle = () => {
        toggle.setAttribute('aria-pressed', stopped ? 'true' : 'false');
        toggle.querySelector('[data-ikon-jeda]').hidden = stopped;
        toggle.querySelector('[data-ikon-putar]').hidden = !stopped;
        toggle.querySelector('[data-berita-jeda-label]').textContent = stopped ? toggle.dataset.labelPutar : toggle.dataset.labelJeda;
    };

    const restart = () => {
        window.clearInterval(timer);
        timer = window.setInterval(() => {
            if (running()) {
                show(index + 1);
            }
        }, delay);
    };

    controls.classList.remove('hidden');
    controls.classList.add('flex');
    bar.dataset.beritaSiap = '';
    updateLive();
    updateToggle();
    restart();

    controls.querySelector('[data-berita-sebelum]').addEventListener('click', () => {
        show(index - 1);
        restart();
    });
    controls.querySelector('[data-berita-berikut]').addEventListener('click', () => {
        show(index + 1);
        restart();
    });
    toggle.addEventListener('click', () => {
        stopped = !stopped;
        updateLive();
        updateToggle();
        restart();
    });

    // Pause while the visitor reads (pointer) or moves through the links and buttons (keyboard).
    bar.addEventListener('mouseenter', () => { hovering = true; });
    bar.addEventListener('mouseleave', () => { hovering = false; });
    bar.addEventListener('focusin', () => { hovering = true; });
    bar.addEventListener('focusout', (event) => {
        if (!bar.contains(event.relatedTarget)) {
            hovering = false;
        }
    });
}

export function initBerita() {
    document.querySelectorAll('[data-berita]').forEach(initTicker);
}
