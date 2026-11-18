# Phase 2 plan — Registration

Scope (roadmap): waves; online + offline form; one shared numbering sequence;
duplicate warning; previous-school reference data; document requirements, upload,
resize, storage outside `public/`, verification; PDF card with random QR token and
verification page; public status check (number + date of birth, D-008); basic
public pages. Process: `docs/coordination.md`. Decisions: D-029 … D-038.

Integration branch: **`phase-2`**. Coordinator session label: `spmb:coordinator`.

## What the foundation already provides (PR `feat/p2-foundation`)

| Piece | Where |
| --- | --- |
| Module folders + `Config/Module.php` (`requires`), `Menu.php`, `PublicMenu.php`, `Audit.php`, empty `Routes.php`, `Language/id/<Name>.php` | `app/Modules/{Referensi,Pendaftaran,Berkas,Kartu,Situs}` |
| Migrations for every shared table | see "Tables" below |
| Shared models (Auditable) | `Modules\Referensi\Models\SchoolModel`, `Modules\Pendaftaran\Models\{WaveModel,ApplicantModel}`, `Modules\Berkas\Models\{DocumentTypeModel,ApplicantDocumentModel}`, `Modules\Kartu\Models\CardTokenModel` |
| Settings defaults | `app/Config/{Pendaftaran,Berkas,Kartu,Referensi}.php` → `setting('Pendaftaran.academicYear')` … |
| Permission `referensi.kelola` (Super Admin, Admin Sekolah) | `app/Config/AuthGroups.php`, `tests/unit/AuthGroupsTest.php` |
| Applicant session grant (D-008, D-034) | `service('applicantAccess')`: `grant(id)`, `id()`, `allows(id)`, `revoke()` |
| Shared status labels | `app/Language/id/Status.php`; `status_badge_for($type, $code)` for `partials/status_badge` |
| Panels (one module's section on another module's page) | `module_panels('applicant_admin', [...])` in `ui_helper` |
| Public navigation from modules | `Config/PublicMenu.php` read by `layouts/public` |
| Audit labels from modules | `Config/Audit.php` (events, subjects, settingGroups, fields, eventColors, boolFields) read by Riwayat |
| Installer hooks | `spmb:install` runs `Modules\<M>\Database\Seeds\InstallSeeder` (defaults) and, with `--demo`, `DemoSeeder` — both idempotent |
| Dependencies | `setasign/fpdf` (MIT, PDF card, D-030), `phpoffice/phpspreadsheet` (MIT, XLSX import) |

## Tables

| Table | Owner | Notes |
| --- | --- | --- |
| `ref_sekolah` | A | `npsn` unique (nullable), `level` SD/MI/…, `source` dapodik/emis/import/manual, `is_active` |
| `waves` | B | `academic_year`, `name`, `starts_on`, `ends_on` (dates, inclusive), `quota` (null = none), `is_active`, soft delete |
| `registration_counters` | B | `scope` PK (academic year by default), `last_number`; lock with `SELECT … FOR UPDATE` |
| `applicants` | B | see migration; `status` registered/verified/cancelled; `document_status` incomplete/pending/to_follow/complete (written by C); `method` online/offline; `name_key` normalised name; `possible_duplicate`; `access_token_hash` (sha256 of private-link token); soft delete |
| `document_types` | C | `code` unique, `is_required`, `may_follow`, `max_size_kb`, `extensions` csv, `sort_order`, soft delete |
| `applicant_documents` | C | `file_path` relative to `writable/uploads/berkas/` (null for to_follow), `status` pending/valid/invalid/to_follow, `verified_by/at`, soft delete |
| `card_tokens` | D | `token` (32 random bytes base64url) unique, `revoked_at`, `last_seen_at` |

A package that needs another column adds a **new** migration in its own module
with a timestamp in its range: A `2026-11-19-1000xx`, B `2026-11-19-1100xx`,
C `2026-11-19-1200xx`, D `2026-11-19-1300xx`. Never edit foundation migrations.

## Work packages

Branch from `phase-2`, PR into `phase-2`. "Files" = the only files the package may
create or change. Tests are named after the module (`tests/feature/Pendaftaran*Test.php`, …).

### A — Referensi (previous schools) · branch `feat/p2-referensi`

Requirements: FR-REG-06, D-012, FR-RPT-04 (school import part).

- Admin `/admin/referensi/sekolah` (list, search, filter level/regency, add, edit,
  deactivate), `/admin/referensi/sekolah/impor` (CSV/XLSX upload with row-level
  report), `/admin/referensi/sekolah-lain` (free-text schools typed by applicants:
  map them to a list entry or add them; updates `applicants.previous_school_id`
  through `ApplicantModel`, event `school_mapped`). Permission `referensi.kelola`.
- `php spark referensi:import` fetching SD (and MI if available) for
  `setting('Referensi.provinces')` from the public Dapodik data
  (`dapo.kemendikdasmen.go.id`, fallback `sekolah.data.kemendikdasmen.go.id`);
  verify the real endpoints; upsert by NPSN; event `schools_imported`. Network is open.
  Commit a small fixture for tests, not the full data set.
- **Provides** (interface):
  - `GET /sekolah-asal/cari?q=<text>` public JSON, throttled (`service('throttler')`,
    e.g. 30/min per IP): `[{"id":1,"npsn":"20319xxx","name":"SD N 1 …","level":"SD","district":"…","regency":"…","label":"SD N 1 … — Kec. …, Kab. …"}]`,
    max 20, active only, at least 2 characters, configured provinces first.
  - View `Modules\Referensi\Views\partials\school_picker` with one array `picker`:
    `['id' => ?int, 'name' => ?string, 'error' => ?string, 'required' => bool]`.
    It posts `previous_school_id` (hidden) and `previous_school_name` (free text,
    "Sekolah saya tidak ada di daftar"); works without JavaScript (free text).
  - `SchoolModel::label(int $id): ?string`.
  - `Database/Seeds/DemoSeeder` with ~20 sample schools (development).
- Files: `app/Modules/Referensi/**` (except the foundation migration),
  `app/Config/Referensi.php`, `tests/**/Referensi*`, `tests/_support/Referensi/**`,
  `docs/modules/referensi.md`.

### B — Pendaftaran (waves, numbering, form, duplicates, status check) · branch `feat/p2-pendaftaran`

Requirements: FR-REG-01…05, FR-REG-07, D-008, D-031, D-032, D-034, D-037.

- Admin `/admin/pengaturan/gelombang` (CRUD waves, no overlapping dates within a
  year) and `/admin/pengaturan/pendaftaran` (academic year, number format + live
  example, number scope, online open, required/hidden fields) —
  `pengaturan.pendaftaran`.
- `RegistrationNumber::next(string $academicYear): string` — transaction, row lock
  on `registration_counters`, tokens `{YEAR}`, `{YY}`, `{0000}`; unit tests incl.
  formatting and "never reused" after delete.
- `ApplicantService::register(array $data, string $method, ?int $staffId): array`
  → `['applicant' => row, 'token' => plain private-link token]`; picks the wave from
  today's date (`WaveModel::current()`), offline staff may choose a wave; event
  `applicant_registered`.
- `DuplicateChecker::find(array $data, ?int $exceptId = null): list<array>` — NIK,
  NISN, `name_key` + `birth_date`.
- Public `/daftar` (form, illustration `form/daftar-online`), `/daftar/selesai`
  (number, private link shown once, illustration `state/sukses-pendaftaran`, then
  set briefs 002 and 006 to `done` in `ai/`), closed state when no wave is open or
  `onlineOpen` is off.
- Public `/cek-status` (number + date of birth, throttled per
  `statusCheckAttempts`, generic error), `/cek-status/data` (applicant's own page:
  status, data, `module_panels('applicant_public', …)`), `/cek-status/ubah`
  (correct contact/address fields; identity fields only by staff), `/cek-status/keluar`,
  `/tautan/<token>` (private link → `applicantAccess()->grant()`).
- Admin `/admin/pendaftar` (list with search and filters: wave, method, status,
  document status, possible duplicate; NIK masked), `/admin/pendaftar/tambah`
  (offline form, shows duplicates and asks to confirm), `/admin/pendaftar/<id>`
  (detail + `module_panels('applicant_admin', …)`), `/admin/pendaftar/<id>/ubah`,
  POST `/admin/pendaftar/<id>/status`, POST `/admin/pendaftar/<id>/hapus` (soft,
  `pendaftar.delete`). Permissions `pendaftar.view/create/edit/delete`.
- Dashboard: real counts on `/admin` (total, today, per wave, online/offline,
  documents complete) replacing the placeholders.
- `Database/Seeds/DemoSeeder`: waves for the configured year and ~30 demo applicants.
- **Provides**: `ApplicantModel`, `WaveModel` methods `current(?DateTimeInterface)`,
  `forYear(string)`; the services above; the panel slots `applicant_admin` and
  `applicant_public`; `ApplicantModel::findByNumberAndBirthDate()`.
- Files: `app/Modules/Pendaftaran/**` (except foundation migrations), `app/Config/Pendaftaran.php`,
  `app/Controllers/Admin/Dashboard.php`, `app/Views/admin/dashboard.php`,
  `ai/prompts/002*`, `ai/prompts/006*`, `ai/README.md` status rows for 002/006,
  `tests/**/Pendaftaran*`, `docs/modules/pendaftaran.md`.

### C — Berkas (documents) · branch `feat/p2-berkas`

Requirements: FR-DOC-01…04, D-036.

- Admin `/admin/pengaturan/berkas` (document types CRUD + order, image settings) —
  `pengaturan.pendaftaran`.
- Applicant upload on their own page: panel `applicant_public` (one row per type:
  upload / replace / "menyusul" when `may_follow`), POST `/cek-status/berkas/<typeId>`,
  GET `/cek-status/berkas/<docId>` (own file only, `applicantAccess()->allows()`).
- Staff: panel `applicant_admin` (list, view, upload on behalf, verify),
  POST `/admin/pendaftar/<id>/berkas/<typeId>` (`pendaftar.edit`),
  GET `/admin/berkas/<docId>/lihat` (`pendaftar.view`), POST `/admin/berkas/<docId>/periksa`
  (valid / invalid + note, `pendaftar.verifikasi`), queue `/admin/berkas`
  (documents waiting for a check, `pendaftar.verifikasi`).
- Storage `writable/uploads/berkas/<yyyy>/<random>.<ext>`; validate extension +
  real MIME + size per type; images resized (`Berkas.imageMaxEdge`) and saved as JPEG
  (`Berkas.imageQuality`), EXIF stripped; PDFs kept as uploaded within the size limit.
- `DocumentStatus::refresh(int $applicantId): string` recomputes
  `applicants.document_status` through `ApplicantModel` after every change.
- `Database/Seeds/InstallSeeder`: default types (Kartu Keluarga, Akta Kelahiran,
  Pas Foto, Rapor/Surat Keterangan Lulus) — defaults until the school answers.
- **Provides**: `DocumentTypeModel::active(): list<array>` (for `/persyaratan`),
  `DocumentStatus::refresh()`, the two panels.
- Files: `app/Modules/Berkas/**` (except foundation migrations), `app/Config/Berkas.php`,
  `tests/**/Berkas*`, `docs/modules/berkas.md`.

### D — Kartu + Situs (card, QR, public pages) · branch `feat/p2-kartu-situs`

Requirements: FR-CARD-01…04, FR-WEB-01 (Persyaratan, Jadwal Gelombang), FR-WEB-02, D-030, D-035.

- `CardTokenService`: `tokenFor(int $applicantId, ?int $staffId = null): string`
  (active token or a new one), `renew(int $applicantId, int $staffId): string`
  (revokes the old one, `card_revoked` + `card_issued`), `resolve(string $token): ?array`
  (applicant row or null when unknown/revoked).
- `CardAvailability::allows(array $applicant): bool` (`Kartu.availableWhen`).
- PDF card with FPDF (A6 or half A4; school logo/name from `School.*`, number, name,
  wave, previous school, method, test status "Belum dijadwalkan" until phase 3, QR
  PNG via chillerlan pointing to `/kartu/<token>`). Never creates data except the
  first token. GET `/cek-status/kartu` (applicant, availability rule),
  GET `/admin/pendaftar/<id>/kartu` (`kartu.cetak`, event `card_printed`),
  POST `/admin/pendaftar/<id>/kartu/baru` (`kartu.cetak`).
- Public `/kartu/<token>`: card check page, minimal data (number, name, wave, valid
  or not); signed-in staff with `pendaftar.view` also get a link to the applicant.
- Admin `/admin/kartu` (enter/scan a card code or registration number → applicant,
  `kartu.cetak`), `/admin/pengaturan/kartu` (availability, header, footer note —
  `pengaturan.pendaftaran`). Panels `applicant_admin` (print/renew buttons) and
  `applicant_public` (download button or "available after check" message).
- Situs: `/persyaratan` (active document types via `DocumentTypeModel::active()` when
  Berkas is on, plus registration steps), `/jadwal` (waves of the academic year with
  open/closed state), and the home page: "Daftar Sekarang" when online registration
  is open, "Cek Status" always.
- Files: `app/Modules/Kartu/**`, `app/Modules/Situs/**` (except foundation migrations),
  `app/Config/Kartu.php`, `app/Views/home.php`, `app/Controllers/Home.php`,
  `tests/**/Kartu*`, `tests/**/Situs*`, `tests/feature/HomeTest.php`,
  `docs/modules/kartu.md`, `docs/modules/situs.md`.

## Dependencies between packages

All packages start at once against the foundation. Where a package uses another
package's service that is not merged yet, it codes against the interface above and
tests with rows inserted through the shared models. Panels render nothing when
there is nothing to show, so pages work with any subset of modules switched on.
Merge order (expected): A, B, C, D — the coordinator resolves anything that crosses.

## Coordinator-only files

`app/Config/{AuthGroups,Features,Routes,Filters,Services,Autoload,Audit}.php`,
`app/Libraries/**`, `app/Helpers/**`, `app/Views/layouts/**`, `app/Views/partials/**`,
`app/Language/id/**`, `app/Modules/Riwayat/**`, foundation migrations and models'
existing signatures, `composer.json/.lock`, `package*.json`, `resources/**`,
`.github/**`, and the shared docs (`progress`, `decisions`, `CHANGELOG`,
`requirements`, `roadmap`, `architecture`, `README`, this plan).

## Questions for the school (answered 2026-11-18, see `interview/round-01.md` Outcomes, D-047…D-049)

| Question | Default in use | Setting |
| --- | --- | --- |
| Dates of waves 1–3; tests per wave or after all waves? | Admin enters waves; tests in phase 3 | waves table |
| Required documents, which may follow later, deadline | KK, Akta, Pas Foto required; Rapor/SKL may follow; no deadline | `document_types`, `Berkas.deadline` |
| Card printable immediately or after first check? | Immediately (D-035) | `Kartu.availableWhen` |
| Number format and yearly reset | `PMB-{YEAR}-{0000}`, restarts each academic year (D-031) | `Pendaftaran.numberFormat`, `numberScope` |
| Required form fields (NISN, NIK, KK, parent occupation/income …) | NIK optional, NISN required, KK and occupations hidden, no income (D-037) | `Pendaftaran.requiredFields`, `hiddenFields` |
| Previous schools outside Central Java | "Sekolah lain" free text, mapped by admins | `Referensi.provinces` |
