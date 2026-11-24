# Module: Pengaturan

School settings. Core module.

| Route | Permission | Purpose |
| --- | --- | --- |
| `GET/POST /admin/pengaturan/sekolah` | `pengaturan.sekolah` | Name, short name, tagline, address, phone, WhatsApp, email, website, brand colour, time zone label, footer text, logo, favicon |
| `GET/POST /admin/pengaturan/keamanan` | `sistem.keamanan` | Roles that must use two-step verification |
| `GET /admin/pengaturan/kondisi-server` | `sistem.server` (Super Admin) | Read-only server report (D-101) |

Values are stored with `setting('School.*')` and `setting('TwoFactor.requiredRoles')`; defaults in
`Config\School` and `Config\TwoFactor`. Logo (PNG/JPG/WebP ≤ 1 MB, scaled to 512 px) and favicon
(PNG/ICO ≤ 200 KB) are saved under random names in `writable/uploads/branding/` and served by
`App\Controllers\Branding` at `/aset/logo` and `/aset/favicon` (image types only, `nosniff`).
Every change writes a `settings_updated` audit entry with old and new values.

## Server condition (D-101)

`App\Libraries\ServerCheck::report()` returns rows (label, value, status `ok|warn|bad|info`, hint) in six
sections: upload (effective PHP limit = min(`upload_max_filesize`, `post_max_size`) compared with the largest active
document type limit and `Berkas.imageUploadLimitKb`; Nginx `client_max_body_size` read from `docker/nginx/spmb.conf`),
PHP (version, memory, execution time, OPcache, `display_errors` in production), extensions (required and optional),
storage (writable folders, free disk, upload folder size, load, memory), database (version, size, tables) and app
(environment, CodeIgniter version, baseURL https in production, session/cache handler, OS). Nothing is changed.

## Setup checklist (D-099)

`App\Libraries\SetupChecklist::steps($user)` powers "Persiapan sistem" on the dashboard (`app/Views/admin/_persiapan.php`):
steps for identity, registration (year, waves, an active wave, online open, document types), tests (rooms, sessions),
website (no `[ISI SEKOLAH` answers left), staff accounts (Admin Sekolah, Panitia, own 2FA) and the server upload limit.
Each step has a permission and the page that fixes it; users only see their own steps, steps of switched-off modules
are left out, and the card shows only for users with `pengaturan.sekolah` or `sistem.server`. Required steps block
"siap dipakai"; others are marked "Saran".

## Folding cards (D-100)

`lipatan_buka([...])` / `lipatan_tutup()` (helpers in `ui_helper.php`, view `partials/lipatan_buka.php`) render a
native `<details data-lipatan>` card; `partials/lipatan_semua` adds "Buka semua / Tutup semua". `resources/js/lipatan.js`
remembers each card per page path in `localStorage`, opens cards holding an error (server-side or the browser's
`invalid` event) and redraws charts when a card opens. Always use the helpers, not `view()`, so `title` does not leak
into the page's view data. Used on the dashboard and on the settings pages.
