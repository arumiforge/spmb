# Tes — Jadwal (rooms, calendar, sessions, settings)

Work package A of phase 3 (`docs/phase-3-plan.md`), requirement FR-TEST-01, decision D-059.
Module overview: [`tes.md`](tes.md). Everything here needs `tes.atur` (Super Admin, Admin Sekolah).

## Pages

| URL | Controller | What it does |
| --- | --- | --- |
| `/admin/tes/ruang` (+ `/tambah`, `/{id}/ubah`, POST `/{id}/nonaktifkan`, `/{id}/aktifkan`, `/{id}/hapus`) | `Ruang` | Rooms: name (unique, case-insensitive), capacity 1…999, location, order, active. Delete is a soft delete and only when no session (not deleted) uses the room. |
| `/admin/tes/kalender?bulan=YYYY-MM` | `Kalender` | Month grid (Monday first) on ≥ md, list of days on phones: sessions, seats filled/quota, holidays, "+" to add a session on a date. Holiday form and list below. |
| POST `/admin/tes/kalender/libur`, `/libur/{id}/hapus` | `Kalender` | Add (date + note) or remove a holiday. Refused when the date already is a holiday or has sessions. |
| `/admin/tes/sesi` | `Sesi` | Sessions grouped by date with counts from `TestSessionModel::withStats()`. Filters: `tanggal`, `gelombang` (id or `none`), `ruang`, `status` (1/0), `lewat=1` (by default only sessions from today on). |
| `/admin/tes/sesi/tambah`, `/{id}/ubah`, POST `/{id}/nonaktifkan`, `/aktifkan`, `/hapus` | `Sesi` | One session. Empty quota = room capacity. Changing date, time or room of a session with participants first shows a confirmation (flash `confirmChange`); participants keep their seat. Delete is soft and only when no assignment row exists at all (cancelled applicants included), so no seat ever points at a deleted session. |
| `/admin/tes/sesi/buat-banyak`, POST `/buat-banyak/pratinjau`, POST `/buat-banyak` | `Sesi` | Session generator: form → preview → create. |
| `/admin/pengaturan/tes` | `Pengaturan` | `Tes.scheduleVisible`, `eligibleStatuses` (every applicant status except cancelled, at least one), `requireCompleteDocuments`, `instructions` (≤ 500), `defaultDurationMinutes` (30…480). One `settings_updated` audit event with old/new values. |

## Session rules — `TestSessionModel::validateSession(array $data, ?array $existing): array`

Returns friendly messages keyed by field (empty = valid): valid date and times, end after
start, quota 1…999 (`QUOTA_MAX`) and not below `seatsTaken()` when editing, room exists and is
active (an inactive room may stay on a session that already uses it), wave of the same academic
year, date not a holiday, and no overlap (`starts_at < other.ends_at AND ends_at > other.starts_at`)
with another session (active or not) in the same room on the same date. Back-to-back sessions are fine.

Other methods added by package A: `TestSessionModel::seatsTaken()`, `assignmentCount()`,
`overlapping()`, `countOnDate()`, static `time()`, `isTime()`, `isDate()`;
`TestRoomModel::withSessionCounts()`, `sessionCount()`, `findByName()`;
`TestHolidayModel::upcoming()`.

## Session generator — `Modules\Tes\Libraries\SessionGenerator`

- `preview(array $input): array` — input `date_from`, `date_to` (≤ 366 days), `weekdays`
  (ISO 1…7, default Mon–Sat), `slots` (`start`, `end`; empty end = start +
  `Tes.defaultDurationMinutes`; at most 8, no overlap between slots), `rooms` (active),
  `wave_id`, `label` (pattern, `{n}` = slot number of the day by start time; default "Sesi {n}").
  Returns `errors`, normalised `input`, `sessions` (rows + `room_name`), `days`, `count`,
  `seats`, `skippedHolidays`, `skippedOverlaps`. At most 1000 sessions per run.
- `create(array $input, int $staffId): int` — locks the chosen rooms (`SELECT … FOR UPDATE`),
  re-runs the preview inside one transaction, inserts with a batch (model callbacks off, D-042)
  and logs one `sessions_generated` audit event (count, dates, rooms, slots, wave). Throws
  `InvalidArgumentException` with a friendly message when the input is invalid or nothing
  would be created. Running it twice creates nothing new (every slot is taken).

## Demo data — `Database/Seeds/DemoSeeder.php`

Four rooms (Ruang 1, Ruang 2, Lab Komputer, Aula), two holidays just after the test days, and
2 slots × 4 rooms on the six Monday–Saturday dates starting 30 days from today (48 sessions,
made with `SessionGenerator`). Idempotent. Ends with `DemoAllocationSeeder` (package B) when it exists.

## Tests

`tests/feature/TesJadwal{Ruang,Sesi,Kalender,Pengaturan}Test.php`,
`tests/database/TesJadwal{Generator,DemoSeeder}Test.php`, helpers in
`tests/_support/Tes/TesJadwalHelpers.php`.
