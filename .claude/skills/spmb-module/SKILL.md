---
name: spmb-module
description: Create or modify a feature module in the SPMB CodeIgniter 4 app (app/Modules/<Name>) with routes, permissions, migrations, Indonesian URLs, breadcrumbs, guide page and tests. Use for any new feature or schema change.
---

# Building a module

Each feature (Pendaftaran, Berkas, JadwalTes, DaftarUlang, Seragam, Laporan,
Pengguna, Pengaturan, Analitik, Chat, Panduan, ...) is a self-contained module.

## Layout

```
app/Modules/<Name>/
├── Config/
│   ├── Routes.php        # Indonesian slugs, grouped under the module filter
│   ├── Permissions.php   # permission keys this module declares
│   └── Menu.php          # sidebar entries (label, icon, permission, order)
├── Controllers/          # thin; delegate to Services
├── Services/             # business rules (numbering, scheduling, status changes)
├── Models/               # one per table, $allowedFields explicit, soft deletes where data matters
├── Entities/
├── Database/Migrations/  # YYYY-MM-DD-HHMMSS_CreateXxx.php, table prefix none
├── Database/Seeds/
├── Language/id/<Name>.php  # all UI strings; English keys, Indonesian values
├── Views/
└── Guide/                # markdown sources for /panduan pages per role (Indonesian)
```

Register the namespace `Modules\<Name>` in `app/Config/Autoload.php` (PSR-4 `Modules` → `APPPATH . 'Modules'`).
Modules can be disabled in settings; a disabled module's routes return 404 and its menu is hidden.
Only `app/Config/Routes.php` loads module routes, for enabled modules; never add `'routes'` back to
`Config\Modules::$aliases` (D-112). A disabled module's named routes do not exist, so a link into another
module (`url_to('<name>')`) must check `service('modules')->isEnabled('<Module>')` first, unless that module
is core or in this module's `requires`.

## Rules

1. **URLs in Indonesian, kebab-case, readable**: `/daftar`, `/cek-status`,
   `/admin/pendaftar`, `/admin/pendaftar/tambah`, `/admin/jadwal-tes`,
   `/admin/daftar-ulang`. No IDs that leak sequence where a token fits
   (public card/QR URLs use a random token).
2. **Permissions, not role names**: `can('pendaftar.edit')`. Declare keys as
   `<module>.<action>` in `Config/Permissions.php`; super admin implicitly has all.
3. **Audit everything that changes applicant data**: call the audit service with
   who, what, before/after, when. Status changes store officer + timestamp.
4. **Configurable values come from settings** (`setting('pendaftaran.prefix')`),
   never constants: waves, quotas, prefixes, required documents, school identity.
5. **Registration numbers** are generated inside a DB transaction with a row lock
   on a counter table — unique, never reused, shared by online and offline.
6. **Uploads**: validate MIME + size, store under `writable/uploads/<module>/`,
   resize images on upload (max edge from settings, WebP/JPEG), serve via an
   authorised controller only.
7. **Export/import**: tabular data supports XLSX (PhpSpreadsheet) and PDF (Dompdf)
   via the shared Laporan service; imports validate row-by-row and return a
   downloadable error report in Indonesian.
8. **Every admin page** passes breadcrumbs to the layout (see `spmb-ui`).
9. **Every user-facing feature** gets a guide entry under `Guide/` for each role
   that uses it (see `spmb-copy`).
10. **Tests**: feature test for each route's permission, unit tests for services
    (numbering, scheduling, duplicate NIK/NISN detection).

## Finish

- Run `php spark migrate --all` and `composer test`.
- Update `docs/` per `spmb-docs` (module list, permissions table, changelog).
