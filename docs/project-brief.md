# Project Brief

## Source 1 — "Rancangan Website" (SMP NU Al Ma'ruf Kudus, AY 2027/2028)

Received 2026-11-17. A short guide to the system flow for the school committee
and the developer (not a formal proposal). Summary in English:

### 1. End-to-end flow

Online / offline registration → one database with a unique registration number
→ registration card (PDF / print) → placement test scheduling → test attendance
confirmation → re-registration (*daftar ulang*) + uniform administration →
committee dashboard and reports.

### 2. Two entry points, one system

| Online (applicant / parent) | Offline (school officer) |
| --- | --- |
| Opens the website, chooses *Daftar Online* | Applicant comes to school with documents |
| Fills identity, parents, previous school, contacts | Officer interviews and fills the same form |
| Uploads required documents | Officer checks physical documents, may scan them |
| Submits, keeps the number, downloads the card | Officer saves and prints the card |
| Method recorded automatically: ONLINE | Method recorded automatically: OFFLINE |

The sequence number is shared across all applicants (online 0001, next offline
0002). The method is a data column, not a separate database.

### 3. Identity, waves, card

- **Registration number**: e.g. `PMB-2027-0001`; unique, automatic, never changes, never reused.
- **Waves**: 1, 2, 3 by date ranges set by the school; admin can change the dates.
- **Card** contains: number, name, wave, previous school, method, test status/schedule, verification QR.
- Card is used at the placement test and at re-registration / uniform purchase.
- Card can be reprinted without creating new data or a new number.
- If no schedule exists yet the card can still be generated; the latest version shows the schedule once set.

### 4. Placement test

Admin sets day, time, room and quota. The system orders eligible applicants by
registration number (online and offline treated equally), e.g. quota 50/day:
day 1 = 0001–0050, day 2 = 0051–0100, and so on. Real dates follow an admin
calendar (holidays can be excluded). Officers scan the card or search the number
and set status: *Belum Tes / Hadir / Tidak Hadir / Dijadwalkan Ulang*. Schedule
changes must be logged.

### 5. Re-registration and uniform

Applicant brings the card → officer scans QR or searches → system shows data and
test status → officer processes re-registration → status saved with date and
officer name.

- Re-registration status: *Belum daftar ulang / Sedang diproses (optional) / Sudah daftar ulang*
- Uniform status (separate): *Belum diproses / Dalam proses / Sudah ambil seragam*

Kept separate so the committee can see who re-registered but has not finished uniform administration.

### 6. Site structure

| Public website | Committee / admin panel |
| --- | --- |
| Home, profile, requirements, wave schedule | Summary dashboard |
| Online registration | Add offline applicant |
| Status check and data correction | Applicant data + document verification |
| Download / print card | Waves, dates and test quotas |
| Test and re-registration announcements | Test attendance + rescheduling |
| Contact / help | Re-registration + uniform + reports |

### 7. Dashboard

Stats: total applicants, online vs offline, per wave, documents complete/incomplete,
tested/not tested/absent, re-registered or not, uniform status.
Filters and downloads: method, wave, date, previous SD/MI, status; export
Excel/CSV/PDF; list of applicants to contact. Totals must never double count.

### 8. Minimum data and technical controls

| Core data | Safeguards |
| --- | --- |
| Student identity + parents/guardian | Detect potential duplicate NISN/NIK |
| Registration method + wave | Separate access: registration, test, re-registration, admin |
| Documents + verification status | Documents never publicly accessible; HTTPS |
| Test schedule + attendance | Audit log: who changed what and when |
| Re-registration + uniform | Regular backup and restore |
| Officer accounts and configuration | QR uses a secure token, never raw NIK |

### 9. Build order

Must-have first: online/offline registration in one database; upload/verification
and PDF card; unique numbering and waves; test scheduling and attendance;
re-registration, reports and access rights.
Later: automatic WhatsApp notifications, uniform stock and packages, digital
payment (if needed), previous-school / trend analysis, integration with other school systems.

### 10. Decisions pending from the committee

- Exact dates of each wave; whether tests run per wave.
- Required documents and rules for documents submitted later.
- Daily quota, dates, sessions and rooms for tests.
- Whether the card is printable immediately or only after initial verification.
- Re-registration period and completion requirements.
- Whether uniform size/package/transaction details are needed.
- Domain, hosting, technology PIC, backup procedure.

**Goal**: one website that tracks every applicant from registration → card → test →
re-registration, with reports that always distinguish ONLINE and OFFLINE origin.

## Source 2 — Arumi Studios product requirements (2026-11-17)

Additional requirements from the developer: white label, modular, configurable;
roles from super admin and school admin down; PDF and XLSX export/import;
full-width responsive admin dashboard; optional Google Authenticator 2FA for
dashboard users; image resizing; light visitor analytics; modern interactive chat;
friendly breadcrumbs; Indonesian guide pages per role; non-technical language
except for super admin; public SD data for Central Java to normalise school names.
Details in [requirements.md](requirements.md).
