# Requirements

Status markers: **[confirmed]** agreed with the client · **[proposed]** recommended
default awaiting confirmation (see `interview/`) · **[later]** post-launch.

IDs are stable; reference them in commits, PRs and tests (`FR-REG-03`).

## 1. Roles

| Role | Who | Main capabilities | Language level |
| --- | --- | --- | --- |
| Super Admin | Arumi Studios | Everything: modules on/off, white-label, system settings, backups, logs, users of all roles | Technical allowed |
| Admin Sekolah | School IT/operator | Everything for the school: identity, waves, test schedule, documents list, uniform settings, users (except super admin), all data and reports **[confirmed, D-051]** | Non-technical |
| Kepala Sekolah **[optional, unused by default]** | Principal | Read-only dashboard and reports | Non-technical |
| Panitia Pendaftaran | Registration committee | Registration work only: offline applicants, data edits, document checks, cards, test attendance, re-registration, uniform pickup; no settings **[confirmed, D-051]** | Non-technical |
| Petugas Tes **[optional]** | Test officers | Scan card / search, record attendance, reschedule | Non-technical |
| Petugas Daftar Ulang **[optional]** | Re-registration officers | Process re-registration | Non-technical |
| Petugas Seragam **[optional]** | Uniform officers | Update uniform status | Non-technical |
| Calon Murid / Orang Tua ("akun siswa") | Public, used by parents | Register online, check status, correct data, download card, send messages/criticism/suggestions — no password: registration number + birth date or private link **[confirmed, D-008, D-052]** | Non-technical |

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
- FR-TEST-01 Calendar of test days (holidays excluded), sessions, rooms, quotas.
- FR-TEST-02 Automatic allocation by registration number order, online and offline equal.
- FR-TEST-03 Attendance via QR scan or search: Belum Tes / Hadir / Tidak Hadir / Dijadwalkan Ulang.
- FR-TEST-04 Every schedule change logged (who, when, from → to, reason).

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
- NFR-02 HTTPS only (Cloudflare), no inbound ports on the server (Cloudflare Tunnel).
- NFR-03 Daily automated database + uploads backup with tested restore procedure.
- NFR-04 Non-technical Indonesian language for everyone except Super Admin.
- NFR-05 Personal data handled per UU No. 27/2022 (PDP): minimum data, access control, retention policy.
- NFR-06 Peak load: registration opening day — target 200 concurrent users on a small VPS.
- NFR-07 Accessibility: WCAG 2.1 AA for contrast, labels, keyboard use.
