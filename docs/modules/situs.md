# Situs (public information pages)

Public pages for parents and students (FR-WEB-01 Persyaratan and Jadwal Gelombang,
FR-WEB-02 Indonesian slugs), the Tanya Jawab page (D-072) and the registration actions on
the home page. Requires the Pendaftaran module. Persyaratan and Jadwal read waves and
document types; Tanya Jawab has its own table and admin screen. The module also owns the
website extras edited under the sidebar group "Website": the news ticker ("Berita singkat"),
the home page popup and the footer's "Tautan" column, plus the social media icons in the footer.

## Routes

| URL | Content |
| --- | --- |
| `/persyaratan` | Active document types in `sort_order` (required / optional, "boleh menyusul", formats, size limit) while the Berkas module is on, then the registration steps (upload and card steps only when Berkas / Kartu are on). Empty state when Berkas is off or no type is active. Uses `DocumentTypeModel::active()` when available, else `is_active = 1` ordered by `sort_order`, `name`. |
| `/jadwal` | Active waves of `setting('Pendaftaran.academicYear')` with state *Sedang dibuka* / *Akan dibuka* / *Sudah ditutup*, dates, quota and description, plus a banner. Empty state without waves. |
| `/tanya-jawab` | Published questions grouped by topic (topic chips when there is more than one topic, `<details>` accordion per question, anchors `#<topic-slug>` and `#pertanyaan-<id>`), then a contact block from `School.*` settings (WhatsApp, phone, email, address) and links to Daftar (while online registration is open), Persyaratan, Jadwal and Cek Status. A search box above the topics filters the questions while typing (D-097: all words must appear, matches marked, matching answers open). Empty state with no published question. The public menu link "Tanya Jawab" only appears while a question is published. |
| `/` (`App\Controllers\Home`, `app/Views/home.php`) | "Daftar Sekarang" while online registration is open, "Cek Status" whenever Pendaftaran is on, "Lihat Persyaratan" when Situs is on, the current/next wave line and the three "Cara mendaftar" steps as a timeline (`<ol class="garis-waktu garis-waktu-mendatar">`: numbered circles joined by a line, horizontal from `md`, a vertical line with the number on the left on phones; the painted scenes and `.kartu-langkah` backgrounds of D-090 stay in the cards). With Pendaftaran off it only points to the guide. |

## Admin: Website → Tanya jawab

Permission `situs.kelola` (Admin Sekolah; Super Admin implicitly). Every change goes through
the audited `SiteQuestionModel` (audit subject `site_questions`, labels in `Config/Audit.php`).

| URL | Action |
| --- | --- |
| `GET /admin/tanya-jawab` | List grouped by topic, badges *Tampil* / *Disembunyikan* / *Perlu dilengkapi*, empty state |
| `GET/POST /admin/tanya-jawab/tambah` | Add (`?topik=` prefills the topic) |
| `GET/POST /admin/tanya-jawab/{id}/ubah` | Edit |
| `POST /admin/tanya-jawab/{id}/pindah` | `arah=naik\|turun`, inside the topic only |
| `POST /admin/tanya-jawab/topik/pindah` | `topik`, `arah`: moves the whole topic |
| `POST /admin/tanya-jawab/{id}/tampilkan` | `tampil=1\|0` show or hide |
| `POST /admin/tanya-jawab/{id}/hapus` | Soft delete |

## Table `site_questions`

`topic` (80), `question` (200), `answer` (Markdown, max 5,000 characters), `sort_order`,
`is_published`, timestamps, soft delete. One `sort_order` runs through the list;
`normalise()` keeps a topic's questions together (topics are matched ignoring case and spaces)
and renumbers in steps of 10.

Answers are rendered by `Libraries/AnswerRenderer` with CommonMark (`html_input: strip`,
no unsafe links, like the guide pages). Links that start with `/` are rewritten with
`site_url()`. Placeholders: `{nama_sekolah}`, `{tahun_ajaran}`.

Text only the school can write is marked `[ISI SEKOLAH: ...]`
(`SiteQuestionModel::PLACEHOLDER`). Such a question cannot be published until the marker is
replaced; the admin list counts and badges them.

## Website extras

All admin pages need `situs.kelola`, carry breadcrumbs (Dasbor › Website › page) and are in the
sidebar group "Website" (`Config/Menu.php`, after Tanya jawab). Settings live in `Config\Situs`
(`setting('Situs.*')`); setting changes are logged as `settings_updated` with subject ids `News`,
`Popup` and `FooterLinks` (labels in `Config/Audit.php`).

Links typed by Admin Sekolah (news item, popup button, footer link) go through
`Libraries/SiteLink`: an internal path (`/jadwal`, `jadwal` becomes `/jadwal`, query and `#anchor`
allowed) or a full `http(s)://` address. `javascript:`, `data:`, `//host` and `..` are refused.
Internal paths are printed with `site_url()`; external links open in a new tab.

### Berita singkat (news ticker)

| URL | Action |
| --- | --- |
| `GET /admin/berita-singkat` | Switch and speed card, then the list with badges *Tampil* / *Disembunyikan* / *Belum waktunya* / *Sudah lewat* and the dates |
| `POST /admin/berita-singkat/pengaturan` | `enabled` (0/1) → `Situs.newsEnabled` (default off), `interval` 3–30 seconds → `Situs.newsInterval` (default 5) |
| `GET/POST /admin/berita-singkat/tambah` | Add: `text` (max 160), `url` (optional), `starts_on` / `ends_on` (optional, inclusive), `is_active` |
| `GET/POST /admin/berita-singkat/{id}/ubah` | Edit |
| `POST /admin/berita-singkat/{id}/pindah` | `arah=naik\|turun` |
| `POST /admin/berita-singkat/{id}/aktifkan` | `aktif=1\|0` |
| `POST /admin/berita-singkat/{id}/hapus` | Soft delete |

Table `site_news`: `text` (160), `url` (255, '' = no link), `starts_on`, `ends_on` (DATE, null = no
limit), `is_active`, `sort_order` (steps of 10, `normalise()`), timestamps, soft delete. Audited
through `SiteNewsModel` (subject `site_news`). `visible($today)` returns the active items whose dates
cover today (`Time::today()`), in order.

Front end: panel `Views/panels/public_top.php` in the public layout slot `public_top`, directly
under the sticky header on every public page. Nothing is rendered while the switch is off or no
item shows today. A slim bar with the megaphone icon and "Info" (icon only on phones), one item at a
time; `resources/js/berita.js` fades to the next item every `newsInterval` seconds, pauses while the
pointer or keyboard focus is inside the bar and while the tab is hidden, and has previous / pause-play /
next buttons (previous hidden on phones; no controls with one item). The list has
`aria-live="off"` while rotating and `polite` when paused, so screen readers only announce an item
the visitor asked for. With `prefers-reduced-motion` the bar does not rotate by itself and changes
without animation (the buttons still work). Items share one grid cell, so the bar keeps the height of
the longest item (no layout shift); on phones an item is cut after three lines. Without JavaScript
(`html.js` is set in `partials/head`) all items are listed.

### Popup beranda (home page popup)

| URL | Action |
| --- | --- |
| `GET /admin/popup` | Form with the current state (shows today / switched on but outside its dates / off) and a "Pratinjau" link to `/?popup=pratinjau` |
| `POST /admin/popup` | Saves the popup; every save raises `Situs.popupVersion` |
| `GET /popup/gambar` | The image (public while the popup shows today, always for `situs.kelola`) |
| `GET /popup/lampiran` | The attachment as a download with its original name (same rule) |

Settings: `popupEnabled` (default off), `popupTitle` (max 120, required while on), `popupText`
(max 3,000, rendered by `AnswerRenderer` with `keepLineBreaks`: Tanya Jawab Markdown where a single
line break stays a line break), `popupImage` (jpg/png/webp up to 2 MB, scaled to fit 1200 px),
`popupButtonLabel` + `popupButtonUrl` (both or neither), `popupAttachment` + `popupAttachmentName`
(pdf/doc/docx/xls/xlsx up to 5 MB; extension and real MIME type checked), `popupStartsOn` /
`popupEndsOn` (inclusive, '' = no limit), `popupFrequency` (`once` = until the popup is changed,
`daily`, `always` = every visit) and `popupVersion`. Files live in `writable/uploads/situs/` under
random names (`Libraries/PopupFiles`); replacing or removing one deletes the old file.

Front end: panel `Views/panels/public_home.php`, rendered by the public layout only on `/` (slot
`public_home`); `Services/HomePopup::forPage()` decides whether there is anything to show (on, today
inside the dates, some content). A native `<dialog>` (title, scrollable body with image and text,
footer with Tutup, the attachment download and the button) opened by `resources/js/popup.js` 400 ms
after the page has loaded. `showModal()` keeps focus inside and Esc closes; the close buttons and a
click on the dimmed background close it too. On close (or a click on the button) the browser stores
`{"v": version, "d": "Y-m-d"}` in `localStorage["spmb-popup"]` (wrapped in try/catch): `once`
shows again only for a new version, `daily` on another day, `always` on every visit. The admin
preview always opens and stores nothing.

### Tautan bawah website (footer links, changes D-092)

| URL | Action |
| --- | --- |
| `GET /admin/tautan-footer` | List by position, badges, "Kembalikan ke bawaan" while the list is customised |
| `GET/POST /admin/tautan-footer/tambah` | Add: `label` (max 40), `url`, `active` (max 12 links) |
| `GET/POST /admin/tautan-footer/{n}/ubah` | Edit the link at position `n` (1-based) |
| `POST /admin/tautan-footer/{n}/pindah` | `arah=naik\|turun` |
| `POST /admin/tautan-footer/{n}/tampilkan` | `tampil=1\|0` |
| `POST /admin/tautan-footer/{n}/hapus` | Remove |
| `POST /admin/tautan-footer/kembalikan` | Back to the default list |

Stored as JSON in `Situs.footerLinks` (`[{"label", "url", "active"}]`); '' means the default list
Cek Status, Tanya Jawab, Panduan (`Libraries/FooterLinks`). The first change saves the whole list.
`FooterLinks::visible($nav)` prints active links whose page exists: external links always; internal
ones when the page is in the website menu, or a route answers it that does not belong to a
switched-off module (the routes of switched-off modules are collected separately because the
framework's route discovery loads every module's `Config/Routes.php`). Tanya Jawab stays hidden while
no question is published, as before. Without any visible link the "Tautan" column is not shown.
The admin list marks an active link whose page is not available right now with *Belum tampil*
(`FooterLinks::available()`).

### Footer social icons and slots

Under the school logo, name and slogan, the footer shows round icon buttons (44 px, `aria-label`
with the network name) for the links filled in Identitas Sekolah: Instagram, Facebook, YouTube,
TikTok (http/https only) and WhatsApp (`wa.me`), from `school_social_links()`. The slot
`module_panels('public_footer')` sits above the copyright line in small text (the Analitik module
fills it with a visitor summary). The copyright line and "Developed by : Mr. F" are unchanged.

## Starter questions (client pack)

`php spark spmb:branding <pack>` also loads `tanya-jawab.json` from the pack folder or its
parent (e.g. `clients/smp-nu-al-maruf-kudus/tanya-jawab.json` next to `branding/`), only while
the installation has no questions (deleted ones count, so the school's edits are never
overwritten). Format: `{"questions": [{"topic", "question", "answer", "published"?}]}`.
Questions with `[ISI SEKOLAH` are stored hidden.

## WaveSchedule

`Modules\Situs\Services\WaveSchedule` (dates inclusive, today from `Time::today()` in the
app timezone):

- `forYear(?string $year = null, ?string $today = null)` — active waves with `state`.
- `current()`, `next()` — the open / next upcoming wave.
- `onlineOpen()` — `Pendaftaran.onlineOpen` and a wave covers today.
- `banner()` — `open`, `offline` (wave running, online form switched off: come to the
  school), `upcoming` or `closed`, with the Indonesian text.

## Tests

`tests/feature/SitusTest.php`, `tests/feature/HomeTest.php`, `tests/feature/SitusTanyaJawabTest.php`,
`tests/feature/SitusWebsiteTest.php` (ticker, popup, footer links and social icons, permissions).
