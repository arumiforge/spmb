/*
 * "Tanya Asisten" chat widget (module Asisten, D-110). Markup: app/Modules/Asisten/Views/panels/public_end.php.
 *
 * - The round button opens a <dialog>: modal full-height sheet on phones (focus stays inside), a panel
 *   next to the page from 40rem (Esc and the close button close it; focus returns to the button).
 * - Quick choices: links open their page directly (asisten/buka/<n> counts the choice); a choice with a
 *   saved answer and every typed question go to asisten/tanya as a form POST with the CSRF token.
 *   The token comes from asisten/mulai the first time the chat is used (so ordinary page views start
 *   no session) and every answer carries the next one. A refused token is fetched again once.
 * - One request at a time; a loading line; "Coba lagi" after a network error or a busy assistant.
 * - Answers arrive as HTML the server already cleaned (AnswerRenderer); plain messages use textContent.
 * - The conversation is kept for the browser tab in sessionStorage (per language); storage errors are ignored.
 * - Long numbers (NIK, NISN, phone) and e-mail addresses are masked before a question is shown, kept or sent.
 */
const MAX_STORED = 40;

const store = {
    read(key) {
        try {
            return JSON.parse(window.sessionStorage.getItem(key) || '[]');
        } catch {
            return [];
        }
    },
    write(key, value) {
        try {
            window.sessionStorage.setItem(key, JSON.stringify(value.slice(-MAX_STORED)));
        } catch {
            // Private mode or blocked storage: the conversation is simply not kept.
        }
    },
    clear(key) {
        try {
            window.sessionStorage.removeItem(key);
        } catch {
            // Nothing to clear.
        }
    },
};

/* Same rule as TextNormaliser::maskPersonalData(): numbers of 8+ digits and e-mail addresses never leave the box. */
function mask(text) {
    return text.replace(/[^\s@]+@[^\s@]+\.[^\s@]+/g, '[email]').replace(/\+?\d(?:[\s.-]?\d){7,}/g, '[####]');
}

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) {
        node.className = className;
    }
    if (text !== undefined && text !== null) {
        node.textContent = text;
    }
    return node;
}

function setupWidget(root) {
    const dialog = root.querySelector('dialog');
    const opener = root.querySelector('[data-asisten-buka]');
    const list = root.querySelector('[data-asisten-daftar]');
    const area = root.querySelector('[data-asisten-isi]');
    const form = root.querySelector('[data-asisten-form]');
    const input = root.querySelector('[data-asisten-masukan]');
    const sendButton = root.querySelector('[data-asisten-kirim]');
    const counter = root.querySelector('[data-asisten-sisa]');
    const loading = root.querySelector('[data-asisten-memuat]');
    const heading = root.querySelector('#asisten-judul');
    const texts = JSON.parse(root.dataset.asistenTeks || '{}');
    const storageKey = root.dataset.asistenKunci || 'spmb-asisten';
    const wide = window.matchMedia('(min-width: 40rem)');
    const icons = {};

    root.querySelectorAll('template[data-asisten-ikon]').forEach((template) => {
        icons[template.dataset.asistenIkon] = template.innerHTML;
    });

    let token = null;
    let busy = false;
    let history = store.read(storageKey);

    const icon = (name) => {
        const span = el('span', 'asisten-ikon');
        span.setAttribute('aria-hidden', 'true');
        span.innerHTML = icons[name] || '';
        return span;
    };

    const scrollDown = () => {
        area.scrollTop = area.scrollHeight;
    };

    /* ---------- Rendering ---------- */

    function userBubble(text) {
        const row = el('div', 'asisten-pesan dari-anda');
        const bubble = el('div', 'asisten-gelembung');
        bubble.append(el('p', 'sr-only', `${texts.you}:`));
        const p = el('p', null, text);
        p.dir = 'auto';
        bubble.append(p);
        row.append(bubble);
        return row;
    }

    function link(href, label, className, external) {
        const a = el('a', className);
        a.href = href;
        if (external) {
            a.target = '_blank';
            a.rel = 'noopener';
        }
        const text = el('bdi', null, label);
        text.dir = 'auto';
        a.append(text);
        if (external) {
            a.append(el('span', 'sr-only', ` ${texts.opensNewTab}`));
        }
        return a;
    }

    function assistantBubble(data, params) {
        const row = el('div', 'asisten-pesan dari-asisten');
        const avatar = el('span', 'asisten-pesan-avatar');
        avatar.setAttribute('aria-hidden', 'true');
        avatar.innerHTML = icons.avatar || '';
        const bubble = el('div', 'asisten-gelembung');
        bubble.append(el('p', 'sr-only', `${texts.assistant}:`));

        if (data.auto) {
            const tag = el('p', 'asisten-tanda asisten-tanda-otomatis');
            tag.append(icon('otomatis'), el('span', null, texts.autoAnswer));
            bubble.append(tag);
        } else if (data.type === 'qa') {
            const tag = el('p', 'asisten-tanda');
            tag.append(icon('tanya-jawab'), el('span', null, texts.fromQa));
            bubble.append(tag);
        }

        if (data.image) {
            const picture = el('div', 'asisten-gambar-status');
            picture.innerHTML = data.image;
            bubble.append(picture);
        }

        if (data.title) {
            const title = el('p', 'asisten-judul-jawaban', data.title);
            title.dir = 'auto';
            bubble.append(title);
        }

        if (data.html) {
            const answer = el('div', 'asisten-jawaban');
            answer.dir = 'auto';
            answer.innerHTML = data.html;
            answer.querySelectorAll('a[href^="http"]').forEach((a) => {
                if (a.host !== window.location.host) {
                    a.target = '_blank';
                    a.rel = 'noopener';
                }
            });
            bubble.append(answer);
        } else if (data.message) {
            const message = el('p', null, data.message);
            message.dir = 'auto';
            bubble.append(message);
        }

        if (data.masked) {
            bubble.append(el('p', 'asisten-catatan-kecil', texts.masked));
        }

        if (data.source) {
            const source = link(data.source.url, data.source.label, 'asisten-tautan', false);
            source.append(icon('buka'));
            bubble.append(source);
        }

        if (Array.isArray(data.related) && data.related.length > 0) {
            const related = el('div', 'asisten-terkait');
            related.append(el('p', 'asisten-terkait-judul', texts.related));
            const ul = el('ul');
            data.related.forEach((item) => {
                const li = el('li');
                li.append(link(item.url, item.label, 'asisten-tautan', false));
                ul.append(li);
            });
            related.append(ul);
            bubble.append(related);
        }

        const actions = el('div', 'asisten-aksi');

        if (data.retry && params) {
            const retry = el('button', 'asisten-tombol');
            retry.type = 'button';
            retry.append(icon('ulang'), el('span', null, texts.retry));
            retry.addEventListener('click', () => {
                retry.disabled = true;
                send(params, { echo: false });
            });
            actions.append(retry);
        }

        if (data.askAi && params && params.pertanyaan) {
            const ask = el('button', 'asisten-tombol');
            ask.type = 'button';
            ask.append(icon('otomatis'), el('span', null, texts.notThis));
            ask.addEventListener('click', () => {
                ask.disabled = true;
                send({ pertanyaan: params.pertanyaan, mode: 'ai' }, { echo: false });
            });
            actions.append(ask);
        }

        if (data.handover) {
            const handover = link(data.handover.url, data.handover.label, data.handover.prominent ? 'asisten-wa asisten-wa-utama' : 'asisten-wa', data.handover.external);
            handover.prepend(icon('wa'));
            actions.append(handover);
        }

        if (actions.childElementCount > 0) {
            bubble.append(actions);
        }

        row.append(avatar, bubble);
        return row;
    }

    function noteBubble(text) {
        const row = el('div', 'asisten-pesan dari-asisten asisten-pesan-catatan');
        const p = el('p', 'asisten-gelembung', text);
        p.dir = 'auto';
        row.append(p);
        return row;
    }

    function render(entry) {
        if (entry.k === 'anda') {
            return userBubble(entry.t);
        }
        if (entry.k === 'catatan') {
            return noteBubble(entry.t);
        }
        return assistantBubble(entry.d, entry.p);
    }

    function add(entry, keep = true) {
        list.append(render(entry));
        if (keep) {
            history.push(entry);
            store.write(storageKey, history);
        }
        scrollDown();
    }

    /* ---------- Requests ---------- */

    const headers = () => ({ 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' });

    async function fetchToken() {
        const response = await fetch(root.dataset.asistenMulai, { headers: headers(), credentials: 'same-origin' });
        const data = await response.json();
        token = data.csrf || null;
        return token;
    }

    async function post(params, retried = false) {
        if (!token) {
            await fetchToken();
        }
        const body = new URLSearchParams(params);
        const extra = headers();
        if (token) {
            body.set(token.name, token.hash);
            extra[token.header] = token.hash;
        }
        const response = await fetch(root.dataset.asistenTanya, { method: 'POST', body, headers: extra, credentials: 'same-origin' });
        let data = null;
        try {
            data = await response.json();
        } catch {
            data = null;
        }
        if (data && data.csrf) {
            token = data.csrf;
        }
        if (response.status === 403 && !retried) {
            token = null;
            return post(params, true);
        }
        if (!data) {
            throw new Error(`HTTP ${response.status}`);
        }
        return data;
    }

    function setBusy(state) {
        busy = state;
        loading.hidden = !state;
        sendButton.disabled = state;
        root.querySelectorAll('[data-asisten-chip]').forEach((chip) => {
            chip.disabled = state;
        });
        if (state) {
            scrollDown();
        }
    }

    async function send(params, { echo = true, label = null } = {}) {
        if (busy) {
            return;
        }
        if (echo) {
            add({ k: 'anda', t: label || params.pertanyaan });
        }
        setBusy(true);
        try {
            const data = await post(params);
            if (data.type === 'link' && data.url) {
                window.location.href = data.url;
                return;
            }
            add({ k: 'asisten', d: data, p: params });
        } catch {
            add({ k: 'asisten', d: { type: 'error', message: texts.network, retry: true }, p: params }, false);
        } finally {
            setBusy(false);
        }
    }

    /* ---------- Opening and closing ---------- */

    function open() {
        if (dialog.open) {
            return;
        }
        if (wide.matches || typeof dialog.showModal !== 'function') {
            if (typeof dialog.show === 'function') {
                dialog.show();
            } else {
                dialog.setAttribute('open', '');
            }
        } else {
            dialog.showModal();
        }
        opener.setAttribute('aria-expanded', 'true');
        root.classList.add('is-terbuka');
        scrollDown();
        // Phones: start on the heading so the keyboard does not cover the conversation at once.
        (wide.matches ? input : heading).focus();
        if (!token) {
            fetchToken().catch(() => {
                token = null;
            });
        }
    }

    function close() {
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
            onClosed();
        }
    }

    function onClosed() {
        opener.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-terbuka');
        opener.focus();
    }

    opener.addEventListener('click', () => (dialog.open ? close() : open()));
    root.querySelector('[data-asisten-tutup]').addEventListener('click', close);
    dialog.addEventListener('close', onClosed);
    dialog.addEventListener('keydown', (event) => {
        // A non-modal dialog does not close on Escape by itself.
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    });

    root.querySelector('[data-asisten-ulang]').addEventListener('click', () => {
        history = [];
        store.clear(storageKey);
        list.querySelectorAll('.asisten-pesan').forEach((node, index) => {
            if (index > 0) {
                node.remove();
            }
        });
        add({ k: 'catatan', t: texts.restarted }, false);
        if (token) {
            post({ ulang: '1' }).catch(() => {});
        }
        input.focus();
    });

    /* ---------- Asking ---------- */

    root.querySelectorAll('[data-asisten-chip]').forEach((chip) => {
        chip.addEventListener('click', () => send({ pilihan: chip.dataset.asistenChip }, { label: chip.textContent.trim() }));
    });

    const maxLength = Number(input.getAttribute('maxlength')) || 300;

    function updateCounter() {
        const left = maxLength - input.value.length;
        counter.textContent = left <= 40 ? (texts.charsLeft || '{0}').replace('{0}', String(left)) : '';
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 128)}px`;
    }

    input.addEventListener('input', updateCounter);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const question = mask(input.value.replace(/\s+/g, ' ').trim());
        if (question === '' || busy) {
            input.focus();
            return;
        }
        input.value = '';
        updateCounter();
        send({ pertanyaan: question });
    });

    history.forEach((entry) => list.append(render(entry)));
    root.hidden = false;
}

export function initAsisten() {
    document.querySelectorAll('[data-asisten]').forEach((root) => {
        try {
            setupWidget(root);
        } catch {
            // A broken widget must never break the page; the button simply stays hidden.
        }
    });
}
