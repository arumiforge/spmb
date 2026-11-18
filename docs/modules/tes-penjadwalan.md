# Tes — Penjadwalan (package B)

Allocation of test seats, moving and removing a seat, the schedule change log and the
applicant's schedule (FR-TEST-02, FR-TEST-04, D-060, D-062). Part of the `Tes` module
([`tes.md`](tes.md)); plan in `docs/phase-3-plan.md` → B.

## Pages

| URL | Permission | Purpose |
| --- | --- | --- |
| `GET /admin/tes/penjadwalan` | `tes.atur` | Counts (waiting per wave, free seats, sessions with seats), form (wave, start date), dry-run preview with `?lihat=1` |
| `POST /admin/tes/penjadwalan` | `tes.atur` | Runs the allocation (`gelombang`, `mulai`) after the browser confirm; flash summary |
| `GET /admin/tes/peserta` | `tes.kehadiran` | Participants of the year incl. "Belum dijadwalkan"; filters `q`, `tanggal`, `sesi`, `status` (`unscheduled` or a test status), `gelombang`; 25 per page; cancelled applicants left out |
| `GET /admin/pendaftar/{id}/tes` | `tes.kehadiran` | Give a first seat or move to another session (staff with `tes.atur` also see past sessions) |
| `POST /admin/pendaftar/{id}/tes` | `tes.kehadiran` | `sesi`, `alasan` (required for a move), `lebih_kuota` (allow a full session) |
| `POST /admin/pendaftar/{id}/tes/hapus` | `tes.atur` | Take the applicant out of the schedule (`alasan` required) |
| `GET /admin/tes/riwayat-jadwal` | `tes.kehadiran` | Change log, newest first; filters `dari`, `sampai`, `jenis`, `q` |

Panels: `applicant_admin` (schedule, status, seat, last 5 changes, button "Beri Jadwal" /
"Pindah Jadwal" for `tes.kehadiran`) and `applicant_public` (day, time, room, location,
status and `Tes.instructions` when `Tes.scheduleVisible`; otherwise `Tes.scheduleLater`;
nothing for cancelled applicants).

## `Modules\Tes\Services\Scheduler`

The only writer of seats. Every change runs in one transaction, locks the rows it reads
(`SELECT … FOR UPDATE` on the target sessions, the applicant and their assignment), adds a
`test_schedule_changes` row and logs one semantic audit event with model callbacks off.

| Method | Notes |
| --- | --- |
| `allocate(array $options, bool $dryRun = false): array` | Options `academic_year`, `wave_id` (null = all), `from_date` (default tomorrow), `staff_id` (audit user). Report `placed`, `unplaced`, `eligible`, `sessions` (`session_id`, `label`, `session_label`, `count`, `first_number`, `last_number`), `unplacedNumbers` |
| `assign(int $applicantId, int $sessionId, int $staffId, ?string $reason = null, bool $allowOverQuota = false): array` | First seat, status `pending`, kind `assigned`. `$allowOverQuota` added for walk-ins (package C) |
| `move(int $applicantId, int $sessionId, int $staffId, string $reason, bool $allowOverQuota = false): array` | Reason ≥ 5 characters; status `rescheduled`; `attended_at`/`recorded_by` cleared; kind `moved` |
| `remove(int $applicantId, int $staffId, string $reason): void` | Row deleted, kind `removed`; refused when the applicant is already marked present |
| `eligibleCounts(?string $year)`, `openSessions(...)`, `choicesFor(...)` | Read helpers for the pages |

`assign()`/`move()` return `TestAssignmentModel::forApplicant()` after the change.
Refusals throw `SchedulerException` with a friendly message (`lang('TesPenjadwalan.err.*')`):
cancelled/deleted applicant, already scheduled / not scheduled, same session, missing or
inactive session, other academic year, other wave, holiday, past session (`test_date` before
today; `move()` allows it for staff with `tes.atur`), full session, missing reason.

### Allocation order (D-060)

1. Eligible: applicants of the year, not deleted, status in `Tes.eligibleStatuses` (never
   `cancelled`), without an assignment row, `document_status = complete` when
   `Tes.requireCompleteDocuments`. Optional wave filter.
2. Sessions: active, not deleted, same year, `test_date >= from_date`, not on a holiday,
   accepting the wave; ordered by date, start time, room `sort_order`, room name, id.
3. Applicants in `applicants.id` order (= registration order, online and offline equal) take
   the first session with a free seat whose wave fits (a wave session takes only its wave;
   applicants without a wave go only to sessions for every wave).
4. Free seats = quota − assignments of non-cancelled, non-deleted applicants; `seat_number`
   continues after the session's highest number (cancelled rows keep theirs).
5. Nobody already scheduled is moved, so re-runs only place new applicants.

Allocated seats have `assigned_by` and `changed_by` null ("Sistem"); the run logs one
`test_allocated` event (subject `test_sessions`, user = staff who ran it). Seat events
(`test_assigned`, `test_moved`, `test_removed`) use subject `applicants` + applicant id,
like the card events (D-042).

## Model additions

`TestScheduleChangeModel::detailed(array $filters)` (joins applicant, from/to session and
room, staff name) and `latestForApplicant(int $id, int $limit = 5)`.

## Demo data

`Database/Seeds/DemoAllocationSeeder` (called by package A's `DemoSeeder`) runs
`allocate()` for the demo applicants and moves one applicant once, so the change log has an
example. Idempotent.

## Guides

`Guide/admin-sekolah/20-penjadwalan.md`, `Guide/panitia-pendaftaran/20-jadwal-tes.md`,
`Guide/calon-murid.md`.

## Tests

`tests/database/TesPenjadwalanSchedulerTest.php` (allocation order, waves, holidays, full
sessions, re-runs, cancelled applicants, eligibility, dry run, logging; assign/move/remove and
refusals) and `tests/feature/TesPenjadwalanTest.php` (permissions of every route, pages,
panels).
