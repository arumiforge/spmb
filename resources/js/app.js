/*
 * SPMB main script: Flowbite components (drawer, dropdown, modal, collapse,
 * dismiss) are initialised from data attributes by the flowbite package.
 */
import 'flowbite';
import { initDialogs, initFolds, initTableOfContents, initSlideshow, initIcons3d, initPageSearch } from './ui.js';
import { initTanggal } from './tanggal.js';
import { initLipatan } from './lipatan.js';
import { initGuardianChoice, initWizards } from './wizard.js';
import { initBerita } from './berita.js';
import { initPopup } from './popup.js';

const storage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Private mode or blocked storage: the preference is simply not remembered.
        }
    },
};

/* Dark mode toggle: <button data-theme-toggle>. The initial class is set inline in <head>. */
function initThemeToggle() {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const dark = document.documentElement.classList.toggle('dark');
            storage.set('spmb-theme', dark ? 'dark' : 'light');
        });
    });
}

/* Desktop sidebar: collapse to icons only (html.sidebar-collapsed, see app.css), remembered per browser. */
function initSidebarCollapse() {
    const button = document.querySelector('[data-sidebar-collapse]');

    if (!button) {
        return;
    }

    const apply = (collapsed) => {
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };

    apply(storage.get('spmb-sidebar') === 'collapsed');

    button.addEventListener('click', () => {
        const collapse = !document.documentElement.classList.contains('sidebar-collapsed');
        storage.set('spmb-sidebar', collapse ? 'collapsed' : 'expanded');
        apply(collapse);
    });
}

/* Toasts close themselves after a few seconds (errors stay until closed). */
function initToasts() {
    document.querySelectorAll('[data-toast-autohide]').forEach((toast) => {
        window.setTimeout(() => {
            toast.classList.add('opacity-0');
            window.setTimeout(() => toast.remove(), 300);
        }, 6000);
    });
}

/* Confirm before submitting destructive forms: <form data-confirm="Hapus ...?">. */
function initConfirm() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}

/* Copy text to the clipboard: <button data-copy="text">. */
function initCopy() {
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                const original = button.innerHTML;
                button.textContent = button.dataset.copiedLabel || 'Tersalin';
                window.setTimeout(() => {
                    button.innerHTML = original;
                }, 2000);
            } catch {
                // Clipboard not available: the text is still visible to copy by hand.
            }
        });
    });
}

/* Show/hide password: <button data-password-toggle="input-id">. */
function initPasswordToggle() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) {
                return;
            }
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    });
}

/* Switch label next to a toggle: <label><span data-toggle-text data-on="Menyala" data-off="Mati"></span><input type="checkbox"></label>. */
function initToggleText() {
    document.querySelectorAll('[data-toggle-text]').forEach((text) => {
        const input = text.closest('label')?.querySelector('input[type="checkbox"]');
        if (!input) {
            return;
        }
        input.addEventListener('change', () => {
            text.textContent = input.checked ? text.dataset.on : text.dataset.off;
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initSidebarCollapse();
    initToasts();
    initConfirm();
    initCopy();
    initPasswordToggle();
    initToggleText();
    initDialogs();
    initTanggal();
    initGuardianChoice();
    initWizards();
    initFolds();
    initLipatan();
    initTableOfContents();
    initPageSearch();
    initSlideshow();
    initIcons3d();
    initBerita();
    initPopup();
});
