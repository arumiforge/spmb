# Module: Tes (placement test)

Placement test for applicants (FR-TEST-01…04): rooms, test calendar with holidays,
sessions with quotas, automatic allocation by registration order, attendance by QR
scan or search, and a log of every schedule change. Built in phase 3
(`docs/phase-3-plan.md`, decisions D-059…D-062). Requires Pendaftaran.

Package pages: [`tes-jadwal.md`](tes-jadwal.md) (rooms, calendar, sessions, settings),
[`tes-penjadwalan.md`](tes-penjadwalan.md) (allocation, moves, change log, applicant
schedule), [`tes-kehadiran.md`](tes-kehadiran.md) (attendance, scanning, attendance sheet).

## Tables

| Table | Purpose |
| --- | --- |
| `test_rooms` | Rooms with capacity and location |
| `test_holidays` | Days without tests |
| `test_sessions` | Room × date × time with a quota; optional wave |
| `test_assignments` | One row per scheduled applicant: session, seat number, attendance status |
| `test_schedule_changes` | Insert-only log: from → to session, kind, reason, staff |

Attendance status (`test_assignments.status`, labels `lang('Status.test.*')`):
`pending` Belum tes · `present` Hadir · `absent` Tidak hadir · `rescheduled` Dijadwalkan ulang.
No row = "Belum dijadwalkan".

## Interface for other modules

- `Modules\Tes\Libraries\TestSchedule`: `forApplicant(int $applicantId): ?array` (day, time, room,
  location, status, one-line label), `isVisibleToApplicants()`, `cardLine(int $applicantId)` — the
  card's "Jadwal Tes" line ("Belum dijadwalkan" while hidden or not scheduled).
- Panel slots filled by Tes: `applicant_admin`, `applicant_public`, `card_check`, `card_lookup`, `dashboard`.

## Permissions

`tes.atur` — rooms, calendar, sessions, allocation, settings (Super Admin, Admin Sekolah).
`tes.kehadiran` — attendance, participant list, moving an applicant to another session
(also Panitia Pendaftaran and Petugas Tes, D-051).

## Settings (`app/Config/Tes.php`, Pengaturan → Tes)

| Key | Default | Meaning |
| --- | --- | --- |
| `scheduleVisible` | true | Applicants see their schedule (page and card) |
| `eligibleStatuses` | registered, verified | Applicant statuses that get a seat automatically |
| `requireCompleteDocuments` | false | Only applicants with complete documents get a seat automatically |
| `instructions` | "Datang 30 menit …" | Shown to applicants with their schedule |
| `defaultDurationMinutes` | 120 | Session length suggested by the session generator |
