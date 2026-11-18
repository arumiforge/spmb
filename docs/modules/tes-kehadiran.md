# Tes — package C: Kehadiran (attendance, scanning, attendance sheet, dashboard)

Part of the [Tes module](tes.md); plan in `docs/phase-3-plan.md` (section C).
Requirement FR-TEST-03, decision D-061. Permission: `tes.kehadiran` for every route
(Super Admin, Admin Sekolah, Panitia; roles per D-063).

## Service: `Modules\Tes\Services\Attendance`

The only writer of `test_assignments.status` besides package B's `Scheduler`.
Refusals throw `DomainException` with a friendly Indonesian message.

| Method | Behaviour |
| --- | --- |
| `resolve(string $input): ?array` | Card URL (`…/kartu/<token>`), bare card code, or registration number (exact; the column collation makes it case-insensitive) → `['applicant' => row, 'schedule' => TestSchedule::forApplicant() ?: null]`. `null` when nothing matches. A revoked card throws (`TesKehadiran.scanRevoked`). Soft-deleted applicants are not found. |
| `record(int $applicantId, string $status, int $staffId): array` | `present` (sets `attended_at`, `recorded_by`), `absent` (`recorded_by`, clears `attended_at`), `pending` = undo (clears both). Refuses unknown/cancelled applicants and applicants without a seat. The assignment row is locked (`FOR UPDATE`) so two phones scanning the same card do not race. The same status again is a no-op (`changed` false, no audit). Returns the row + `previous` + `changed`. |
| `markRemainingAbsent(int $sessionId, int $staffId): int` | Only after the session's end time. `pending` and `rescheduled` participants (not cancelled/deleted) become `absent`, one bulk update, one audit event per applicant. |
| `hasEnded(array $session)`, `isRunning(array $session)` | Server-time helpers for the UI. |

Audit: event `test_attendance` on subject `applicants/<id>`, old `{status}` → new
`{status, session_id}`, description "Kehadiran tes PMB-…: Belum tes → Hadir". The model's
generic callbacks are off for these writes (D-042 pattern).

## Routes (`Config/Routes/kehadiran.php`)

| Method | URL | Purpose |
| --- | --- | --- |
| GET | `/admin/tes/kehadiran` (`?tanggal=Y-m-d`) | Sessions of one day (today by default) with present/total, state (belum mulai / berlangsung / selesai), the list of test days and a date picker |
| GET | `/admin/tes/kehadiran/sesi/{id}` (`?q=`) | Roster: counts, "Pindai Kartu" panel, participants with Hadir / Tidak hadir / Batalkan, "Tandai Sisanya Tidak Hadir" |
| POST | `/admin/tes/kehadiran/catat` | `applicant_id`, `status`, `session_id` (optional, must match the applicant's session), `kembali` (relative path; anything with a scheme/host or odd characters falls back to the roster or the day page) |
| POST | `/admin/tes/kehadiran/sesi/{id}/pindai` | `kode`: resolves and, for someone of this session, marks present |
| POST | `/admin/tes/kehadiran/sesi/{id}/pindahkan` | `applicant_id`, `lebih_kuota`: `Scheduler::move()` (reason "Hadir di sesi lain") or `assign()` for a walk-in without a seat, then `record(present)` |
| POST | `/admin/tes/kehadiran/sesi/{id}/tidak-hadir-semua` | `markRemainingAbsent()` (confirm in the browser) |
| GET | `/admin/tes/sesi/{id}/daftar-hadir` | Attendance sheet PDF (`Libraries\AttendanceSheetPdf`) |

Every POST works as a plain form (redirect + flash). With `Accept: application/json` it
answers `{ok, message, csrf, applicantId?, rowHtml?, counts?}`; the scan and move routes add
`state` and `resultHtml`. HTML fragments are rendered by the same partials as the page
(`Views/kehadiran/_row.php`, `_scan_result.php`), so the script only swaps them in. The CSRF
token is regenerated on every POST, so each JSON answer carries the new hash and the script
updates every form. Without JavaScript the scan result is passed to the roster as flash data
`tesScan`.

Scan states: `marked`, `already`, `elsewhere` (offer to move here), `unscheduled` (offer to
assign here), `cancelled`, `revoked`, `notFound`, `error`. When the session is full, the
move form sends `lebih_kuota=1` and the button asks for confirmation (`data-ask`).

The "move here" action needs package B's `Scheduler`; it is guarded with
`class_exists(Scheduler::class)` and explains that moving is not available otherwise.

## Roster page (phones in the test room)

- Buttons are at least 48 px high; Hadir/Tidak hadir show the current state (`aria-pressed`).
- Camera: `public/build/scanner.js` (`SpmbScanner.start/cardCode/hasCamera`), start/stop
  buttons, hidden when the device has no camera. Scans are ignored while a request is in flight;
  the scanner itself ignores a repeat of the same code within 3 s.
- Text field for USB scanners and typed numbers; focused automatically only on devices with a
  fine pointer (so phones do not open the keyboard).
- Confirmation: the result box (green/blue/yellow/red with icon and text, not colour alone),
  `aria-live`, a short vibration on phones, and the row flashes. Undo = "Batalkan" (back to
  "Belum tes").
- Search filters the roster as you type (GET `?q=` without JavaScript).

## Attendance sheet PDF

FPDF, A4 portrait, black on white: school logo and name, "DAFTAR HADIR TES PENEMPATAN",
academic year, session/day/time/room; table no. / registration number / name / L-P /
signature (numbers alternate left and right) / note (Hadir or Tidak hadir once recorded);
the table header repeats on each page; totals (participants, boys, girls), blank lines for
hadir/tidak hadir and the room supervisor's signature; footer with print date, session and
"Halaman x dari y". Printing writes nothing.

## Panels

- `card_check` (`/kartu/<token>`, staff with `tes.kehadiran`): schedule, status badge,
  "Catat Hadir" (POST `catat`, back to the same check page) or "Tercatat hadir pukul …",
  link to the roster.
- `card_lookup` (`/admin/kartu`): day, time, room + location, seat number, status; for
  `tes.kehadiran` also "Catat Hadir" and the roster link.
- `dashboard` (`/admin`, `tes.kehadiran`): today's sessions with present/total bars;
  with `tes.atur` also "Pendaftar belum dijadwalkan" (non-cancelled applicants of the
  academic year without a seat) and a link to Penjadwalan.

## Tests

`tests/database/TesKehadiranServiceTest.php` (service, PDF) and
`tests/feature/TesKehadiranTest.php` (routes, permissions, JSON, panels). The move test runs
the real `Scheduler` when package B is present and checks the "not available" refusal otherwise.
