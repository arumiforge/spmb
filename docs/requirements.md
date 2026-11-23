# Requirements

Status markers: **[confirmed]** agreed with the client · **[proposed]** recommended
default awaiting confirmation (see `interview/`) · **[later]** post-launch.

IDs are stable; reference them in commits, PRs and tests (`FR-REG-03`).

## 1. Roles

| Role | Who | Main capabilities | Language level |
| --- | --- | --- | --- |
| Super Admin | Arumi Studios | Everything: modules on/off, white-label, system settings, backups, logs, users of all roles | Technical allowed |
| Admin Sekolah | School IT/operator | Everything for the school: identity, waves, test schedule, documents list, uniform settings, users (except super admin), all data and reports **[confirmed, D-051]** | Non-technical |
| Panitia | School committee (one role for all committee work) | Offline applicants, data edits, document checks, cards, test attendance and moving a test seat, re-registration, uniform pickup, replies to parents' messages; no settings **[confirmed, D-051, D-063]** | Non-technical |
| Siswa — Calon Murid / Orang Tua ("akun siswa") | Public, used by parents | Register online, check status, correct data, download card, send messages/criticism/suggestions — no password: registration number + birth date or private link **[confirmed, D-008, D-052]** | Non-technical |

Only three roles are used by the school: Admin Sekolah, Panitia and Siswa (D-063); Super Admin is the
developer's technical account. Kepala Sekolah, Petugas Tes, Petugas Daftar Ulang and Petugas Seragam were removed.
Access is permission-based (`Config\AuthGroups`, D-014). Admin Sekolah assigns roles and
extra per-user permissions; changing a role's permission set is a configuration change
(Super Admin only for system permissions). One user may hold several roles.

## 2. Functional requirements

### Registration (REG)
- FR-REG-01 Online registration form: identity, parents/guardian, previous school, contacts, documents. [confirmed] **[built in phase 2]**
- FR-REG-02 Offline registration by an officer using the same form; method recorded automatically. [confirmed] **[built in phase 2]**
- FR-REG-03 One shared sequence and number format (default `PMB-{YEAR}-{0000}`, configurable); unique, immutable, never reused. [confirmed] **[built in phase 2]**
- FR-REG-04 Wave assigned from the registration date; waves and dates configurable. [confirmed] **[built in phase 2]**
- FR-REG-05 Duplicate warning on NIK/NISN (and similar name + birth date). [confirmed] **[built in phase 2]**
- FR-REG-06 Previous school chosen from a normalised list (public SD/MI data, Central Java first) with a fallback "other school" entry that admins can map later. [confirmed] **[built in phase 2; national SD/MI list ships as default data (D-058); CSV/XLSX/JSON import works]**
- FR-REG-07 Public status check and data correction **[proposed: number + date of birth, no account]**. **[built in phase 2: D-008, D-034]**

### Documents (DOC)
- FR-DOC-01 Required documents list configurable (type, required/optional, max size, formats). **[built in phase 2]**
- FR-DOC-02 Images resized/compressed on upload; PDFs size-limited. **[built in phase 2]**
- FR-DOC-03 Files stored outside the public web root, served only to authorised users. **[built in phase 2]**
- FR-DOC-04 Verification status per document and per applicant (complete / incomplete / to follow). **[built in phase 2]**

### Card (CARD)
- FR-CARD-01 PDF card: number, name, wave, previous school, method, test status/schedule, QR. **[built in phase 2]**
- FR-CARD-02 QR holds a random token (never NIK/NISN) resolving to a verification page/officer lookup. **[built in phase 2]**
- FR-CARD-03 Reprint any time; always shows current schedule; never creates new data. **[built in phase 2]**
- FR-CARD-04 Availability rule configurable: immediately or after initial verification (committee decision pending). **[built in phase 2]**

### Placement test (TEST)
- FR-TEST-01 Calendar of test days (holidays excluded), sessions, rooms, quotas. **[built in phase 3: rooms, calendar with holidays, sessions + generator, D-059, D-064]**
- FR-TEST-02 Automatic allocation by registration number order, online and offline equal. **[built in phase 3, D-060]**
- FR-TEST-03 Attendance via QR scan or search: Belum Tes / Hadir / Tidak Hadir / Dijadwalkan Ulang. **[built in phase 3: camera/USB scan, search, card check page, D-061]**
- FR-TEST-04 Every schedule change logged (who, when, from → to, reason). **[built in phase 3: `test_schedule_changes` + Riwayat Jadwal page]**

### Re-registration & uniform (DU, SRG)
- FR-DU-01 Lookup by QR or number shows data and test status; status Belum / Sedang diproses (optional) / Sudah, with date and officer.
- FR-SRG-01 Uniform status separate: Belum diproses / Dalam proses / Sudah ambil seragam.
- FR-SRG-02 Sizes, packages, stock, transactions. [later]

### Dashboard & reports (RPT)
- FR-RPT-01 Stats per section 7 of the brief, never double counting.
- FR-RPT-02 Filters: method, wave, date range, previous school, each status.
- FR-RPT-03 Export XLSX, CSV, PDF of any filtered list; "to contact" list.
- FR-RPT-04 XLSX import (e.g. bulk offline data, reference schools) with row-level validation report.

### Public website (WEB)
- FR-WEB-01 Pages: Beranda, Profil, Persyaratan, Jadwal Gelombang, Pengumuman, Kontak/Bantuan — content editable by Admin Sekolah. **[phase 2: Beranda actions, Persyaratan, Jadwal Gelombang; the rest and editing in phase 5]**
- FR-WEB-03 Tanya Jawab page for parents: questions grouped by topic, edited by Admin Sekolah, answers link to the real pages; starter questions per school in the client pack. [confirmed, D-072] **[built in the platform round: `/tanya-jawab`, Website → Tanya jawab]**
- FR-WEB-02 Indonesian, friendly, readable URLs (`/daftar`, `/cek-status`, `/persyaratan`).

### Platform (PLT)
- FR-PLT-01 White label: school name, logo, favicon, colours, contacts, footer, card header from settings.
- FR-PLT-02 Modular: features as modules that can be enabled/disabled.
- FR-PLT-03 Optional TOTP 2FA (Google Authenticator) per dashboard user; Super Admin can require it per role.
- FR-PLT-04 Audit log of data changes and logins.
- FR-PLT-05 Breadcrumbs on every page except home; friendly labels.
- FR-PLT-06 Guide pages per role at `/panduan/<role>` in plain Indonesian.
- FR-PLT-07 Visitor analytics, compact: page views, unique visitors/day, top pages (referrers and device type only as simple totals). [confirmed: built-in, no third-party script, not detailed — D-053]
- FR-PLT-08 Messages, not live chat: applicants (through their status page) send messages, criticism and suggestions; the committee reads and replies in an inbox; replies appear on the applicant's page. [confirmed, D-052]
- FR-PLT-09 Notifications via WhatsApp. [later]
- FR-PLT-10 Digital payment. [later]

## 3. Non-functional requirements

- NFR-01 Responsive 360 px → 1920 px; admin dashboard full width.
- NFR-02 HTTPS only (Cloudflare Tunnel); the apps listen on `127.0.0.1` only. The one open port is SSH with key-only login, kept as the last way in because the VPS has no provider console (D-077). **[built: `deploy/server/setup.sh`]**
- NFR-03 Daily automated database + uploads backup with tested restore procedure. **[built: daily `spmb backup` timer (14 days kept), backup before every deploy, `spmb restore`; off-site copy and restore drill at go-live]**
- NFR-04 Non-technical Indonesian language for everyone except Super Admin.
- NFR-05 Personal data handled per UU No. 27/2022 (PDP): minimum data, access control, retention policy.
- NFR-06 Peak load: registration opening day — target 200 concurrent users on a small VPS.
- NFR-07 Accessibility: WCAG 2.1 AA for contrast, labels, keyboard use; motion off under `prefers-reduced-motion` (D-070).
- NFR-08 Runtimes: PHP 8.5.13+, Node.js 24+, MySQL 8.4 (D-069); staging and production as separate Docker stacks on one server, production only runs images tested on staging (D-066…D-068).
