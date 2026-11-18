# Phase 3 plan — Placement test

Scope (roadmap): test calendar (holidays excluded), sessions/rooms/quotas set by the
school, automatic allocation by registration order (online and offline equal),
attendance by QR scan or search (Belum tes / Hadir / Tidak hadir / Dijadwalkan ulang),
every schedule change logged (who, when, from → to, reason). Requirements FR-TEST-01…04.
Process: `docs/coordination.md`. Decisions: D-059 … D-062.

Integration branch: **`phase-3`**. Coordinator session label: `spmb:coordinator`.

**Status: done.** Foundation #18, three roles #19 (D-063), B #20, A #22, C #21 merged into `phase-3`; 424 tests.
As built: `assign()` also takes `$allowOverQuota` (walk-ins); `assign()`/`move()` return `TestAssignmentModel::forApplicant()`;
details settled during the build are in D-064.
Module: **`Tes`** (`app/Modules/Tes`, label "Tes Penempatan", requires Pendaftaran).

## What the foundation already provides

| Piece | Where |
| --- | --- |
| Module folder, `Config/Module.php`, `Menu.php` (group "Tes" + Pengaturan → Tes), `Audit.php` | `app/Modules/Tes/Config/` |
| Route files, one per package, loaded by `Config/Routes.php` | `Config/Routes/{jadwal,penjadwalan,kehadiran}.php` (empty groups, namespace `Modules\Tes\Controllers`) |
| Migrations for every table | see "Tables" |
| Models (Auditable except the change log) | `Modules\Tes\Models\{TestRoomModel, TestHolidayModel, TestSessionModel, TestAssignmentModel, TestScheduleChangeModel}` |
| Read helpers | `TestSessionModel::withStats(filters)`, `findWithStats(id)`; `TestAssignmentModel::forApplicant(id)`, `forSession(id)`; `TestHolidayModel::isHoliday(date)`, `between(from, to)`; `TestRoomModel::active()`; `TestScheduleChangeModel::forApplicant(id)` |
| Schedule reader for other modules | `Modules\Tes\Libraries\TestSchedule`: `forApplicant(id)`, `isVisibleToApplicants()`, `cardLine(id)`, static `sessionLabel(row)`, `dayLabel(date)`, `timeLabel(start, end)` |
| Card integration | `CardPdf::details()['testStatus']` = `TestSchedule::cardLine()` when Tes is on (PDF card, `/admin/kartu` lookup) |
| Panel slots (new) | `card_check` on `/kartu/<token>` for signed-in staff (`applicantId`, `cardState`); `card_lookup` on `/admin/kartu` (`applicant`); `dashboard` on `/admin` (`user`). Existing: `applicant_admin`, `applicant_public` |
| Settings | `app/Config/Tes.php` → `setting('Tes.scheduleVisible')`, `eligibleStatuses`, `requireCompleteDocuments`, `instructions`, `defaultDurationMinutes` |
| Status labels and badges | `lang('Status.test.<code>')` (`unscheduled`, `pending`, `present`, `absent`, `rescheduled`); `status_badge_for('test', $code)` |
| Shared strings | `Language/id/Tes.php` (`lang('Tes.room')`, `Tes.change.moved`, …) |
| Permissions (already in `AuthGroups`) | `tes.atur` (Super Admin, Admin Sekolah), `tes.kehadiran` (+ Panitia) — only three roles (D-063): tests use `admin`/`panitia`, and `Tests\Support\ViewerAccounts::viewer()` for a staff account with chosen permissions only |
| Camera QR scanner | `public/build/scanner.js` (`resources/js/scanner.js`, `qr-scanner` MIT): `SpmbScanner.start(video, onText) → Promise<stop>`, `SpmbScanner.cardCode(text)`, `SpmbScanner.hasCamera()` |
| Guide sections | `GuideBuilder` also reads `Guide/<audience>/*.md` (file name order) after `Guide/<audience>.md`, so packages own separate files |
| Test fixtures | `tests/_support/Tes/TesFixtures.php`: `makeWave`, `makeApplicant`, `makeRoom`, `makeSession`, `makeAssignment`, `makeStaff` |

## Tables

| Table | Owner | Notes |
| --- | --- | --- |
| `test_rooms` | A | `name`, `capacity`, `location` (shown to applicants), `sort_order`, `is_active`, soft delete |
| `test_holidays` | A | `date` unique, `note` |
| `test_sessions` | A | `academic_year`, `wave_id` (null = every wave), `test_date`, `starts_at`/`ends_at` (TIME), `room_id`, `quota` (defaults to room capacity), `label`, `is_active` (0 = no new allocations), `notes`, soft delete |
| `test_assignments` | B (schedule) / C (attendance) | one row per applicant (`applicant_id` unique), `session_id`, `seat_number`, `status` pending/present/absent/rescheduled, `attended_at`, `recorded_by`, `assigned_by` (null = automatic) |
| `test_schedule_changes` | B | insert-only log: `applicant_id`, `from_session_id`, `to_session_id`, `kind` allocated/assigned/moved/removed, `reason`, `changed_by` (null = system), `created_at` |

Seats taken = assignments of applicants that are not cancelled and not deleted (a
cancelled applicant frees the seat; the assignment row stays for the record).

A package that needs another column adds a **new** migration in the module with a
timestamp in its range: A `2026-11-21-1000xx`, B `2026-11-21-1100xx`, C `2026-11-21-1200xx`.
Never edit foundation migrations.

## Rules that every package follows (D-059 … D-062)

- **Writes**: `test_assignments` only through B's `Scheduler` (assign/move/remove) and C's
  `Attendance` (status). Each write logs one audit event (`test_assigned`, `test_moved`,
  `test_removed`, `test_attendance`, one `test_allocated` per allocation run) with the
  model callbacks off for bulk work, like D-042. Schedule changes also add a
  `test_schedule_changes` row.
- **Order** (FR-TEST-02): registration order = `applicants.id` within the academic year
  (numbers are taken in the same transaction as the insert, D-031), online and offline equal.
- **Sessions in time order**: `test_date`, `starts_at`, room `sort_order`, room `name`.
- **Holidays**: no session may be created on a holiday; the generator skips them; allocation
  skips sessions on a holiday (if one was added later).
- **Waves**: a session with `wave_id` takes only applicants of that wave; `wave_id` null takes any.
- **Dates and times** shown with `TestSchedule::dayLabel()` / `timeLabel()` / `sessionLabel()`.
- **Applicants** see their schedule only when `Tes.scheduleVisible` is on.

## Work packages

Branch from `phase-3`, PR into `phase-3`. "Files" = the only files the package may
create or change. Tests are named after the package (`tests/**/TesJadwal*Test.php`, …).

### A — Jadwal (rooms, calendar, sessions, settings) · branch `feat/p3-jadwal`

Requirements: FR-TEST-01, D-059.

- `/admin/tes/ruang` list, `/tambah`, `/{id}/ubah`, POST `/{id}/nonaktifkan`, `/{id}/aktifkan`,
  POST `/{id}/hapus` (soft delete only when no session uses it) — `tes.atur`.
- `/admin/tes/kalender`: month view (Flowbite-style grid, responsive: list on phones) of the
  academic year's test days with sessions per day (filled/quota) and holidays; navigation by
  month (`?bulan=2027-06`); POST `/admin/tes/kalender/libur` (date + note; refused when a
  session exists that day — tell the user to move or delete those sessions first),
  POST `/admin/tes/kalender/libur/{id}/hapus` — `tes.atur`.
- `/admin/tes/sesi` list with filters (date, wave, room, active) and counts from `withStats()`;
  `/tambah`, `/{id}/ubah` (quota may not go below seats taken; room/date/time change of a session
  with participants asks for confirmation and is audited — participants keep their seat),
  POST `/{id}/nonaktifkan`, `/{id}/aktifkan`, POST `/{id}/hapus` (soft; only without participants)
  — `tes.atur`. Validation: end after start; no two sessions in the same room overlapping in time
  on the same date; not on a holiday; quota 1…999.
- `/admin/tes/sesi/buat-banyak` session generator (`Libraries/SessionGenerator.php`): date range,
  weekdays (checkboxes, default Mon–Sat), one or more time slots (start–end; default length
  `Tes.defaultDurationMinutes`), rooms (checkboxes; quota = room capacity), optional wave, label
  pattern ("Sesi {n}" per day by start time). Preview table (days, sessions, seats, skipped
  holidays/overlaps) → POST creates them in one transaction; one audit event `sessions_generated`
  (ask the coordinator to add its label).
- `/admin/pengaturan/tes` (`tes.atur`): `scheduleVisible`, `eligibleStatuses` (checkboxes of
  applicant statuses except cancelled), `requireCompleteDocuments`, `instructions` (textarea, max
  500), `defaultDurationMinutes` (30…480). Saved with `setting()`, audited like other settings pages.
- `Database/Seeds/DemoSeeder.php` (`spmb:install --demo`): 4 rooms, 2 holidays, sessions on the
  6 weekdays after today + 30 days (2 slots × rooms), idempotent; at the end
  `if (class_exists(DemoAllocationSeeder::class)) $this->call(DemoAllocationSeeder::class);`
  (package B provides it).
- **Provides**: `SessionGenerator::preview(array $input): array`, `create(array $input, int $staffId): int`;
  `TestSessionModel` validation and any extra read methods A needs.
- Files: `app/Modules/Tes/Controllers/{Ruang,Kalender,Sesi,Pengaturan}.php`,
  `app/Modules/Tes/Views/{ruang,kalender,sesi,pengaturan}/**`, `Config/Routes/jadwal.php`,
  `Language/id/TesJadwal.php`, `Libraries/SessionGenerator.php`, `Models/{TestRoomModel,TestHolidayModel,TestSessionModel}.php`
  (add methods/validation only), `Database/Seeds/DemoSeeder.php`, migrations in A's range,
  `Guide/admin-sekolah/10-jadwal.md`, `tests/**/TesJadwal*`, `docs/modules/tes-jadwal.md`.

### B — Penjadwalan (allocation, moves, change log, applicant schedule) · branch `feat/p3-penjadwalan`

Requirements: FR-TEST-02, FR-TEST-04, D-060, D-062.

- `Services/Scheduler.php` (+ `SchedulerException` with a friendly Indonesian message):
  - `allocate(array $options, bool $dryRun = false): array` — options `academic_year` (default
    setting), `wave_id` (null = all waves), `from_date` (default tomorrow). Eligible: applicants of
    the year, status in `Tes.eligibleStatuses`, not deleted, without an assignment, documents
    complete when `Tes.requireCompleteDocuments`. Fills active sessions from `from_date` in time
    order (skipping holidays, respecting session waves) up to their free seats, in registration
    order; `seat_number` continues after the session's highest. Never moves anyone already
    scheduled. Lock the sessions (`SELECT … FOR UPDATE`) in one transaction. Report:
    `['placed' => int, 'unplaced' => int, 'sessions' => list<['session_id', 'label', 'count', 'first_number', 'last_number']>, 'unplacedNumbers' => list<string>]`.
  - `assign(int $applicantId, int $sessionId, int $staffId, ?string $reason = null, bool $allowOverQuota = false): array` — first
    seat (kind `assigned`, status pending).
  - `move(int $applicantId, int $sessionId, int $staffId, string $reason, bool $allowOverQuota = false): array`
    — reason required (min 5 characters); status becomes `rescheduled`; attendance fields cleared;
    kind `moved`. A full session is refused unless `$allowOverQuota` (staff confirmed).
  - `remove(int $applicantId, int $staffId, string $reason): void` — kind `removed`, row deleted.
  - Refuse: cancelled/deleted applicant, inactive/deleted session, session of another academic
    year or another wave, session on a holiday, session in the past (except `move` by staff with
    `tes.atur`).
- `/admin/tes/penjadwalan` (`tes.atur`): counts (eligible without seat per wave, free seats from
  `from_date`), form (wave, from date) → preview table (session, count, number range) and the
  unplaced count with a hint to add sessions → POST runs it (confirm step), flash summary.
- `/admin/tes/peserta` (`tes.kehadiran`): participants list, filters (date, session, status incl.
  "Belum dijadwalkan", wave, search number/name), 25 per page, links to the applicant;
  export not in this phase.
- `/admin/pendaftar/{id}/tes` (`tes.kehadiran`): give a seat or move (session select with free
  seats, reason), POST `/admin/pendaftar/{id}/tes/hapus` (`tes.atur`) with reason.
- `/admin/tes/riwayat-jadwal` (`tes.kehadiran`): change log, newest first, filters (date range,
  kind, search), from → to labels, staff name or "Sistem".
- Panels: `Views/panels/applicant_admin.php` (schedule, status badge, last 5 changes, buttons to
  move / give a seat), `Views/panels/applicant_public.php` (when `scheduleVisible`: day, time,
  room, location, status, `Tes.instructions`; otherwise `Tes.scheduleLater`; nothing for cancelled applicants).
- `Database/Seeds/DemoAllocationSeeder.php`: runs `allocate()` for the demo applicants (idempotent).
- **Provides**: `Scheduler` (C uses `move()` to seat a walk-in at today's session).
- Files: `app/Modules/Tes/Controllers/{Penjadwalan,Peserta,JadwalPendaftar,RiwayatJadwal}.php`,
  `Views/{penjadwalan,peserta,jadwal_pendaftar,riwayat_jadwal}/**`, `Views/panels/applicant_admin.php`,
  `Views/panels/applicant_public.php`, `Services/{Scheduler,SchedulerException}.php`,
  `Config/Routes/penjadwalan.php`, `Language/id/TesPenjadwalan.php`,
  `Models/{TestAssignmentModel,TestScheduleChangeModel}.php` (add methods only),
  `Database/Seeds/DemoAllocationSeeder.php`, migrations in B's range, `Guide/admin-sekolah/20-penjadwalan.md`,
  `Guide/panitia/20-jadwal-tes.md`, `Guide/calon-murid.md`, `tests/**/TesPenjadwalan*`,
  `docs/modules/tes-penjadwalan.md`.

### C — Kehadiran (attendance, scan, attendance sheet, dashboard) · branch `feat/p3-kehadiran`

Requirements: FR-TEST-03, D-061.

- `Services/Attendance.php`:
  - `resolve(string $input): ?array` — card URL or token (`CardTokenService::resolve`, revoked
    cards refused with a clear message), registration number (exact, case-insensitive) →
    `['applicant' => row, 'schedule' => TestSchedule::forApplicant() ?: null]`; null when unknown.
  - `record(int $applicantId, string $status, int $staffId): array` — `present` (sets
    `attended_at`, `recorded_by`), `absent`, or `pending` (undo; clears both). Only for a scheduled,
    not cancelled applicant. Event `test_attendance` (old → new status).
  - `markRemainingAbsent(int $sessionId, int $staffId): int` — after the session ended: pending and
    rescheduled participants become absent; one audit event per applicant.
- `/admin/tes/kehadiran` (`tes.kehadiran`): today's sessions first (counts present/absent/pending),
  then a date picker for other days.
- `/admin/tes/kehadiran/sesi/{id}`: roster (`forSession()`), big Hadir / Tidak hadir buttons per row
  (forms; with JavaScript they post via fetch and update the row without reload), search box,
  "Pindai Kartu" panel: camera scan (`scanner.js`, start/stop button, works on phones) and a text
  field for USB scanners or typed numbers. A scan of someone in this session marks them present
  (with a visible confirmation and an undo button). Someone scheduled elsewhere: show their
  schedule and offer "Pindahkan ke sesi ini dan catat hadir" (B's `Scheduler::move()` with reason
  "Hadir di sesi lain", `allowOverQuota` after confirmation) → then present. Not scheduled: offer the
  same (assign + present). POST `/admin/tes/kehadiran/sesi/{id}/tidak-hadir-semua` (confirm).
- `/admin/tes/sesi/{id}/daftar-hadir` (`tes.kehadiran`): printable attendance sheet PDF (FPDF:
  school header, session label, table no./number/name/gender/signature, totals) — `Libraries/AttendanceSheetPdf.php`.
- Panels: `Views/panels/card_check.php` (staff with `tes.kehadiran`: schedule + status + "Catat Hadir"
  POST to `/admin/tes/kehadiran/catat`, back to the check page), `Views/panels/card_lookup.php`
  (schedule and status on `/admin/kartu`), `Views/panels/dashboard.php` (staff with
  `tes.kehadiran`: today's sessions and present/total; with `tes.atur` also "belum dijadwalkan" count).
- POST `/admin/tes/kehadiran/catat` (`tes.kehadiran`): `applicant_id`, `status`, `session_id` (optional:
  must match), `kembali` (safe relative return URL); JSON when `Accept: application/json`.
- **Provides**: `Attendance` service.
- Files: `app/Modules/Tes/Controllers/{Kehadiran,DaftarHadir}.php`, `Views/kehadiran/**`,
  `Views/panels/{card_check,card_lookup,dashboard}.php`, `Services/Attendance.php`,
  `Libraries/AttendanceSheetPdf.php`, `Config/Routes/kehadiran.php`, `Language/id/TesKehadiran.php`,
  migrations in C's range, `Guide/panitia/30-kehadiran.md`,
  `Guide/admin-sekolah/30-kehadiran.md`, `tests/**/TesKehadiran*`, `docs/modules/tes-kehadiran.md`.

## Dependencies between packages

All three start at once against the foundation. C uses B's `Scheduler::move()`/`assign()` and A's
pages only through links: until B is merged, C codes against the signature above, guards the
"move here" action with `class_exists(Scheduler::class)`, and tests it after B is in (the
coordinator runs the combined suite). Rows for tests are made with `TesFixtures`.
Expected merge order: A, B, C.

## Coordinator-only files

Everything listed in `docs/phase-2-plan.md` → "Coordinator-only files", plus
`app/Modules/Tes/Config/{Module,Menu,Routes,Audit}.php`, `Language/id/Tes.php`,
`Libraries/TestSchedule.php`, the foundation migrations, existing model method signatures,
`app/Config/Tes.php` (ask for new settings), `tests/_support/Tes/TesFixtures.php`,
`app/Modules/Kartu/**` and `app/Views/admin/dashboard.php` (panel slots are in place), and
`docs/modules/tes.md`.

## Done means (every package)

- Every route has permission tests (allowed role, denied role, guest) and the happy path + main
  refusals are tested; `composer test`, `composer lint` (`vendor/bin/php-cs-fixer fix --dry-run`)
  and `npm run build` pass.
- Playwright screenshots at 360 and 1280 px of each new page (light; one dark), checked by you.
- Breadcrumbs on every page, friendly Indonesian (`spmb-copy`), empty states with the existing
  illustrations (`state/kosong-data`, `state/kosong-pencarian`, `state/kosong-riwayat`), no horizontal scroll at 360 px.
- PR description: summary, screenshots list, proposed decisions/changelog lines/requirement
  status, "Needs from coordinator" (audit labels, settings, shared strings).
