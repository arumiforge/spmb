# Requirements

Status markers: **[confirmed]** agreed with the client · **[proposed]** recommended
default awaiting confirmation (see `interview/`) · **[later]** post-launch.

IDs are stable; reference them in commits, PRs and tests (`FR-REG-03`).

## 1. Roles

| Role | Who | Main capabilities | Language level |
| --- | --- | --- | --- |
| Super Admin | Arumi Studios | Everything: modules on/off, white-label, system settings, backups, logs, users of all roles | Technical allowed |
| Admin Sekolah | School IT/operator | School identity, waves, test schedule, documents list, users (except super admin), all data and reports | Non-technical |
| Kepala Sekolah **[proposed]** | Principal | Read-only dashboard and reports | Non-technical |
| Panitia Pendaftaran | Registration committee | Add offline applicants, edit data, verify documents, print cards | Non-technical |
| Petugas Tes | Test officers | Scan card / search, record attendance, reschedule | Non-technical |
| Petugas Daftar Ulang | Re-registration officers | Process re-registration | Non-technical |
| Petugas Seragam **[proposed]** | Uniform officers | Update uniform status | Non-technical |
| Calon Murid / Orang Tua | Public | Register online, check status, correct data, download card | Non-technical |

Access is permission-based (`Config\AuthGroups`, D-014). Admin Sekolah assigns roles and
extra per-user permissions; changing a role's permission set is a configuration change
(Super Admin only for system permissions). One user may hold several roles.

## 2. Functional requirements

### Registration (REG)
- FR-REG-01 Online registration form: identity, parents/guardian, previous school, contacts, documents. [confirmed]
- FR-REG-02 Offline registration by an officer using the same form; method recorded automatically. [confirmed]
- FR-REG-03 One shared sequence and number format (default `PMB-{YEAR}-{0000}`, configurable); unique, immutable, never reused. [confirmed]
- FR-REG-04 Wave assigned from the registration date; waves and dates configurable. [confirmed]
- FR-REG-05 Duplicate warning on NIK/NISN (and similar name + birth date). [confirmed]
- FR-REG-06 Previous school chosen from a normalised list (public SD/MI data, Central Java first) with a fallback "other school" entry that admins can map later. [confirmed]
- FR-REG-07 Public status check and data correction **[proposed: number + date of birth, no account]**.

### Documents (DOC)
- FR-DOC-01 Required documents list configurable (type, required/optional, max size, formats).
- FR-DOC-02 Images resized/compressed on upload; PDFs size-limited.
- FR-DOC-03 Files stored outside the public web root, served only to authorised users.
- FR-DOC-04 Verification status per document and per applicant (complete / incomplete / to follow).

### Card (CARD)
- FR-CARD-01 PDF card: number, name, wave, previous school, method, test status/schedule, QR.
- FR-CARD-02 QR holds a random token (never NIK/NISN) resolving to a verification page/officer lookup.
- FR-CARD-03 Reprint any time; always shows current schedule; never creates new data.
- FR-CARD-04 Availability rule configurable: immediately or after initial verification (committee decision pending).

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
- FR-WEB-01 Pages: Beranda, Profil, Persyaratan, Jadwal Gelombang, Pengumuman, Kontak/Bantuan — content editable by Admin Sekolah.
- FR-WEB-02 Indonesian, friendly, readable URLs (`/daftar`, `/cek-status`, `/persyaratan`).

### Platform (PLT)
- FR-PLT-01 White label: school name, logo, favicon, colours, contacts, footer, card header from settings.
- FR-PLT-02 Modular: features as modules that can be enabled/disabled.
- FR-PLT-03 Optional TOTP 2FA (Google Authenticator) per dashboard user; Super Admin can require it per role.
- FR-PLT-04 Audit log of data changes and logins.
- FR-PLT-05 Breadcrumbs on every page except home; friendly labels.
- FR-PLT-06 Guide pages per role at `/panduan/<role>` in plain Indonesian.
- FR-PLT-07 Visitor analytics, light: page views, unique visitors/day, top pages, referrers, device type. [proposed: built-in, no third-party script]
- FR-PLT-08 Modern interactive chat. [scope pending — interview round 1]
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
