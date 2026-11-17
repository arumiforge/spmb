# Architecture

> Stage: foundation. Sections marked *(planned)* describe the target design and
> become descriptive as modules are built.

## Stack

| Layer | Choice |
| --- | --- |
| Language / framework | PHP 8.3, CodeIgniter 4.7 (Composer dependency, appstarter layout) |
| Database | MySQL 8.0, `utf8mb4_unicode_ci` |
| Web server | Nginx + PHP-FPM 8.3 on Ubuntu Server 24.04 LTS |
| Edge | Cloudflare (DNS, TLS, WAF) via Cloudflare Tunnel (`cloudflared`), no inbound ports |
| Remote access | SSH through Cloudflare Access (`cloudflared access ssh`) |
| Frontend | Tailwind CSS v4 + Flowbite, ApexCharts, built with npm *(planned, phase 1)* |
| CI/CD | GitHub Actions: lint, tests on MySQL, commit policy, manual release deploy |

Planned libraries (added in the phase that needs them, see `decisions.md`):
CodeIgniter Shield (auth, groups, permissions), `pragmarx/google2fa` (TOTP),
`dompdf/dompdf` (PDF), `phpoffice/phpspreadsheet` (XLSX import/export),
`chillerlan/php-qrcode` (QR), CI4 Image library with GD (resize, WebP).

## Code layout

```
app/
├── Config/            framework + app config (Autoload registers Modules\)
├── Modules/<Name>/    feature modules — see .claude/skills/spmb-module
├── Views/layouts/     admin (full width) · public · auth            (planned)
├── Views/partials/    breadcrumbs, flash, empty-state, table, form   (planned)
public/                web root (index.php, built assets)
writable/              logs, cache, sessions, uploads (private)
deploy/activate.sh     server-side release activation
ai/                    ChatGPT image prompts and raw outputs
flowbite/              Flowbite Pro templates — markup reference only (private repo)
```

## Modules *(planned)*

| Module | Responsibility | Phase |
| --- | --- | --- |
| Core | Layouts, breadcrumbs, settings, white label, module registry, menu | 1 |
| Auth | Login, roles/permissions, optional TOTP 2FA, user management | 1 |
| Audit | Change and login logs | 1 |
| Panduan | Role-based Indonesian guide pages assembled from module `Guide/` | 1 |
| Referensi | Normalised schools (SD/MI), regions; import from public data | 2 |
| Pendaftaran | Waves, online/offline form, numbering, duplicates, status check | 2 |
| Berkas | Required documents, uploads, resize, verification | 2 |
| Kartu | PDF card, QR token, verification page | 2 |
| JadwalTes | Calendar, sessions, rooms, allocation, attendance, reschedule log | 3 |
| DaftarUlang | Re-registration processing | 4 |
| Seragam | Uniform status (stock/packages later) | 4 |
| Laporan | Dashboard stats, filters, XLSX/CSV/PDF export, XLSX import | 4 |
| Situs | Public pages and announcements | 2 → 5 |
| Analitik | Light visitor tracking | 5 |
| Chat | Interactive chat (scope pending) | 5 |

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

## Public school reference data *(planned, phase 2)*

Source: Dapodik public recap data at <https://dapo.kemendikdasmen.go.id/>
(Kemendikdasmen; the old `dapo.kemdikbud.go.id` address is obsolete) for SD in Central Java,
with <https://sekolah.data.kemendikdasmen.go.id/> (school reference data by NPSN)
as the alternative/secondary source — to verify in phase 2 whether it also lists MI. If not, MI
(Kemenag/EMIS) are loaded through an XLSX import.
A `spark referensi:import` command fetches and upserts into `ref_sekolah`
(NPSN, name, type, status, district, sub-district); applicants pick from an
autocomplete; unknown schools are stored as free text and mapped by admins.
Network access to `dapo.kemendikdasmen.go.id` and `sekolah.data.kemendikdasmen.go.id` must be allowed on the server (and in
the dev environment when testing the importer). Exact endpoints are verified when
the importer is built in phase 2.
