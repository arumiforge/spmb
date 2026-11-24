/*
 * Step-by-step forms (wizard) and the guardian question.
 *
 * <form data-wizard> holds sections [data-wizard-langkah="N"] and a stepper (app/Views/partials/stepper.php).
 * It stays one form with one POST: the script only shows one step at a time, checks the fields of the
 * current step with the browser's constraint validation before moving on, moves focus to the step heading,
 * keeps the step in the URL (#langkah-2) so the browser's Back button works, and builds the summary of the
 * last step ([data-wizard-ringkasan]). Without JavaScript the whole form shows on one page.
 *
 * [data-wali]: the guardian fields ([data-wali-isian]) show only after "Ada wali" (radio value 1).
 */

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* "Ada wali?": guardian fields only after "Ada wali"; switched off (not sent, not checked) otherwise. */
export function initGuardianChoice(root = document) {
    root.querySelectorAll('[data-wali]').forEach((box) => {
        const choices = box.querySelectorAll('[data-wali-pilihan]');
        const fields = box.querySelector('[data-wali-isian]');
        const note = box.querySelector('[data-wali-catatan]');

        if (!fields || choices.length === 0) {
            return;
        }

        const apply = () => {
            const has = [...choices].some((choice) => choice.checked && choice.value === '1');
            fields.hidden = !has;
            fields.querySelectorAll('input, select, textarea, button').forEach((control) => {
                control.disabled = !has;
            });
        };

        if (note) {
            note.hidden = true;
        }

        choices.forEach((choice) => choice.addEventListener('change', apply));
        apply();
    });
}

/* Label text without the "(tidak wajib)" mark. */
function labelText(label) {
    const copy = label.cloneNode(true);
    copy.querySelectorAll('span').forEach((span) => span.remove());

    return copy.textContent.replace(/\s+/g, ' ').trim();
}

function valueText(control) {
    if (control.tagName === 'SELECT') {
        const option = control.selectedOptions[0];

        return option && option.value !== '' ? option.textContent.trim() : '';
    }

    return control.value.trim();
}

function usable(control) {
    return !control.disabled && !control.closest('[hidden]') && control.type !== 'hidden';
}

function initWizard(form) {
    const steps = [...form.querySelectorAll('[data-wizard-langkah]')];
    const stepper = form.querySelector('[data-stepper]');

    if (steps.length < 2 || !stepper) {
        return;
    }

    const total = steps.length;
    const items = [...stepper.querySelectorAll('[data-stepper-item]')];
    const back = form.querySelector('[data-wizard-kembali]');
    const next = form.querySelector('[data-wizard-lanjut]');
    const submit = form.querySelector('[data-wizard-kirim]');
    const summary = form.querySelector('[data-wizard-ringkasan]');
    const texts = stepper.dataset;
    const filled = form.dataset.wizardTerisi === '1';
    const done = new Set();
    const errors = new Set(steps.filter((step) => step.hasAttribute('data-ada-salah')).map((step) => Number(step.dataset.wizardLangkah)));
    let current = 1;

    const stepOf = (n) => steps[n - 1];
    const fromHash = () => {
        const match = /^#langkah-(\d+)$/.exec(window.location.hash);
        const n = match ? Number(match[1]) : 1;

        return n >= 1 && n <= total ? n : 1;
    };

    if (filled) {
        steps.forEach((step, i) => {
            if (!errors.has(i + 1)) {
                done.add(i + 1);
            }
        });
    }

    /* ---------- Field messages (the browser's own bubbles are not in Indonesian everywhere) ---------- */
    const clearError = (control) => {
        const message = document.getElementById(`${control.id}-wizard-error`);

        if (message) {
            message.remove();
            const describedBy = (control.getAttribute('aria-describedby') || '').split(' ').filter((id) => id && id !== message.id);
            control.setAttribute('aria-describedby', describedBy.join(' '));

            if (!document.getElementById(`${control.id}-error`)) {
                control.removeAttribute('aria-invalid');
            }
        }
    };

    const showError = (control) => {
        if (!control.id || document.getElementById(`${control.id}-wizard-error`)) {
            return;
        }

        const message = document.createElement('p');
        message.id = `${control.id}-wizard-error`;
        message.className = 'mt-2 text-sm text-red-600 dark:text-red-500';
        const missing = control.type === 'checkbox' ? form.dataset.pesanCentang || form.dataset.pesanWajib : form.dataset.pesanWajib;
        message.textContent = control.validity.valueMissing ? missing : form.dataset.pesanFormat;
        const holder = control.closest('.bidang, [data-school-picker]') || control.parentElement;
        holder.append(message);
        control.setAttribute('aria-invalid', 'true');
        control.setAttribute('aria-describedby', `${control.getAttribute('aria-describedby') || ''} ${message.id}`.trim());

        const clear = () => {
            if (control.checkValidity()) {
                clearError(control);
            }
        };
        control.addEventListener('input', clear);
        control.addEventListener('change', clear);
    };

    /* The invalid controls of step n (and shows their messages when mark is true). */
    const invalidIn = (n, mark) => {
        const invalid = [...stepOf(n).querySelectorAll('input, select, textarea')].filter((control) => usable(control) && control.willValidate && !control.checkValidity());

        if (mark) {
            invalid.forEach(showError);
        }

        return invalid;
    };

    /* ---------- Stepper and buttons ---------- */
    const render = () => {
        items.forEach((item, i) => {
            const n = i + 1;
            const state = n === current ? 'aktif' : done.has(n) ? 'selesai' : 'nanti';
            const link = item.querySelector('[data-wizard-ke]');
            const status = item.querySelector('[data-stepper-status]');
            item.classList.remove('is-aktif', 'is-selesai', 'is-nanti');
            item.classList.add(`is-${state}`);
            item.classList.toggle('has-error', errors.has(n));

            if (n === current) {
                link.setAttribute('aria-current', 'step');
            } else {
                link.removeAttribute('aria-current');
            }

            if (status) {
                const note = errors.has(n) ? texts.teksSalah : { aktif: texts.teksSekarang, selesai: texts.teksSelesai, nanti: texts.teksNanti }[state];
                status.textContent = `, ${note}`;
            }
        });

        const counter = stepper.querySelector('[data-stepper-hitung]');
        const title = stepper.querySelector('[data-stepper-judul]');
        const bar = stepper.querySelector('[data-stepper-kemajuan]');

        if (counter) {
            counter.textContent = texts.teksHitung.replace('{0}', String(current)).replace('{1}', String(total));
        }

        if (title) {
            title.textContent = stepOf(current).dataset.judul || '';
        }

        if (bar) {
            bar.style.setProperty('--kemajuan', `${(current / total) * 100}%`);
        }

        steps.forEach((step, i) => step.classList.toggle('is-aktif', i + 1 === current));

        if (back) {
            back.hidden = current === 1;
        }

        if (next) {
            next.hidden = current === total;
        }

        if (submit) {
            submit.hidden = current !== total;
        }
    };

    /* ---------- Summary of the last step ---------- */
    const buildSummary = () => {
        if (!summary) {
            return;
        }

        const t = summary.dataset;
        summary.replaceChildren();

        steps.slice(0, -1).forEach((step, i) => {
            const card = document.createElement('div');
            card.className = 'wizard-ringkasan-kartu';

            const head = document.createElement('div');
            head.className = 'wizard-ringkasan-kepala';
            const title = document.createElement('h3');
            title.textContent = step.dataset.judul || '';
            const edit = document.createElement('button');
            edit.type = 'button';
            edit.className = 'wizard-ringkasan-ubah';
            edit.dataset.wizardKe = String(i + 1);
            edit.textContent = t.teksUbah;
            edit.setAttribute('aria-label', (t.teksUbahLabel || t.teksUbah).replace('{0}', step.dataset.judul || ''));
            head.append(title, edit);

            const list = document.createElement('dl');
            list.className = 'wizard-ringkasan-daftar';

            step.querySelectorAll('label[for], fieldset[data-ringkas-pilihan]').forEach((node) => {
                let label = '';
                let value = '';

                if (node.tagName === 'FIELDSET') {
                    if (node.closest('[hidden]')) {
                        return;
                    }

                    const legend = node.querySelector('legend');
                    const checked = node.querySelector('input:checked');
                    label = node.dataset.ringkasLabel || (legend ? legend.textContent.trim() : '');
                    const choice = checked ? checked.closest('label') : null;
                    const choiceTitle = choice ? choice.querySelector('.pilihan-kartu-judul') : null;
                    value = choiceTitle ? choiceTitle.textContent.trim() : '';
                } else {
                    const control = document.getElementById(node.htmlFor);

                    if (!control || !usable(control) || ['radio', 'checkbox'].includes(control.type)) {
                        return;
                    }

                    label = labelText(node);
                    value = valueText(control);
                }

                const row = document.createElement('div');
                const dt = document.createElement('dt');
                const dd = document.createElement('dd');
                dt.textContent = label;
                dd.textContent = value === '' ? t.teksKosong : value;

                if (value === '') {
                    dd.classList.add('is-kosong');
                }

                row.append(dt, dd);
                list.append(row);
            });

            card.append(head, list);
            summary.append(card);
        });
    };

    /* ---------- Moving between steps ---------- */
    const show = (n, { focus = true, push = true } = {}) => {
        current = Math.max(1, Math.min(total, n));
        render();

        if (current === total) {
            buildSummary();
        }

        if (push && window.location.hash !== `#langkah-${current}`) {
            window.history.pushState({ wizard: current }, '', current === 1 && !window.location.hash ? window.location.pathname + window.location.search : `#langkah-${current}`);
        }

        if (focus) {
            const heading = stepOf(current).querySelector('h2');

            if (heading) {
                heading.focus({ preventScroll: true });
            }

            const top = stepper.getBoundingClientRect().top;

            if (top < 0 || top > window.innerHeight * 0.4) {
                stepper.scrollIntoView({ block: 'start', behavior: reduceMotion() ? 'auto' : 'smooth' });
            }
        }
    };

    /* Goes to step n; going forward checks every step on the way and stops at the first one that is not complete. */
    const go = (n, options = {}) => {
        const target = Math.max(1, Math.min(total, n));

        for (let step = current; step < target; step++) {
            const invalid = invalidIn(step, true);

            if (invalid.length > 0) {
                errors.add(step);

                if (step !== current) {
                    show(step, { ...options, focus: false });
                } else {
                    render();
                }

                invalid[0].focus();

                return false;
            }

            errors.delete(step);
            done.add(step);
        }

        show(target, options);

        return true;
    };

    form.addEventListener('click', (event) => {
        const link = event.target.closest('[data-wizard-ke]');

        if (link && form.contains(link)) {
            event.preventDefault();
            go(Number(link.dataset.wizardKe));
        }
    });

    // Pressing a button must not take the focus first: a field that reacts to losing focus (the school
    // picker writes a note) would move the button away before the click lands.
    [back, next, submit].forEach((button) => button?.addEventListener('mousedown', (event) => event.preventDefault()));

    if (next) {
        next.addEventListener('click', () => go(current + 1));
    }

    if (back) {
        back.addEventListener('click', () => show(current - 1));
    }

    form.addEventListener('submit', (event) => {
        // Enter in a field of an earlier step moves on instead of sending the form.
        if (current < total) {
            event.preventDefault();
            go(current + 1);

            return;
        }

        for (let step = 1; step <= total; step++) {
            const invalid = invalidIn(step, true);

            if (invalid.length > 0) {
                event.preventDefault();
                errors.add(step);
                show(step, { focus: false });
                invalid[0].focus();

                return;
            }
        }
    });

    window.addEventListener('popstate', () => {
        const n = fromHash();

        if (n !== current) {
            if (n < current) {
                show(n, { push: false });
            } else {
                go(n, { push: false });
            }
        }
    });

    // First step: the first one with an error after a round trip to the server, else the one in the URL
    // (only when every earlier step is already complete), else step 1.
    let start = Number(form.dataset.wizardAwal) || 1;

    if (errors.size === 0 && window.location.hash) {
        const wanted = fromHash();
        start = 1;

        while (start < wanted && invalidIn(start, false).length === 0) {
            done.add(start);
            start++;
        }
    }

    form.classList.add('is-wizard');
    show(start, { focus: errors.size > 0, push: false });

    if (start > 1 || window.location.hash) {
        window.history.replaceState({ wizard: start }, '', start === 1 ? window.location.pathname + window.location.search : `#langkah-${start}`);
    }
}

export function initWizards(root = document) {
    root.querySelectorAll('form[data-wizard]').forEach(initWizard);
}
