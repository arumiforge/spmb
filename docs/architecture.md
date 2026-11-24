# Architecture

> Stage: phases 1–3 and the platform round built (registration, placement test, Docker deployment, Tanya Jawab);
> see `phase-2-plan.md`, `phase-3-plan.md` and `platform-plan.md`. Sections marked *(planned)* describe the target
> design and become descriptive as modules are built.

## Stack

| Layer | Choice |
| --- | --- |
| Language / framework | PHP 8.5.13+, CodeIgniter 4.7 (Composer dependency, appstarter layout) (D-069) |
| Database | MySQL 8.4, `utf8mb4_unicode_ci` |
| Web server | Nginx + PHP-FPM 8.5 in Docker (images `spmb-web`, `spmb-app`) on Ubuntu Server 24.04 LTS (D-066) |
| Environments | Staging and production as two Docker Compose stacks on one VPS, each with its own database (D-067) |
| Edge | Cloudflare (DNS, TLS, WAF) via Cloudflare Tunnel (`cloudflared` on the host); stacks listen on `127.0.0.1` only |
| Remote access | Cockpit (browser console) and SSH through Cloudflare Access; direct SSH stays open, key-only (D-077) |
| Frontend | Tailwind CSS v4 + Flowbite, Lucide icons, ApexCharts, built with Node.js 24 (esbuild) |
| CI/CD | GitHub Actions: lint, tests (PHP 8.5, MySQL 8.4), frontend build, commit policy. Deploys are pulled and built by the server (`spmb deploy`, D-068) |

Installed: CodeIgniter Shield 1.4 (auth, groups, permissions) with `codeigniter4/settings`,
`pragmarx/google2fa` (TOTP), `codeigniter4/translations` (Indonesian framework messages).
In use: `chillerlan/php-qrcode` (QR, inline SVG), `league/commonmark` (guide Markdown), CI4 Image with GD (logo resize).
Phase 2 added `setasign/fpdf` (card PDF) and `phpoffice/phpspreadsheet` (XLSX/CSV import) (D-030). PDF reports for phase 4 are still to be decided.

Frontend (`package.json`): Tailwind CSS v4 (`@tailwindcss/cli`), Flowbite 3 (open-source package), ApexCharts,
bundled with esbuild. Sources in `resources/css/app.css` and `resources/js/{app,charts}.js`; `npm run build`
writes `public/build/` (git-ignored; built by CI and inside the Docker image). Automatic class detection is off
(`source(none)`), so only `app/Views`, `app/Modules` and `resources/js` are scanned — never `flowbite/`.
Icons are Lucide (`lucide-static`, ISC): `npm run icons` copies the icons listed in `resources/icons.json` into
`app/Helpers/lucide_icons.php`, which `icon()` reads, so production needs no Node (D-070).

## Code layout

```
app/
├── Config/            framework + app config (Autoload registers Modules\)
├── Authentication/    Shield login action for TOTP (TotpAction)
├── Filters/           StaffArea (forced password change, 2FA required by role)
├── Helpers/ui_helper  asset_url, brand_color, icon (Lucide), illustration, tanggal_indo, initials
├── Helpers/lucide_icons.php  generated icon data (npm run icons)
├── Libraries/         AuditLogger, TwoFactor, QrCode, ModuleRegistry, Menu
├── Modules/<Name>/    feature modules — see .claude/skills/spmb-module
├── Views/layouts/     admin (full width) · public · auth
├── Views/partials/    head, breadcrumbs, flash, empty_state, form_field, status_badge, pagination
resources/             frontend sources (Tailwind CSS, JS, icons.json)
scripts/               icons.mjs (Lucide generator)
public/                web root (index.php, build/ = compiled assets)
writable/              logs, cache, sessions, uploads (private)
deploy/server/         setup.sh (one-time server setup) and spmb (server CLI: deploy, rollback, backup)
deploy/compose.yaml    Docker Compose stack per environment (staging, production)
deploy/env/            example stack.env / app.env per environment (placeholders only)
docker/                Dockerfile (app: PHP-FPM 8.5, web: Nginx), Nginx and PHP config
ai/                    Codex briefs, delivered assets and the asset checker (D-073, D-075)
clients/<school>/      per-school branding pack, starter Tanya Jawab and example files for document types (loaded by spmb:branding)
.claude/               agent skills; cloud/setup.sh for Claude Code cloud environments (D-074)
flowbite/              Flowbite Pro templates — markup reference only (private repo)
```

## Modules

| Module | Responsibility | Phase |
| --- | --- | --- |
| Core | Layouts, breadcrumbs, settings, white label, module registry, menu | 1 |
| Auth | Login, roles/permissions, optional TOTP 2FA, user management | 1 |
| Audit | Change and login logs | 1 |
| Panduan | Role-based Indonesian guide pages assembled from module `Guide/` | 1 (built) |
| Profil | Own profile, password change, 2FA setup | 1 (built) |
| Pengaturan | School identity / branding, security policy | 1 (built) |
| Pengguna | Staff accounts and roles | 1 (built) |
| Referensi | Normalised schools (SD/MI), regions; import from public data | 2 (built, `modules/referensi.md`) |
| Pendaftaran | Waves, online/offline form, numbering, duplicates, status check | 2 (built, `modules/pendaftaran.md`) |
| Berkas | Required documents, uploads, resize, verification | 2 (built, `modules/berkas.md`) |
| Kartu | PDF card, QR token, verification page | 2 (built, `modules/kartu.md`) |
| Tes | Rooms, test calendar and holidays, sessions with quotas, allocation, attendance (QR/search), schedule change log | 3 (built, `modules/tes.md`) |
| DaftarUlang | Re-registration processing | 4 |
| Seragam | Uniform status (stock/packages later) | 4 |
| Laporan | Dashboard stats, filters, XLSX/CSV/PDF export, XLSX import | 4 |
| Situs | Public pages: Persyaratan, Jadwal, Tanya Jawab (edited under Website) and later announcements | 2 (Persyaratan, Jadwal), platform round (Tanya Jawab, `modules/situs.md`) → 5 |
| Analitik | Light visitor tracking | 5 |
| Statistik | Applicant statistics for school planning: location drill-down, origin schools, family, age, registrations over time, cross-tabs (D-111) | platform round (built, `modules/statistik.md`) |
| Asisten | "Tanya Asisten" help widget: quick choices, Tanya Jawab search, AI answers from the school's own content with rotating OpenAI-compatible connections (D-110) | platform round (built, `modules/asisten.md`) |
| (messages, name to be chosen) | Messages, criticism and suggestions from applicants with committee replies — not live chat (D-052) | 5 |

### Module registry and menu

`service('modules')` (`App\Libraries\ModuleRegistry`) lists folders in `app/Modules`. A module is
enabled unless it is in `setting('Features.disabled')`; `Config\Features::$core` (Pengaturan, Pengguna,
Profil, Panduan, Riwayat) cannot be disabled. Super Admin switches modules on the **Fitur** page
(`/admin/pengaturan/fitur`, permission `sistem.modul`); each change is audited as `features_changed`.
Each module describes itself for that page in `Config/Module.php` (`label`, `description`, `icon`;
D-025), read with `service('modules')->info($name)` even while the module is off. `app/Config/Routes.php` loads `Config/Routes.php` of enabled modules
only, so a disabled module's URLs (public and admin) return 404 and its named routes do not exist. Each module is also registered as namespace
`Modules\<Name>` (in `Config\Autoload`), so `Language/id/<Name>.php` and `Views` resolve. Because of that,
route auto-discovery is off (`'routes'` is not in `Config\Modules::$aliases`, D-112): it would load the
routes of switched-off modules too. A link from one module to another (`url_to('<name>')`) checks
`service('modules')->isEnabled()` first unless the target is core or listed in the module's `requires`.

`service('menu')` builds the sidebar from each enabled module's `Config/Menu.php`
(`label`, `url`, `icon`, `permission`, `group`, `order`) and keeps only entries the user can access.
Permissions stay declared centrally in `Config\AuthGroups` (tested by `AuthGroupsTest`).

### Layouts and theming

- `layouts/admin`: full width, sidebar collapsible to icons (`html.sidebar-collapsed`, remembered in
  `localStorage`), Flowbite drawer below `lg`, user menu, breadcrumb bar, toasts. Controllers pass
  `title`, optional `subtitle`, `breadcrumbs`; views fill sections `content` and optional `actions`.
- `layouts/auth`: sign-in and verification card (also Shield's layout); `layouts/public`: website and guides.
- White label: `partials/head` prints `:root{--brand:#rrggbb}` from `setting('School.primaryColor')`
  (validated hex). `@theme` derives `primary-50…950` from `--brand` with `color-mix()`.
- Dark mode via `.dark` on `<html>`: pages start light whatever the device theme; dark only after the toggle (remembered per browser, D-078).
- Illustrations: `illustration('state/kosong-data', $alt, $class, $lazy)` (`ui_helper`) prints the WebP from
  `public/assets/img/` plus its `-dark` variant for dark mode when present; `partials/empty_state` takes
  `image`/`imageAlt`. Codex makes the assets from briefs in `ai/` (D-073, D-075).
- Visual rules (D-070): Lucide icons without background shapes, no eyebrow labels, no accent bars or coloured
  side/top borders on cards; motion classes `motion-rise`, `motion-lift`, `motion-icon` switch off under
  `prefers-reduced-motion`; `resources/js/charts.js` applies responsive chart defaults so text never overlaps at 360 px.
- Partials receive one array (`field`, `badge`, `empty`, `brand`) because CI view data is shared
  between renders (`saveData`); a partial must not rely on loose variables.

### Website languages (D-109)

The public website is Indonesian (`/`), English (`/en/…`) and Arabic (`/ar/…`, right to left). The prefix is
taken off the request before routing (`Config\Services::incomingrequest`, `App\Libraries\Locales`), the
required filter `bahasa` (`App\Filters\WebsiteLanguage`) redirects prefixed staff/file addresses and
switched-off languages, `site_url()` adds the prefix back (`app/Helpers/url_helper.php`), and
`App\Libraries\Language` falls back to Indonesian per key. School texts have optional `_en`/`_ar`
fields read with `localized()` / `setting_localized()`. Dashboard, sign-in and PDFs stay Indonesian.
Details: [i18n.md](i18n.md).

### Audit log viewer (Riwayat)

`/admin/riwayat` (permission `audit.view`: Super Admin, Admin Sekolah) lists `audit_logs` newest first,
25 per page, with filters in the query string: `pengguna` (user id or `sistem`), `kegiatan` (event),
`data` (subject type), `id` (subject id), `dari` / `sampai` (`YYYY-MM-DD`, whole days; a reversed range is
swapped, an invalid date is ignored with a hint). `/admin/riwayat/<id>` shows before/after values.
`Modules\Riwayat\Libraries\AuditPresenter` turns raw rows into Indonesian: labels for events, subject
types, settings groups and fields live in `Language/id/Riwayat.php` (`events`, `subjects`,
`settingGroups`, `fields`); unknown keys fall back to a tidied key, so a new module should add its labels
there. Role keys are shown as role titles, redacted values as "(disembunyikan)".

### Error pages

`Config\Routing::$override404` is `App\Controllers\Errors::notFound`, so unknown URLs and
`PageNotFoundException` from controllers render a friendly 404 (status 404): admin layout for signed-in
staff, public layout otherwise. With `display_errors` off, `app/Views/errors/html/production.php` renders
the 500 page the same way. If the layout cannot render (e.g. database down, routes not loaded),
`Errors::fallback()` returns a self-contained page with inline CSS (D-026) in the page's language. On an
English or Arabic address the error page always uses the public layout in that language (D-109). In development
CodeIgniter's detailed exception page is unchanged.

### Request pipeline

Global filters: `csrf` (session-based token; skipped only when `ENVIRONMENT === 'testing'`) and
`secureheaders`. `/admin/*`: `session` then `staff` (`App\Filters\StaffArea`): accounts flagged for a
password change go to `/admin/profil/kata-sandi`; users whose role is in
`setting('TwoFactor.requiredRoles')` without 2FA go to `/admin/profil/verifikasi-dua-langkah`.
Routes add `permission:<key>`; a denied permission redirects to `/admin/akses-ditolak`.
`auth-rates` throttles `/masuk` and `/verifikasi`.

### Two-step sign-in

`Config\Auth::$actions['login'] = TotpAction`. The action applies only to users with confirmed 2FA.
After the password, Shield stores a marker identity (`totp_2fa`) and the user is "pending" until a
code passes `service('twoFactor')->verify()` (TOTP or recovery code). The marker stays in the
database until then, so a remember-me cookie cannot skip the step. Five wrong codes cancel the
sign-in (`TwoFactor.maxAttempts`).

## Implemented core (phase 1)

| Piece | Where |
| --- | --- |
| Roles, permissions, Indonesian titles | `app/Config/AuthGroups.php` |
| Auth routes `/masuk`, `/keluar`, `/verifikasi` | `app/Config/AuthRoutes.php` |
| Staff user model (+ `full_name`, `phone`, audited) | `app/Models/UserModel.php` |
| School identity defaults (white label) | `app/Config/School.php` → `setting('School.*')` |
| Audit trail | `audit_logs`, `App\Libraries\AuditLogger` (`service('audit')`), `App\Models\Concerns\Auditable` |
| Two-factor (TOTP) | `user_two_factor`, `App\Libraries\TwoFactor` (`service('twoFactor')`) |
| Installer | `php spark spmb:install` (`app/Commands/Install.php`), `DemoUserSeeder` |
| Admin area | `/admin` dashboard (placeholder stats), `/admin/akses-ditolak` |
| Profile | `/admin/profil`, `/admin/profil/kata-sandi`, `/admin/profil/verifikasi-dua-langkah` (module Profil) |
| School settings | `/admin/pengaturan/sekolah` (`pengaturan.sekolah`), `/admin/pengaturan/keamanan` (`sistem.keamanan`) |
| Users | `/admin/pengguna` (`pengguna.view/create/edit`; Super Admin accounts need `pengguna.kelola-admin`) |
| Guides | `/panduan/calon-murid` (public); staff guides `/admin/panduan/<peran>` (own role, D-097) |
| Branding files | `writable/uploads/branding/`, served by `/aset/logo`, `/aset/favicon` |

### Tables

| Table | Source | Purpose |
| --- | --- | --- |
| `users`, `auth_identities`, `auth_logins`, `auth_token_logins`, `auth_remember_tokens`, `auth_groups_users`, `auth_permissions_users` | Shield | Staff accounts, credentials, login log, role assignments |
| `settings` | codeigniter4/settings | White-label and runtime configuration |
| `audit_logs` | App | Who changed what and when |
| `user_two_factor` | App | Optional TOTP per user |
| `ref_sekolah` | Referensi | Previous schools (D-012, D-039) |
| `waves`, `registration_counters`, `applicants` | Pendaftaran | Waves, numbering (D-031), applicants (D-037) |
| `document_types`, `applicant_documents` | Berkas | Required documents and uploads (D-036, D-043) |
| `card_tokens` | Kartu | Revocable QR tokens (D-034) |
| `test_rooms`, `test_holidays`, `test_sessions`, `test_assignments`, `test_schedule_changes` | Tes | Rooms, calendar, sessions, seats and the schedule change log (D-059…D-064) |
| `site_questions` | Situs | Tanya Jawab questions and answers (Markdown), audited (D-072) |
| `site_news` | Situs | News ticker items under the public header ("Berita singkat"), audited |
| `asisten_providers`, `asisten_questions`, `asisten_usage` | Asisten | AI connections (key encrypted), visitor question log without IP, daily AI answer counts (D-110) |

Module extension points (D-033): `Config/Module.php` `requires`, `Config/PublicMenu.php`, `Config/Audit.php`,
`Views/panels/<slot>.php` rendered with `module_panels()`, and `InstallSeeder`/`DemoSeeder` run by `spmb:install`.
Public layout slots: `public_top` (under the header, every page), `public_home` (home page only),
`public_footer` (above the copyright line), `public_end` (before `</body>` on every page; the Asisten chat widget).
The public applicant session is `service('applicantAccess')` (D-034).

## Key design rules

- **Tenancy** *(proposed, D-004)*: one installation and one database per school; white label via `settings`.
- **Numbering**: a `sequences` row per academic year, incremented inside a transaction
  with `SELECT ... FOR UPDATE`; online and offline share it.
- **Status history**: test attendance, re-registration and uniform statuses are
  current-state columns plus an append-only history table (who, when, from, to, note).
- **Files**: `writable/uploads/<module>/<yyyy>/<random>.<ext>`; DB stores metadata;
  download through a controller that checks permission or the applicant's token.
- **QR / public links**: 32-byte random tokens, revocable; never NIK/NISN or sequential IDs.
- **Settings**: key/value table with typed values and caching; modules declare defaults.
- **Permissions**: `<module>.<action>` keys; Super Admin bypass; checked in filters and views.
- **Audit**: model events write who/what/before/after for applicant-related tables.

## Public school reference data

Source: Dapodik public recap data at <https://dapo.kemendikdasmen.go.id/>
(Kemendikdasmen; the old `dapo.kemdikbud.go.id` address is obsolete) for SD in Central Java,
with <https://sekolah.data.kemendikdasmen.go.id/> (school reference data by NPSN)
as the alternative/secondary source — to verify in phase 2 whether it also lists MI. If not, MI
(Kemenag/EMIS) are loaded through an XLSX import.
**Default since D-058**: the national SD/MI list (177,422 schools) ships in the Referensi module and is loaded by
`spmb:install` and on deploy; the Dapodik route below stays optional.
A `spark referensi:import` command fetches and upserts into `ref_sekolah`
(NPSN, name, type, status, district, sub-district); applicants pick from an
autocomplete; unknown schools are stored as free text and mapped by admins.
Network access to `dapo.kemendikdasmen.go.id` and `sekolah.data.kemendikdasmen.go.id` must be allowed on the server (and in
the dev environment when testing the importer). Exact endpoints are verified when
the importer is built in phase 2.

## National region list (D-111)

The Referensi module also ships the Indonesian administrative regions (Kepmendagri 300.2.2-2138/2025 via
`cahyadsn/wilayah`, MIT): table `ref_wilayah` (code, parent code, level 1–4, official name), loaded from
`Database/Data/wilayah/` like the SD/MI list (version-gated `referensi:import --bawaan`, setting
`Referensi.regionsVersion`; converter `scripts/wilayah.mjs`). Applicants pick their address province → regency/city →
district → village from it (public JSON lookups `/wilayah/daftar` and `/wilayah/cari`, throttled and cached);
`applicants` stores the four codes next to the display names in the old text columns ("Kab. Kudus"). Without
JavaScript, or for a place not in the list, the text fields still work. Older typed addresses are matched once
(`RegionMatcher`/`AddressBackfill`, command `referensi:cocokkan-alamat`, or Sekolah Asal → Alamat pendaftar).
Statistik groups applicants by these codes and names. Details: `docs/modules/referensi.md`.
