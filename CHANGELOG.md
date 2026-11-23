# Changelog

All notable changes are documented here ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/)).

## [Unreleased]

### Added
- Public polish round (D-090…D-095): Codex paintings 021–026 installed beside the headings of Persyaratan, Jadwal,
  Tanya Jawab, Cek Status, the card page and "Cara mendaftar"; generic link-preview painting (035) used when a school
  has no share image; "Cara mendaftar" cards with an art background and slots for step scenes (briefs 046–048
  rewritten, new brief 072); a map preview next to the address even without a pasted Google Maps embed; HTML
  responses minified on the way out (`minifyhtml` filter); "Lihat halaman depan" in the dashboard; the dashboard
  bottom bar ends with Pengaturan.
- UI round (D-080…D-088, `docs/ui-round-plan.md`): staff sign in with email **or username**; mobile bottom bar
  with five items on every page (public: Daftar raised in the middle; dashboard: raised "Cari pendaftar" quick
  search); menu drawer from the right; small top bar with the school's social media and the staff sign-in link;
  school slogan, Google Maps embed and social links in Identitas Sekolah; home page with an automatic hero
  slideshow (default Codex paintings, replaceable by up to 6 own pictures, soft edges), art buttons, "Cara
  mendaftar" and information cards with painted icons in 3D (three.js), and a location/contact section with the
  map; Indonesian date picker for every date field; "Lihat selengkapnya" folds and a contents list that
  highlights the section being read; "Developed by : Mr. F" in the footers; one painting slot per empty condition.
  Topic contents on Tanya Jawab stick under the header and highlight the topic in view. Codex briefs 036–071
  (painted home icons, step pictures, art-button kit, two hero slides, one painting per empty state; new art reads
  on light and dark pages without `-dark` copies). SVG originals live in `resources/img/`; `npm run build` writes
  minified copies to `public/assets/img/` (D-086). Dashboard: tiles 2×2 on phones, cards in 2–3 columns on wide
  screens, filters fold behind "Saring" on phones.
- Off-site backups (D-079): `spmb offsite setup|status|get`; daily and manual backups also upload an encrypted copy
  (restic) to a private Cloudflare R2 bucket; `setup.sh` installs restic; `spmb status` shows the last upload.
- `spmb domain <env> <domain>` changes an environment's domain on the server (stack.env, `app.baseURL`), restarts the
  app, checks it and prints the Cloudflare steps.
- Codex visual kit installed (briefs 015–018): tileable patterns, section dividers, the special "Daftar Sekarang"
  button and loaders (`resources/css/visual/`, `public/assets/img/{pola,pemisah,tombol,memuat}/`). Used on the home
  hero (pattern, curved divider, large button) and the registration buttons on Persyaratan, Jadwal and Tanya Jawab.
- Example settings files for the server stacks: `deploy/env/production/` and `deploy/env/staging/`
  (`stack.env.example`, `app.env.example`), matching what `spmb init` writes, with placeholders only.
- Platform round (D-066…D-075): writing standard skill `bahasa-indonesia-plain`; visual and chart rules in `spmb-ui`;
  Claude cloud environment setup kept in the repository (`.claude/cloud/setup.sh`, `docs/cloud-environment.md`);
  `AGENTS.md` for Codex and other agents.
- Dashboard chart of registration methods; charts keep their text readable on phones; gentle entrance and hover motion
  that respects "reduce motion" (D-070).
- Tanya Jawab (D-072): public page `/tanya-jawab` (topics, accordion, links to the real pages; shown in the menu only when
  something is published), admin screen under Website (`situs.kelola`, audited), starter questions for SMP NU Al Ma'ruf
  Kudus in the client pack loaded by `spmb:branding` (29 questions; 4 stay hidden until the school fills them in).
- Docker images (PHP 8.5 FPM + Nginx), compose stack for staging and production on one server, one-time server setup
  script (`deploy/server/setup.sh`: Docker, cloudflared, Cockpit, firewall with lock-out guards) and the `spmb` server
  CLI (init, deploy, rollback, status, logs, backup, restore, spark) with daily backups and automatic rollback of a
  failed deploy (D-066…D-068, D-076). Laragon setup for Windows in `docs/development.md`.
- Visual asset pipeline moved to Codex: identity style guide (santri, Islamic, NU, Indonesian, Bhinneka), strict respect
  rules for religious elements, 21 new briefs (patterns, dividers, special "Daftar sekarang" button, loader, page and
  state paintings, default share image) and an asset checker (`ai/tools/check_assets.py`).
- Tes, rooms and schedule: test rooms, test calendar with holidays, sessions (add, edit, deactivate, delete), creating many
  sessions at once with a preview, and Pengaturan → Tes (FR-TEST-01).
- Tes, allocation: automatic test seating with preview (registration order, waves, holidays), participant list,
  giving/moving/removing a test seat with a reason, schedule change log, and the test schedule on the applicant's page
  and the staff detail page (FR-TEST-02, FR-TEST-04).
- Tes, attendance: day overview, session roster with Hadir / Tidak hadir / undo, camera and USB card scanning, moving a
  walk-in into the session, marking the rest absent after the session, printable attendance sheet (PDF), test schedule
  and "Catat Hadir" on the card check page and card lookup, "Tes hari ini" on the dashboard (FR-TEST-03).
- Phase 3 foundation: module Tes (rooms, test holidays, sessions, assignments, schedule change log), settings `Tes`,
  test status labels, schedule on the registration card, panel slots `card_check`, `card_lookup` and `dashboard`,
  guide sections from `Guide/<audience>/*.md`, camera QR scanner bundle (`qr-scanner`, MIT), `docs/phase-3-plan.md`
  (D-059…D-062).
- Previous schools: the national SD/MI list (177,422 schools, 38 provinces) ships as default data and is loaded by
  `spmb:install` and on deploy (`php spark referensi:import --bawaan`, skipped once loaded) (D-058).
- Phase 2 foundation: modules Referensi, Pendaftaran, Berkas, Kartu and Situs (registry entries, menus, public
  navigation, audit labels); migrations for `ref_sekolah`, `waves`, `registration_counters`, `applicants`,
  `document_types`, `applicant_documents`, `card_tokens` with audited models; settings defaults
  `Pendaftaran`, `Berkas`, `Kartu`, `Referensi`; permission `referensi.kelola`; applicant session grant
  (`service('applicantAccess')`); module `requires`, panels (`module_panels()`), `Config/PublicMenu.php`,
  `Config/Audit.php` and installer seeders (D-033); `docs/coordination.md` and `docs/phase-2-plan.md`.
- Dependencies `setasign/fpdf` and `phpoffice/phpspreadsheet` (MIT, D-030).
- Referensi: previous-school list for admins (search, filters, add, edit, deactivate), CSV/XLSX import with a row report,
  mapping of schools typed by applicants, public school search `/sekolah-asal/cari` and the school picker,
  `php spark referensi:import` (Dapodik/file), demo schools (FR-REG-06).
- Berkas: required documents settings (`/admin/pengaturan/berkas`), document upload by applicants and staff, document check
  queue and review screen (`/admin/berkas`), photo resizing with EXIF removal, applicant document status (FR-DOC-01…04).
- Kartu: registration card PDF with random QR token, card check page `/kartu/<token>`, applicant card download, staff card
  lookup and card settings (FR-CARD-01…04).
- Pendaftaran: registration waves and settings (academic year, number format, required/hidden fields); online form `/daftar`
  with success page, private link and duplicate flagging; public status check `/cek-status`, applicant page, contact/address
  correction and private link `/tautan/<token>`; staff applicant list, offline registration, detail, edit, status change,
  duplicate review, new private link and delete (FR-REG-01…07).
- School answers: quota behaviour per wave (information only / close online / separate online limit), optional random
  code at the end of registration numbers (`{ACAK:n}`, max 5), KK number and parent/guardian occupation and income
  dropdowns (editable lists), field mode "Hanya diisi petugas" (D-047…D-049).
- Brand images: landscape logo (light/dark), app icon, link-preview image, parent organisation name and logo in the
  footer, Open Graph tags; `php spark spmb:branding` loads a school's branding pack; branding pack for SMP NU Al Ma'ruf
  Kudus (green `#005c2f`, LP Ma'arif NU); optional green illustration accent; `deploy/provision.sh` and a go-live
  checklist (D-054…D-056).
- Previous schools: `php spark referensi:import --file` reads the national SD/MI JSON list (array, NDJSON or .gz),
  matches Indonesian/English keys and takes the level from the school name when missing (D-057).
- Dark mode: lighter accent text tints so dark brand colours stay readable.
- Panitia Pendaftaran may also record test attendance, re-registration and uniform pickup (D-051).
- Dashboard shows real registration counts (total, today, per wave against quota, online/offline, documents complete).
- Situs: public pages `/persyaratan` and `/jadwal`; home page shows "Daftar Sekarang" while registration is open (FR-WEB-01, FR-WEB-02).
- Phase 0 foundation: project rules (`CLAUDE.md`), repo agent skills (`.claude/skills/`),
  staged documentation (`docs/`), interview round 1, AI image prompt workflow (`ai/`).
- `docs/progress.md` and the `spmb-session` skill for handoff between sessions and Claude accounts.
- GitHub Actions: CI (lint, coding standard, PHPUnit on MySQL 8.0), commit policy, manual release deploy over Cloudflare Access SSH.
- `.githooks/commit-msg` enforcing Arumi Studios authorship and no AI attribution.
- `Modules\` namespace for feature modules (`app/Modules`).

- Auth with CodeIgniter Shield: seven staff roles with Indonesian titles, permission matrix, routes `/masuk`, `/keluar`, `/verifikasi`; self-registration and magic links disabled.
- Migrations: profile fields on `users`, `audit_logs`, `user_two_factor` (plus Shield and Settings tables).
- Audit trail service and `Auditable` model trait (old/new values, redaction of secrets).
- TOTP two-factor service with encrypted secret, replay protection and single-use recovery codes.
- `php spark spmb:install` installer (idempotent) and demo account seeder for development.
- `School` config for white-label identity defaults; protected `/admin` dashboard placeholder.
- Tests: roles/permissions matrix, audit trail, 2FA, demo seeder, auth routes (29 tests on MySQL).
- Frontend build: Tailwind CSS v4, Flowbite, ApexCharts (`package.json`, `resources/`, output `public/build/`);
  primary colour scale derived from the school's colour setting.
- Layouts `admin` (full width, collapsible sidebar, mobile drawer, user menu, breadcrumbs, toasts),
  `auth` and `public`; partials for breadcrumbs, toasts, empty state, form field, status badge, pagination; dark mode.
- Indonesian sign-in screen and two-step verification step (`/verifikasi`) with Google Authenticator codes or recovery codes.
- Profile pages: change password, set up / turn off two-step verification with QR code, recovery codes shown once.
- School identity settings (`/admin/pengaturan/sekolah`) with logo and favicon upload; security policy
  (`/admin/pengaturan/keamanan`) to require two-step verification per role; permission `sistem.keamanan`.
- User management (`/admin/pengguna`): search, filters, create with temporary password, edit roles,
  deactivate/activate, reset password, reset two-step verification.
- Module registry (enable/disable via settings) and sidebar menu built from module menus and permissions.
- Guide pages `/panduan` and `/panduan/<peran>` assembled from module `Guide/` Markdown files.
- Public home page placeholder; friendly "no access" page.
- Tests for two-step flow, user management, settings, guides and UI helpers (71 tests); phase 1 finish brings the suite to 95 tests.

- Fitur page (`/admin/pengaturan/fitur`, `sistem.modul`) to switch feature modules on/off, audited; core modules always on; module `Config/Module.php` metadata (D-025).
- Audit log viewer Riwayat Perubahan (`/admin/riwayat`, `audit.view`): filters by staff, activity, data type, record and date range in the query string, pagination, detail page with readable before/after values, stacked cards on mobile.
- Friendly Indonesian 404 and 500 pages in the public layout, admin variant for signed-in staff, self-contained fallback (D-026).
- Guide sections for Fitur (Super Admin) and Riwayat Perubahan (Admin Sekolah); icons `grid`, `clock`, `compass`, `arrow-right`.
- Illustration briefs 001–012 rewritten in detail for a painted storybook style with a recurring cast; `ai/CHATGPT.md` lets ChatGPT generate the queue from the repository (D-027) and deliver it by pull request into `ai/generated/` for Claude to install (D-028); originals 001–012 delivered (PR #7).
- Painted illustrations installed (WebP in `public/assets/img/`, dark variants for dark mode) on the home page, sign-in, empty states, 404/500 pages and guide pages; `illustration()` view helper.
- `THIRD_PARTY_NOTICES.md` listing third-party licences.

### Changed
- Default tagline "Sistem Penerimaan Murid Baru (SPMB)" (migration updates the old default); the first client's
  branding pack and share image follow. Footer links: only Cek Status, Tanya Jawab and Panduan; the parent
  organisation sits on the right (desktop) or last above the copyright bar (phones). The green top bar is hidden on
  phones (its links are in the drawer). Phones show the hero picture before the title. Public empty states show an
  icon that fits the page instead of the same painting. Side-by-side form fields line up when a label wraps.
- Codex paintings 019, 020 and 027 repainted for D-089 (#49) and installed; 019 is now the first home hero slide.
- Dashboard pages use the full width (forms in two card columns from `xl`); breadcrumbs without the home icon;
  no white plate behind pictures in dark mode (D-083); brand images are cached for a year with an ETag, so the
  logo no longer blinks on reload; the cloud setup script installs PHP from packages.sury.org (the ondrej PPA
  is frozen and failed with 503).
- Pages start in the light theme whatever the device theme; dark mode only after the theme toggle (D-078).
- README, roadmap, architecture, requirements, module index, third-party notices and agent guide updated to the
  current state (Docker deployment, PHP 8.5 / MySQL 8.4, Lucide icons, Tanya Jawab, Codex, D-066…D-077).
- The direct SSH port stays open with key-only login as the last way in, because the VPS has no provider console
  (D-077); `setup.sh` keeps it open for good and no longer tells the admin to close it.
- Minimum runtimes: PHP 8.5.13 (Composer platform too), Node.js 24, MySQL 8.4 in CI (D-069); 424 tests green on PHP 8.5.13.
- Icons are Lucide line icons without coloured backgrounds; eyebrow labels and accent borders removed (D-070).
  Icons are generated with `npm run icons` from `resources/icons.json` into `app/Helpers/lucide_icons.php`.
- `composer test` no longer stops at Composer's 300-second limit (the suite takes about six minutes).
- Only three roles: Admin Sekolah, Panitia (one committee role) and Siswa (applicant access); Kepala Sekolah, Petugas Tes,
  Petugas Daftar Ulang and Petugas Seragam removed and their accounts moved to Panitia; guide `/panduan/panitia` (D-063).
- Project licence is now proprietary (© Arumi Studios), replacing the inherited CodeIgniter MIT notice (D-017).
- CSRF protection and secure headers enabled for every request; sign-in and verification are rate limited.
- `composer test` runs PHPUnit without coverage (same as CI).
- Locale `id`, time zone `Asia/Jakarta`, Indonesian framework messages (`codeigniter4/translations`); URLs without `index.php`.
- Switched from the CodeIgniter framework distribution to the appstarter layout; framework 4.7 installed via Composer.

### Removed
- GitHub Actions SSH deploy workflow, `deploy/provision.sh` and `deploy/activate.sh` (replaced by the server pulling and
  building, D-068).

### Fixed
- `deploy/server/setup.sh` waits for the automatic updates a fresh Ubuntu server runs after its first boot instead
  of failing on the dpkg lock.
- Public menu links were duplicated because `Situs/Config/PublicMenu.php` overwrote the registry's items.
- First client's domain corrected to `spmbsmpnualmarufkudus.web.id` (docs, branding pack, share image) (D-065).
- `deploy/provision.sh` no longer locks the admin out: the SSH port (also a custom one) stays open until the tunnel
  runs, and password login is switched off (via `sshd_config.d/00-spmb.conf`, which also works on cloud images) only
  when an admin SSH key exists (D-065).
