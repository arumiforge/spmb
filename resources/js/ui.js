/*
 * Small page behaviours added in the November 2026 UI round (D-081…D-088):
 * dialogs, "Lihat selengkapnya" folds, table of contents highlight, hero slideshow
 * and the lazy loader of the three.js icons.
 */

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* <button data-dialog-open="id"> opens <dialog id="id">; [data-dialog-close] or a click on the backdrop closes it. */
export function initDialogs() {
    document.querySelectorAll('[data-dialog-open]').forEach((button) => {
        const dialog = document.getElementById(button.dataset.dialogOpen);
        if (!dialog || typeof dialog.showModal !== 'function') {
            return;
        }
        button.addEventListener('click', () => {
            dialog.showModal();
            dialog.querySelector('input:not([type="hidden"])')?.focus();
        });
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
        dialog.querySelectorAll('[data-dialog-close]').forEach((close) => close.addEventListener('click', () => dialog.close()));
    });
}

/*
 * Folds (D-088). Markup: <div class="lipat" data-lipat [data-lipat-tinggi="12rem"] [data-lipat-media="(width < 64rem)"]>
 * <div class="lipat-isi">…</div></div>. Only content clearly taller than the limit is folded; without
 * JavaScript everything stays open. A link to an anchor inside a fold opens it.
 */
const labels = () => ({
    more: document.documentElement.dataset.lipatLebih || 'Lihat selengkapnya',
    less: document.documentElement.dataset.lipatKurang || 'Tutup',
});

let foldCount = 0;

function makeFold(fold) {
    const body = fold.querySelector(':scope > .lipat-isi');
    if (!body || fold.dataset.lipatSiap !== undefined) {
        return;
    }
    if (fold.dataset.lipatTinggi) {
        fold.style.setProperty('--lipat-tinggi', fold.dataset.lipatTinggi);
    }
    const media = fold.dataset.lipatMedia ? window.matchMedia(fold.dataset.lipatMedia) : null;
    body.id ||= `lipat-${++foldCount}`;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'lipat-tombol';
    button.setAttribute('aria-controls', body.id);
    button.innerHTML = '<span></span><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';

    const setOpen = (open) => {
        fold.classList.toggle('is-terbuka', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.firstElementChild.textContent = open ? labels().less : labels().more;
    };

    const update = () => {
        const active = !media || media.matches;
        fold.removeAttribute('data-lipat-siap');
        button.remove();
        if (!active) {
            return;
        }
        fold.setAttribute('data-lipat-siap', '');
        const limit = parseFloat(getComputedStyle(body).maxHeight) || 192;
        if (body.scrollHeight <= limit + 48) {
            fold.removeAttribute('data-lipat-siap');
            return;
        }
        fold.append(button);
        setOpen(fold.classList.contains('is-terbuka'));
    };

    button.addEventListener('click', () => {
        const open = !fold.classList.contains('is-terbuka');
        setOpen(open);
        if (!open && fold.getBoundingClientRect().top < 0) {
            fold.scrollIntoView({ block: 'start', behavior: reducedMotion() ? 'auto' : 'smooth' });
        }
    });
    fold.addEventListener('lipat:buka', () => setOpen(true));
    media?.addEventListener('change', update);
    update();
}

/*
 * Long guide pages: every <h2> section inside [data-lipat-bagian] becomes its own fold, so the page
 * shows the outline first and each part opens with "Lihat selengkapnya".
 */
function splitSections(container) {
    let seenHeading = false;
    let current = null;
    [...container.children].forEach((node) => {
        if (node.tagName === 'H2') {
            seenHeading = true;
            current = null;
            return;
        }
        if (!seenHeading) {
            return; // the introduction before the first heading stays open
        }
        if (!current) {
            current = document.createElement('div');
            current.className = 'lipat';
            current.dataset.lipat = '';
            current.dataset.lipatTinggi = container.dataset.lipatBagian || '16rem';
            const body = document.createElement('div');
            body.className = 'lipat-isi';
            current.append(body);
            node.before(current);
        }
        current.firstElementChild.append(node);
    });
}

export function initFolds() {
    document.querySelectorAll('[data-lipat-bagian]').forEach(splitSections);
    document.querySelectorAll('[data-lipat]').forEach(makeFold);

    const openTarget = (hash) => {
        if (!hash || hash.length < 2) {
            return;
        }
        const target = document.getElementById(decodeURIComponent(hash.slice(1)));
        if (!target) {
            return;
        }
        // A heading opens the fold right after it; anything inside a fold opens that fold.
        const folds = [target.closest('.lipat'), target.nextElementSibling?.classList.contains('lipat') ? target.nextElementSibling : null];
        folds.filter(Boolean).forEach((fold) => fold.dispatchEvent(new Event('lipat:buka')));
    };
    window.addEventListener('hashchange', () => openTarget(window.location.hash));
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href^="#"]');
        if (link) {
            openTarget(link.getAttribute('href'));
        }
    });
    openTarget(window.location.hash);
}

/*
 * Table of contents (D-088): <nav data-daftar-isi> with links to #ids on the page. The link of the
 * section in view gets .is-aktif and aria-current="location".
 */
export function initTableOfContents() {
    document.querySelectorAll('[data-daftar-isi]').forEach((nav) => {
        const links = [...nav.querySelectorAll('a[href^="#"]')];
        const targets = links
            .map((link) => document.getElementById(decodeURIComponent(link.getAttribute('href').slice(1))))
            .filter(Boolean);
        if (targets.length === 0) {
            return;
        }
        const setActive = (id) => {
            links.forEach((link) => {
                const on = link.getAttribute('href') === `#${id}`;
                link.classList.toggle('is-aktif', on);
                if (on) {
                    link.setAttribute('aria-current', 'location');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };
        const update = () => {
            // The last heading that passed the upper third of the screen is the part being read.
            let active = targets[0].id;
            targets.forEach((target) => {
                if (target.getBoundingClientRect().top <= window.innerHeight * 0.35) {
                    active = target.id;
                }
            });
            setActive(active);
        };
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(() => {
                    ticking = false;
                    update();
                });
            }
        }, { passive: true });
        update();
        links.forEach((link) => link.addEventListener('click', () => setActive(link.getAttribute('href').slice(1))));
    });
}

/*
 * Hero slideshow (D-084): [data-slide-hero] with .slide-hero-item children. Crossfades every
 * data-jeda ms (default 6000), no dots; pauses on hover and focus and in a hidden tab; under
 * reduced motion only the first picture shows.
 */
export function initSlideshow() {
    document.querySelectorAll('[data-slide-hero]').forEach((show) => {
        const slides = [...show.querySelectorAll('.slide-hero-item')];
        if (slides.length < 2 || reducedMotion()) {
            return;
        }
        let index = slides.findIndex((slide) => slide.classList.contains('is-tampil'));
        index = index < 0 ? 0 : index;
        let paused = false;
        const step = () => {
            if (paused || document.hidden) {
                return;
            }
            slides[index].classList.remove('is-tampil');
            slides[index].setAttribute('aria-hidden', 'true');
            index = (index + 1) % slides.length;
            const next = slides[index];
            next.querySelectorAll('img[loading="lazy"]').forEach((img) => img.removeAttribute('loading'));
            next.classList.add('is-tampil');
            next.removeAttribute('aria-hidden');
        };
        // Warm up the next pictures once the page is idle, so a crossfade never shows a half-loaded image.
        (window.requestIdleCallback || ((fn) => window.setTimeout(fn, 1500)))(() => {
            show.querySelectorAll('img[loading="lazy"]').forEach((img) => img.removeAttribute('loading'));
        });
        window.setInterval(step, Number(show.dataset.jeda) || 6000);
        ['mouseenter', 'focusin'].forEach((type) => show.addEventListener(type, () => { paused = true; }));
        ['mouseleave', 'focusout'].forEach((type) => show.addEventListener(type, () => { paused = false; }));
    });
}

/*
 * three.js icons (D-082). [data-ikon-3d] wraps a Codex image; the 3D script is fetched only when such
 * icons exist, after the page is idle, with WebGL, enough device power and no reduced motion.
 */
export function initIcons3d() {
    const holder = document.querySelector('[data-ikon-3d-src]');
    if (!holder || reducedMotion() || !document.querySelector('[data-ikon-3d] img')) {
        return;
    }
    const connection = navigator.connection;
    if (connection?.saveData || (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) || (navigator.deviceMemory && navigator.deviceMemory < 2)) {
        return;
    }
    const load = () => {
        const script = document.createElement('script');
        script.src = holder.dataset.ikon3dSrc;
        script.defer = true;
        document.body.append(script);
    };
    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(load, { timeout: 3000 });
    } else {
        window.setTimeout(load, 1200);
    }
}
