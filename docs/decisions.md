# Decision Log

Append only. Supersede rather than edit. Format: see `.claude/skills/spmb-docs`.

## D-001 — Core stack (2026-11-17)
Status: accepted
Context: Defined by Arumi Studios.
Decision: CodeIgniter 4, MySQL, Nginx, Ubuntu Server 24.04, Cloudflare Tunnel, SSH, Tailwind + Flowbite.
Consequences: PHP 8.3 (Ubuntu 24.04 default) is the target runtime; CI runs on 8.3.

## D-002 — Appstarter layout, framework via Composer (2026-11-17)
Status: accepted
Context: The repo started as the CI4 framework distribution with `system/` committed.
Decision: Use the appstarter layout; `codeigniter4/framework` is a Composer dependency in `vendor/`.
Consequences: Framework upgrades via `composer update`; `system/` removed from git.

## D-003 — Sole contributor, no AI attribution (2026-11-17)
Status: accepted
Context: Arumi Studios must be the only contributor on GitHub.
Decision: All commits authored by `Arumi Studios <arumiforge@gmail.com>`; no co-author or AI trailers in commits or PRs; enforced by `.githooks/commit-msg` and the `Commit policy` workflow. No `dependabot.yml` version-update PRs (they would add a bot contributor); enable Dependabot security alerts only.
Consequences: Dependency updates are done manually in regular maintenance commits.

## D-004 — One installation per school (2026-11-17)
Status: proposed (interview round 1, Q1)
Context: The product is white label and may be sold to other schools.
Decision: Single-tenant: each school gets its own deployment and database; branding and rules come from settings.
Consequences: Simpler security and data isolation (important for student personal data); updates rolled out per deployment.

## D-005 — Modular structure (2026-11-17)
Status: accepted
Decision: Features live in `app/Modules/<Name>` under namespace `Modules\`, with routes, permissions, menu, migrations, language files and guides per module; modules can be disabled.

## D-006 — Authentication and 2FA (2026-11-17)
Status: accepted (implemented in phase 1: Shield 1.4, TOTP service; login screen wiring pending)
Decision: CodeIgniter Shield for sessions, groups and permissions; custom TOTP action using `pragmarx/google2fa`; 2FA optional per user, enforceable per role by Super Admin; recovery codes.

## D-007 — Documents, export and QR libraries (2026-11-17)
Status: proposed
Decision: Dompdf (PDF), PhpSpreadsheet (XLSX import/export, CSV), chillerlan/php-qrcode (QR), CI4 Image + GD for resize/WebP.

## D-008 — Applicants have no account (2026-11-17)
Status: proposed (interview round 1, Q3)
Decision: Applicants check status, correct data and reprint the card with registration number + date of birth (rate-limited), plus a private link sent after registration.
Consequences: No password resets for parents; lower support load.

## D-009 — Light visitor analytics built in (2026-11-17)
Status: proposed (interview round 1, Q5)
Decision: Own `Analitik` module: page views, daily unique visitors (salted daily hash, no raw IP stored), top pages, referrers, device type. No third-party tracking script.

## D-010 — UI kit (2026-11-17)
Status: accepted
Decision: Tailwind CSS v4 + open-source Flowbite components; licensed Flowbite Pro templates (in `flowbite/`) used as markup reference. The repository stays private while it contains Pro source.

## D-011 — Release-based deploy through Cloudflare Access (2026-11-17)
Status: accepted
Decision: GitHub Actions builds a release, uploads it over SSH via `cloudflared access ssh` with a service token, and `deploy/activate.sh` links shared `.env`/`writable`, migrates and switches the `current` symlink. Manual trigger only.
Consequences: Server needs no GitHub access and no open inbound ports; rollback = repoint `current`.

## D-012 — Previous-school normalisation (2026-11-17)
Status: proposed
Decision: `ref_sekolah` table seeded from public Dapodik data at https://dapo.kemendikdasmen.go.id/ (SD, Central Java) or https://sekolah.data.kemendikdasmen.go.id/ (alternative source) and an XLSX import for MI and out-of-province schools; free-text fallback mapped later by admins.

## D-013 — Indonesian locale and staff-only accounts (2026-11-17)
Status: accepted
Decision: App locale `id`, time zone `Asia/Jakarta`, `codeigniter4/translations` for framework messages. Shield routes moved to `/masuk`, `/keluar`, `/verifikasi` (route names unchanged). Self-registration and magic-link login are disabled; staff accounts are created by an admin or `spmb:install`.
Consequences: Password reset for staff is done by an admin until email delivery is configured (magic link can be enabled then).

## D-014 — Roles are configuration, assignments are data (2026-11-17)
Status: proposed
Context: FR asks that Admin Sekolah can edit role permissions. Shield defines groups and the permission matrix in `Config\AuthGroups`; user ↔ role assignments and per-user extra permissions are stored in the database.
Decision: For launch, roles and their permissions are fixed in config (reviewed with the school); Admin Sekolah assigns roles and can grant individual extra permissions per user. A database-backed role editor is deferred.
Consequences: Changing what a role can do requires a release; adding a role is a small code change with tests (`AuthGroupsTest`).

## D-015 — Audit trail through model callbacks (2026-11-17)
Status: accepted
Decision: `audit_logs` table; models opt in with the `Auditable` trait (insert/update/delete with only changed columns as old/new JSON); explicit events via `service('audit')->log()`. Passwords, secrets and tokens are redacted.
Consequences: Writes that bypass models (raw query builder) are not audited — modules must write applicant data through models.

## D-016 — Two-factor storage (2026-11-17)
Status: accepted
Decision: `user_two_factor` table: TOTP secret encrypted with the app key, 8 single-use recovery codes stored as password hashes, last used time step stored to block code replay. Codes accepted within ±1 time step (30 s).
Consequences: Rotating `encryption.key` invalidates all 2FA setups — document in the key rotation procedure.

## D-017 — Proprietary licence for the repository (2026-11-17)
Status: accepted
Context: SPMB is a developer asset of Arumi Studios, sold/licensed to schools as a white-label product and worked on with outside collaborators. The root `LICENSE` was CodeIgniter's MIT notice inherited from the starter.
Decision: Root `LICENSE` is a proprietary "all rights reserved" licence (`LicenseRef-ArumiStudios-Proprietary`): use only under a written agreement with Arumi Studios; collaborators may use the code only for work for Arumi Studios and must keep it confidential; customer data belongs to the school; Indonesian law. `composer.json` uses `proprietary`, `package.json` uses `UNLICENSED` with `private: true`. Third-party licences are listed in `THIRD_PARTY_NOTICES.md`, including the MIT notice for files from the CodeIgniter starter.
Consequences: Every school deployment needs a signed licence/service agreement; collaborators need a written agreement covering confidentiality and ownership of their work (have a lawyer review both templates). Proof of the Flowbite Pro licence is kept by Arumi Studios outside the repository. ApexCharts v4 licence terms must be checked before launch.

## D-018 — Flowbite Pro as markup reference (2026-11-17)
Status: accepted (refines D-010)
Context: The user chose Flowbite Pro for the UI. The licensed templates (admin dashboard v2.2.0, landing pages v1.1.0) are Hugo sources in `flowbite/`.
Decision: Use them only as a markup reference: copy and adapt the minimum markup into `app/Views`, never publish Pro source or assets, never modify `flowbite/`. The repository stays private. Runtime uses only the open-source `flowbite` npm package. Tailwind source detection is disabled (`source(none)`) so `flowbite/` is never compiled into our CSS.
Consequences: Making the repository public requires removing `flowbite/` first (and from history).

## D-019 — Frontend build and white-label colours (2026-11-17)
Status: accepted
Decision: `@tailwindcss/cli` for CSS and esbuild for JS (`app.js` everywhere, `charts.js` with ApexCharts only on chart pages), output in git-ignored `public/build/`. The primary colour scale is derived in the browser from one `--brand` variable (`color-mix()` in `@theme`), set from `setting('School.primaryColor')`.
Consequences: No per-school CSS build; requires browsers with `color-mix()` (all evergreen browsers since 2023, same baseline as Tailwind v4).

## D-020 — Module registry, menus and guides (2026-11-17)
Status: accepted
Decision: Modules are discovered from `app/Modules/*`; enabled unless listed in `setting('Features.disabled')`; Pengaturan, Pengguna, Profil and Panduan are core. Each module may provide `Config/Routes.php`, `Config/Menu.php`, `Language/id/<Name>.php` and `Guide/<peran>.md` (`semua-petugas.md` applies to every staff role). Permission keys stay in `Config\AuthGroups` (one place, tested) rather than per-module files.
Consequences: A UI to switch modules (`sistem.modul`) still has to be built; guides are versioned with the code.

## D-021 — Staff account lifecycle (2026-11-17)
Status: accepted
Decision: Admins never type passwords for others: creating an account or resetting a password generates a one-time temporary password (shown once) and forces a change at next sign-in. Deactivation uses Shield's ban status (data kept, sign-in refused, reversible); there is no hard delete in the UI. Super Admin and Admin Sekolah accounts and roles can only be managed with `pengguna.kelola-admin` (Super Admin). Admins cannot deactivate themselves or change their own roles. An admin can turn off a user's 2FA when the phone and recovery codes are lost.
Consequences: Without email delivery, temporary passwords are handed over in person.

## D-022 — Two-step verification enforcement (2026-11-17)
Status: accepted (implements D-006)
Decision: `TotpAction` (Shield login action) asks for a code only from users who turned 2FA on. Super Admin chooses roles that must use it (`setting('TwoFactor.requiredRoles')`, permission `sistem.keamanan`); members of those roles are redirected to the setup page until it is on, and cannot turn it off. Five wrong codes cancel the pending sign-in. Recovery codes are shown once after setup or regeneration.
Consequences: Enforcement happens after the first password sign-in (setup cannot be done before signing in).

## D-023 — CSRF and security headers globally (2026-11-17)
Status: accepted
Decision: `csrf` (session token) and `secureheaders` run on every request; `auth-rates` throttles `/masuk` and `/verifikasi`. In the `testing` environment CSRF and throttling are skipped so feature tests can post forms.
Consequences: Every form must include `csrf_field()`.

## D-024 — Admin Sekolah may manage other Admin Sekolah accounts (2026-11-17)
Status: accepted (supersedes the Admin Sekolah part of D-021)
Context: D-021 reserved both Super Admin and Admin Sekolah accounts for Super Admin. The user confirmed that Admin Sekolah should be able to create other admins.
Decision: Only the Super Admin role and Super Admin accounts need `pengguna.kelola-admin` (Super Admin only). Admin Sekolah can create, edit, deactivate and reset other Admin Sekolah accounts and assign the Admin Sekolah role. Nobody can deactivate themselves or change their own roles.
Consequences: A school can run without Super Admin involvement for staff changes; Super Admin stays the only role that can create or manage Super Admins.

## D-025 — Module self-description and the Riwayat core module (2026-11-17)
Status: accepted (extends D-020)
Context: The Fitur page (`sistem.modul`) needs a readable name and description for each module, also while it is switched off. The audit log viewer needed a home.
Decision: Each module may provide `Config/Module.php` returning `label`, `description` and `icon` (Indonesian); a test requires it for every module in `app/Modules`. The audit log viewer is the module `Riwayat` and is core: the audit trail is a security requirement, so its viewer cannot be switched off. Disabling a module only hides routes, menu and guide sections; its data is kept.
Consequences: New modules add `Config/Module.php` and their audit labels in `Riwayat`'s language file. In phase 1 every module is core, so the Fitur page shows an empty optional list until phase 2 modules arrive.

## D-026 — Friendly error pages with a no-dependency fallback (2026-11-17)
Status: accepted
Decision: 404s go through a route override (`App\Controllers\Errors::notFound`); 500s in production use `errors/html/production.php`. Both render `errors/friendly` in the admin layout for signed-in staff and the public layout otherwise, with a fresh view renderer. If rendering fails, open output buffers are discarded and a self-contained page (inline CSS, no settings or database) is returned. Visitors see the school's WhatsApp link on error pages when it is set. No technical details are ever shown outside development.
Consequences: Feature tests receive a 404 response instead of a thrown `PageNotFoundException` (tests assert the status).

## D-027 — Painted storybook illustration style, generated by ChatGPT from the repository (2026-11-17)
Status: accepted (replaces the flat vector style in `ai/style-guide.md`)
Context: The user does not want semi-realistic or 3D-looking images, and wants ChatGPT to work directly from detailed briefs in the repository.
Decision: All illustrations use a hand-painted gouache/watercolour storybook style with stylised characters (not realistic, not 3D), a muted warm palette with one blue accent, and a recurring cast for consistency. Each image has a detailed brief and a ready prompt in `ai/prompts/`; ChatGPT follows `ai/CHATGPT.md` (started with the prompt in `ai/README.md`) and generates the queue one image at a time. Requested sizes follow ChatGPT's shapes (1024², 1536×1024, 1024×1536); Claude crops to the final assets.
Consequences: ChatGPT needs read access to the repository (GitHub connector) or the `ai/` files attached. Images come back to Claude through the chat, since `ai/output/` is git-ignored.

## D-028 — ChatGPT delivers illustrations by pull request into `ai/generated/`; Claude installs (2026-11-17)
Status: accepted (refines D-027)
Context: The user wants ChatGPT to do only the image work and hand it over through the repository, with installation left to Claude. The first delivery (PR #7) put the PNG originals in `ai/generated/`.
Decision: ChatGPT adds the PNG originals to `ai/generated/` (with its README as the delivery table) on a branch `art/illustrations-<date>`, may only set the status of delivered briefs to `generated`, and opens one pull request into `main` without merging it or adding AI attribution. Claude reviews the images, installs the approved ones (WebP under `public/assets/img/`, views) in its own pull request and sets the status to `done`. The originals stay in `ai/generated/` as the source for later re-exports.
Consequences: ChatGPT needs write access to the repository acting as the `arumiforge` account, or the commit-policy check fails; without it the user uploads the files on GitHub. The originals (about 27 MB for 001–012) are part of the repository history.

## D-029 — Parallel sessions with an integration branch per phase (2026-11-17)
Status: accepted
Context: Phase 2 has four fairly independent parts; the user asked for one coordinator session and parallel worker sessions.
Decision: Process in `docs/coordination.md`: integration branch `phase-2` from `main`; the coordinator lands a shared foundation (migrations, models, permissions, settings, menus, extension points, plan) before workers start; each worker owns a disjoint file set (`docs/phase-2-plan.md`) and opens a PR into `phase-2`; only the coordinator edits shared docs and merges into `phase-2`; the user merges `phase-2` into `main`.
Consequences: Workers ask the coordinator for changes to shared files instead of making them; one `composer.lock` writer.

## D-030 — Card PDF with FPDF; PhpSpreadsheet for XLSX (2026-11-17)
Status: proposed (replaces the PDF part of D-007)
Context: Dompdf (D-007) is LGPL-2.1, outside the MIT/BSD/Apache/ISC rule that needs no extra approval. The card is a small fixed layout.
Decision: `setasign/fpdf` (MIT) draws the registration card; the QR is a PNG from `chillerlan/php-qrcode`. `phpoffice/phpspreadsheet` (MIT) reads XLSX/CSV imports. Revisit Dompdf (or another library) for HTML-based report PDFs in phase 4 with the user's approval.
Consequences: Card text uses FPDF core fonts (Latin-1): text is converted from UTF-8; characters outside Latin-1 are transliterated.

## D-031 — Registration numbers (2026-11-17)
Status: proposed (FR-REG-03; the school confirms the format and yearly reset)
Decision: Format from `setting('Pendaftaran.numberFormat')` (default `PMB-{YEAR}-{0000}`; tokens `{YEAR}`, `{YY}`, `{0000}` = zero-padded sequence). One counter row per scope in `registration_counters` (scope = academic year by default, `numberScope = never` for one endless sequence), incremented inside the registration transaction with `SELECT … FOR UPDATE`. The number is assigned when the form is submitted, shared by online and offline, unique, immutable and never reused (deleted applicants keep their number).
Consequences: Gaps appear when an applicant is deleted; that is intended.

## D-032 — Duplicate warnings without leaking data (2026-11-17)
Status: proposed (FR-REG-05)
Decision: `DuplicateChecker` matches NIK, NISN, and normalised name + birth date. On the public form a match never blocks registration and never shows the other record: the applicant gets a neutral hint to use "Cek Status" if they registered before, and the new record is flagged `possible_duplicate` for the committee. Staff entering an offline registration see the matching records and must confirm before saving. Committee review is audited (`duplicate_reviewed`).
Consequences: Real duplicates are cleaned up by staff, not prevented online.

## D-033 — Module extension points (2026-11-17)
Status: accepted (extends D-020, D-025)
Decision: A module's `Config/Module.php` may list `requires` (a module is off while a required module is off). Modules may provide `Config/PublicMenu.php` (public navigation), `Config/Audit.php` (Riwayat labels: events, subjects, settingGroups, fields, eventColors, boolFields), `Views/panels/<slot>.php` rendered by `module_panels('<slot>', $data)` on another module's page (slots `applicant_admin`, `applicant_public`), and idempotent `Database/Seeds/InstallSeeder` / `DemoSeeder` run by `spmb:install` (`--demo`).
Consequences: Pages compose sections from modules without hard dependencies; Riwayat's language file stays small.

## D-034 — Applicant access without an account (2026-11-17)
Status: proposed (implements D-008)
Decision: After a successful status check (registration number + date of birth, throttled per IP, one generic error message) or opening the private link `/tautan/<token>`, the visitor's session holds a grant for that applicant (`service('applicantAccess')`), sliding, `Pendaftaran.applicantSessionMinutes` (30) of inactivity. The private-link token is shown once after registration and stored only as a SHA-256 hash. Card QR tokens are stored as issued (needed to reprint the same QR) and are revocable; they open only a minimal check page.
Consequences: A lost private link cannot be shown again; the number + birth date check still works.

## D-035 — Card available immediately by default (2026-11-17)
Status: proposed (FR-CARD-04; committee decision pending)
Decision: `setting('Kartu.availableWhen')` = `immediately` (default) or `verified` (after the committee sets the applicant to "Sudah diperiksa"). Staff with `kartu.cetak` can always print.

## D-036 — Uploaded documents (2026-11-17)
Status: proposed (FR-DOC-01…04)
Decision: Files live in `writable/uploads/berkas/<yyyy>/<random>.<ext>` and are served only by controllers that check `pendaftar.view` or the applicant grant. Extension, real MIME type and size are checked per document type. Images are resized to at most `Berkas.imageMaxEdge` (1600 px) on the longest side and saved as JPEG at `Berkas.imageQuality` (80) without EXIF; PDFs are kept as uploaded within the size limit. Replacing a file keeps the old row soft-deleted. Applicant document status: incomplete / pending / to_follow / complete, recomputed after each change.

## D-037 — Applicant form fields are settings (2026-11-17)
Status: proposed (school's field list pending)
Decision: The `applicants` table holds the superset of likely fields; `Pendaftaran.requiredFields` and `Pendaftaran.hiddenFields` decide what the form asks. Defaults: name, gender, birth place and date, address, parent/guardian name and WhatsApp number, previous school required; NISN required; NIK optional; KK number and parent occupations hidden; no income field (UU PDP: minimum data). NIK is stored in plain text for exact duplicate checks, masked in lists and visible only with `pendaftar.view`.

## D-038 — Table names (2026-11-17)
Status: accepted
Decision: New tables use English names (`waves`, `applicants`, `registration_counters`, `document_types`, `applicant_documents`, `card_tokens`), like the phase 1 tables; the school list keeps the name `ref_sekolah` from D-012. Status codes are stored in English and labelled in Indonesian (`app/Language/id/Status.php`).

## D-039 — Previous-school import rules (2026-11-18)
Status: proposed
Decision: Imports upsert by NPSN, otherwise by name + district + regency; they never delete, never change `is_active`, and empty cells never wipe data. Bulk writes skip per-row audit and log one `schools_imported` event. MI come from EMIS files (`spark referensi:import --file=`), not Dapodik.

## D-040 — Dapodik access needs a decision (2026-11-18)
Status: superseded by D-057 (no key used; national JSON list instead)
Context: The Dapodik portal is now a single-page app; its API (`/api/progress-pengiriman/...`) requires an `Authorization: Bearer` key that the portal publishes in its own `/env.js`. The importer was built against the endpoints and field names read from the portal's public bundle, but was not run against live data.
Decision: The importer sends a key only when the operator sets `referensi.dapodikToken` in `.env`; until the owner decides whether using that key is acceptable, previous schools are loaded with the CSV/XLSX import.

## D-041 — Registration card layout (2026-11-18)
Status: proposed
Decision: The card is printed on the top half of an A4 sheet with a cut line (A5 landscape area), black on white, with a box for a glued 3×4 photo. The QR is drawn as vector shapes from the QR matrix (sharper print, no temporary files).

## D-042 — Semantic audit events for cards and documents (2026-11-18)
Status: accepted (extends D-015)
Decision: Card and document writes run with model callbacks off and log one meaningful event each instead of generic created/updated rows: `card_issued`, `card_revoked`, `card_printed` (subject: the applicant) and `document_uploaded`, `document_replaced`, `document_to_follow`, `document_verified` (with old/new values). Card scan times (`last_seen_at`) are not audited.

## D-043 — Document upload rules (2026-11-18)
Status: proposed (refines D-036)
Decision: Images are accepted up to `Berkas.imageUploadLimitKb` (10 MB, phone cameras) and the type's `max_size_kb` applies to the resized JPEG; for PDFs it applies to the uploaded file. Images are processed with GD directly (EXIF orientation applied, white background, JPEG re-encode removes metadata). Applicants cannot replace a document already marked valid or change a cancelled registration; staff can. "Menyusul" only for `may_follow` types without a usable document. A document type can be deleted only if never used; otherwise it is hidden.
Consequences: Server limits must allow 10 MB uploads (`docs/deployment.md`).

## D-044 — Online registration closes when the wave quota is full (2026-11-18)
Status: proposed
Decision: The public form closes when no wave covers today, when `Pendaftaran.onlineOpen` is off, or when the current wave's quota is reached. Staff can still register offline above the quota.

## D-045 — Duplicate check scope (2026-11-18)
Status: proposed (refines D-032)
Decision: Duplicate checks run within the same academic year. Offline duplicates confirmed by staff are still flagged `possible_duplicate` for committee review.

## D-046 — Staff can issue a new private link (2026-11-18)
Status: proposed (refines D-034)
Decision: `POST /admin/pendaftar/<id>/tautan` (`pendaftar.edit`, audited `private_link_created`) replaces the applicant's private link; the old link stops working and the new one is shown once with a WhatsApp share button. Always-required form fields: name, gender, birth place and date, address, parent WhatsApp number, previous school, and at least one of father/mother/guardian name. NIK and KK are masked on the applicant's own page.

## D-047 — Quota behaviour per wave (2026-11-18)
Status: accepted (answered by the user; replaces D-044)
Decision: Each wave has `quota_mode`: `none` (the quota is information only), `close` (online registration closes when the wave's applicants reach the quota) or `online_limit` (online registration closes when online applicants reach `online_quota`, e.g. quota 200, online limit 150). Staff can always register offline. Cancelled and deleted applicants do not count.

## D-048 — KK number, parent occupation and income (2026-11-18)
Status: accepted (answered by the user; replaces the KK/occupation/income part of D-037)
Context: The ethics committee and the education office approved collecting the KK number, parent occupation and income; parents and the school agreed.
Decision: KK number, father/mother/guardian occupation and father/mother/guardian monthly income are optional fields by default. Occupation and income are dropdowns whose lists are settings (`Pendaftaran.occupationOptions`, `incomeOptions`; Dapodik categories by default, editable by Admin Sekolah); the chosen text is stored. Each configurable field can be Wajib, Tidak wajib, Hanya diisi petugas (`staffOnlyFields`: not on the public form, filled by staff) or Tidak ditampilkan.
Consequences: Retention and access rules of UU PDP apply to these fields as to the rest of the applicant data (visible only with `pendaftar.view`, audited).

## D-049 — Random code at the end of registration numbers (2026-11-18)
Status: accepted (answered by the user; extends D-031)
Decision: The number format may contain one `{ACAK}` (4 characters) or `{ACAK:n}` token (n = 1…5), normally at the end, e.g. `PMB-{YEAR}-{0000}-{ACAK:3}` → PMB-2027-0001-K7Q. Characters: uppercase letters and digits without look-alikes (no O, I, L, 0, 1), from `random_int`. Without the token there is no random part. Numbers still restart every academic year (D-031 confirmed) and are never reused.

## D-050 — One installation per school (2026-11-18)
Status: accepted (confirms D-004; interview round 1, Q1)
Decision: Each school gets its own installation and database. First client: SMP NU Al Ma'ruf Kudus at `spmbsmpnualmarufkudusweb.id`. The school's name, logo and colours are entered in Pengaturan → Identitas Sekolah (white label); nothing school-specific goes into code.

## D-051 — Roles in use (2026-11-18)
Status: accepted (interview round 1, Q4; refines D-014); unused optional roles removed by D-063
Decision: The school uses Admin Sekolah (everything for the school, including settings for waves, documents, cards, tests and uniforms), Panitia Pendaftaran (registration work only: applicants, documents, cards, test attendance, re-registration and uniform pickup; no settings) and the applicant access used by parents. Panitia gains `tes.kehadiran`, `daftar-ulang.proses` and `seragam.proses`. Kepala Sekolah, Petugas Tes, Petugas Daftar Ulang and Petugas Seragam stay available but unused by default, so tasks can be handed out later without a code change.

## D-052 — "Akun siswa" without a password; messages instead of live chat (2026-11-18)
Status: accepted (confirms D-008 and D-034; interview round 1, Q3 and Q6)
Decision: The student account used by parents is the existing no-password access (registration number + birth date, or the private link). Chat is not live: from that page parents send messages, criticism and suggestions; the committee (`chat.balas`) reads them in an inbox and replies; replies show on the applicant's page (and later via WhatsApp, phase "Later"). No WebSocket or polling server needed. Built in phase 5 (module Chat, renamed in the UI to "Pesan").

## D-053 — Compact visitor analytics (2026-11-18)
Status: accepted (refines D-009; interview round 1, Q5)
Decision: Built-in, no third-party script, deliberately simple: page views and unique visitors per day, top pages, and simple totals for referrers and device type. No per-visitor detail.

## D-054 — Brand images and school branding packs (2026-11-18)
Status: accepted (extends D-019, FR-PLT-01)
Context: The first client delivered a square logo, a landscape logo for light and dark backgrounds, and the logo of its parent organisation (LP Ma'arif NU), and asked for a favicon and a link-preview image.
Decision: Brand images are settings, never code: square logo, landscape logo (light) and its dark-mode version, app icon (512 px), link-preview image (1200 × 630), favicon, and the parent organisation's name and logo for the website footer ("Di bawah naungan"). They are uploaded in Pengaturan → Identitas Sekolah or loaded from a branding pack with `php spark spmb:branding clients/<school>/branding` (branding.json + images); `App\Libraries\BrandingAssets` stores them in `writable/uploads/branding/` and `/aset/<slug>` serves them. Pages carry Open Graph / Twitter tags, `theme-color` and `apple-touch-icon`. Each client's originals and pack live in `clients/<school>/` (data, not code).
Consequences: The public and sign-in headers show the landscape logo (swapping to the dark version in dark mode) when set; otherwise logo + name as before. In dark mode the accent text tints are lighter so dark brand colours stay readable.

## D-055 — Optional illustration accent set (2026-11-18)
Status: accepted (refines D-027)
Decision: `School.illustrationAccent` ('' = blue default, 'hijau' = green) makes `illustration()` use `<name>-hijau.webp` (and `<name>-dark-hijau.webp`) when present and fall back to the default painting otherwise. ChatGPT makes the green set by editing the originals (brief 013); brief 014 adds the missing dark versions. SMP NU Al Ma'ruf Kudus uses the green set (#005c2f brand, logo green and NU green).

## D-056 — Go-live for the school's review (2026-11-18)
Status: accepted (interview round 1, Q2 and Q8)
Decision: Go live as soon as possible on `spmbsmpnualmarufkudusweb.id` so the school can check it; the school enters wave 1 dates itself. Cloudflare (DNS, tunnel, Access) runs on the developer's (Arumi Studios') account. `deploy/provision.sh` prepares a fresh Ubuntu 24.04 server; the checklist is in `docs/deployment.md` ("Go-live").
Consequences: Arumi Studios provides the VPS and keeps the Cloudflare account; transfer to the school later is a DNS/account change, not a code change.

## D-057 — National SD/MI list from a JSON file (2026-11-18)
Status: accepted (resolves D-040)
Context: Arumi Studios prepares one JSON file with every SD and MI in Indonesia (NPSN, name, village, district, regency, province).
Decision: `php spark referensi:import --file <list>.json` (also `.json.gz`, NDJSON) loads it with the same upsert rules as D-039; keys are matched by alias, and the level is taken from the school name when the file has none. This is the main source of previous schools; the Dapodik importer stays optional and no portal access key is used. The search still lists the configured provinces (`Referensi.provinces`, Jawa Tengah) first.
Consequences: Schools from all provinces can be chosen, so fewer "sekolah lain" entries need mapping. The file is kept out of git and imported on the server.

## D-058 — The national SD/MI list ships with SPMB (2026-11-18)
Status: accepted (user's request; refines D-057)
Context: Arumi Studios delivered the list as 38 JSON files, one per province (177,422 SD and MI: NPSN, name, level, status, village, district, regency, province). It is public data with no personal data, so it may live in the repository.
Decision: The list is default data: gzipped per-province files plus a manifest in `app/Modules/Referensi/Database/Data/sekolah-sd-mi/` (2.7 MB). `spmb:install` loads it and every deploy runs `referensi:import --bawaan`, which is skipped while the manifest version equals `Referensi.bundledVersion`. Rows get source `nasional`; D-039 upsert rules apply. Temporary `NP…` numbers are not treated as NPSNs.
Consequences: Every installation can find any SD/MI in Indonesia without a manual import; the Jawa Tengah-first ordering of the search stays. Updating the list = replace the files, bump the manifest version, deploy. Replaces "keep the file out of git" in D-057.

## D-059 — Placement test model: rooms, calendar, sessions (2026-11-18)
Status: accepted (phase 3 foundation; the school sets days, rooms and quotas itself, interview round 1)
Decision: Module `Tes`. A session is one room on one date and time with a quota (default: the room's capacity) and an optional wave (`null` = applicants of every wave, so the school can test per wave or after all waves). Holidays are dates without tests: sessions cannot be created on them and the allocation skips them. Each applicant has at most one current seat (`test_assignments`, status pending/present/absent/rescheduled); every change is also a row in the insert-only `test_schedule_changes` log (FR-TEST-04). A cancelled or deleted applicant frees the seat without deleting the row.

## D-060 — Automatic allocation (2026-11-18)
Status: accepted (FR-TEST-02, brief §4)
Decision: Staff with `tes.atur` preview and then run the allocation for one wave or all waves from a start date (default tomorrow). Eligible: applicants of the academic year whose status is in `Tes.eligibleStatuses` (registered, verified), not deleted, without a seat, and with complete documents only when `Tes.requireCompleteDocuments` is on. They are placed in registration order (`applicants.id` within the year, the same order as the numbers; online and offline equal) into active sessions in time order (date, start time, room order) up to the free seats. Running it again only places applicants without a seat; nobody is moved. Applicants left without a seat are reported so the school can add sessions.

## D-061 — Attendance by QR or search (2026-11-18)
Status: accepted (FR-TEST-03, D-051)
Decision: Panitia Pendaftaran and Petugas Tes (`tes.kehadiran`) record attendance on the session roster: Hadir / Tidak hadir per row, a camera scanner in the page (`qr-scanner`, MIT; BarcodeDetector where available), a text field for USB scanners and typed numbers, and a "Catat Hadir" button on the card check page `/kartu/<token>` for signed-in staff. Someone who comes to another session is moved there with the reason "Hadir di sesi lain" (logged) and then marked present. After a session, the remaining participants can be marked absent in one step. A mark can be undone (back to "Belum tes"); every mark is audited.

## D-062 — Schedule shown to applicants (2026-11-18)
Status: accepted (brief §3: the latest card shows the schedule once set)
Decision: When `Tes.scheduleVisible` is on (default), the applicant's page shows day, time, room, location, status and the instructions text (`Tes.instructions`), and the PDF card's "Jadwal Tes" line shows the session; otherwise both say "Belum dijadwalkan" / that the schedule will be announced. The card is never regenerated as new data: it reads the current schedule each time it is printed.

## D-063 — Only three roles: Admin Sekolah, Panitia, Siswa (2026-11-18)
Status: accepted (user's instruction; replaces the "optional roles" part of D-014 and D-051)
Decision: The school uses exactly three roles: Admin Sekolah (`admin`), Panitia (`panitia`, one role for all committee work: registration, documents, cards, test attendance and seat moves, re-registration, uniform pickup, replies to parents) and Siswa (the no-password applicant access, D-052; not a staff account). Super Admin stays as the developer's technical account. The groups Kepala Sekolah, Petugas Tes, Petugas Daftar Ulang and Petugas Seragam are removed; migration `MergeStaffRolesIntoPanitia` moves any account in them to Panitia and drops them from the two-step verification policy. Guides: `/panduan/panitia` (old role guide URLs redirect). Permissions stay fine-grained, so a role could be split again later without code changes in modules.
Consequences: Reports (`laporan.*`) are for Admin Sekolah only. Tests that need a read-only staff account use per-user permissions (`Tests\Support\ViewerAccounts`).

## D-064 — Placement test details settled while building (2026-11-18)
Status: accepted (phase 3 packages A–C, reviewed by the coordinator; details of D-059…D-061)
Decision:
- Sessions and rooms: a session can be deleted only when it has no assignment row at all (cancelled applicants included); otherwise it is deactivated. A room can be deleted only when no session uses it. Overlap checks in a room count inactive sessions too. Changing the date, time or room of a session with participants asks for confirmation; participants keep their seat. The session generator skips holidays and existing overlaps and logs one `sessions_generated` event.
- Seats: "past" means a test date before today, so today's sessions stay usable for walk-ins. A move to an earlier date needs `tes.atur`. A full session is accepted only after staff confirm ("Tetap masukkan walau sesi penuh"); `Scheduler::assign()` and `move()` take `$allowOverQuota`. Removing someone already marked present is refused (undo the attendance first). Seat events are audited on the applicant (`test_assigned`, `test_moved`, `test_removed`); an allocation run logs one `test_allocated` on `test_sessions`.
- Attendance: undo always returns to "Belum tes"; recording the same status again changes and logs nothing; "mark the rest absent" is possible only after the session's end time; attendance is not limited to today, so mistakes of earlier days can be corrected. A revoked card is refused with a clear message.
Consequences: Admin Sekolah prepares sessions and runs the allocation; Panitia records attendance and moves walk-ins on test day.

## D-065 — Correct domain; provisioning guards for custom SSH ports and password logins (2026-11-19)
Status: accepted (user's answer while preparing the first VPS; corrects the domain in D-050 and D-056)
Context: The first client's domain is `spmbsmpnualmarufkudus.web.id`; the repository had `spmbsmpnualmarufkudusweb.id` (typo). The VPS bought for it runs sshd on a provider-set custom port and is reached as root with a password from Bitvise on Windows. `provision.sh` enabled a deny-all firewall before the tunnel existed (new SSH logins blocked) and turned password login off in the main `sshd_config` (no effect on cloud images, a lock-out on servers without a key).
Decision: The domain is `spmbsmpnualmarufkudus.web.id` everywhere (docs, client README, `branding.json`, share image). `provision.sh` keeps the port(s) sshd listens on open in ufw while `cloudflared` is not running (removed by hand after SSH through the tunnel works; `KEEP_SSH_OPEN=0` skips it) and switches password login off through `/etc/ssh/sshd_config.d/00-spmb.conf` only when root or a sudo user has an SSH key. Tunnel ingress uses `127.0.0.1` and the real SSH port; `SSH_KNOWN_HOSTS` is built from the server's host key, not `ssh-keyscan`; `spark` runs as `deploy`. Server IPs and ports stay out of the repository.
Consequences: Running the script over a password-only root session no longer locks the admin out; password login stays on (with a warning) until a key is added and the script runs again. Clients without a proxy command reach SSH with `cloudflared access tcp` (docs/deployment.md).

## D-066 — Docker on the VPS, admin access that does not depend on SSH or Docker (2026-11-22)
Status: accepted (user, 2026-11-22; replaces the bare-metal server layout of D-011/D-056 and `deploy/provision.sh`)
Context: The production VPS (`spmb-prod`, Ubuntu 24.04.5) is a NAT VPS: private address, sshd on a provider-set port, root with a password from Bitvise, and no provider console. More apps will share the server later, and moving to another server must be easy. The user asked about Proxmox; Proxmox replaces the OS and network and needs nested virtualisation, so installing it here without a console risks a permanent lock-out.
Decision: No Proxmox. Every app runs as its own Docker Compose stack (own network, database and volumes) that publishes one port on `127.0.0.1` only, taken from a port list on the server; nothing listens publicly. `cloudflared` (tunnel) and Cockpit (browser console: terminal, services, logs, reboot) run on the host, not in Docker, so admin access survives a broken Docker or a broken sshd. Cockpit and SSH are reachable only through Cloudflare Tunnel behind Cloudflare Access. A one-time `setup.sh` prepares a fresh Ubuntu 24.04 server (Docker, cloudflared, Cockpit, firewall with the D-065 lock-out guards, swap, security updates, log limits, `live-restore`); it is idempotent and never turns off the access the admin is using.
Consequences: A new or replacement server needs only `setup.sh`, the git checkout and a restore of each stack's backup. Docker publishes no public ports, so ufw and Docker do not fight.

## D-067 — Staging and production as two stacks on one VPS, from the one `main` branch (2026-11-22)
Status: accepted (user, 2026-11-22)
Decision: The same VPS runs `spmb-staging` (`staging.<domain>`, own database and files) and `spmb-production`. There is one long-lived branch, `main`. Staging is deployed from `origin/main` (or a given commit); production is promoted to the exact image that is running on staging (or a given commit/tag), so production never runs an untested build. Development stays on Windows with Laragon (Nginx, MySQL) from the same repository.
Consequences: Staging data is test data only; never copy production personal data to staging (UU PDP).

## D-068 — The server pulls and builds; no deploy key in the repository or in GitHub (2026-11-22)
Status: accepted (user, 2026-11-22; replaces the GitHub Actions SSH deploy of D-011)
Decision: The developer gives the server read access to the repository themselves (their own key or token, kept on the server, never in the repository or in GitHub secrets). A server command (`spmb deploy staging|production [ref]`) fetches, builds the image from the exact commit (multi-stage: Node 24 for assets, PHP 8.5 FPM, Nginx), backs up the database, starts the new containers, runs migrations and the bundled data import, and can roll back to the previous image. The `Deploy` workflow in GitHub Actions is retired; CI keeps linting and testing.

## D-069 — Minimum runtime versions: PHP 8.5.13, Node.js 24, MySQL 8.4 (2026-11-22)
Status: accepted (user, 2026-11-22; updates D-001)
Decision: `composer.json` requires `php ^8.5.13`; `package.json` requires Node `>=24`; CI runs PHP 8.5 with MySQL 8.4 and Node 24; the Docker image uses `php:8.5-fpm` and `node:24`. The full suite (424 tests) passed on PHP 8.5.13 with MySQL 8.4 before the switch. Laragon on Windows needs PHP 8.5.13+ and Node 24 added.

## D-070 — Visual rules: Lucide icons without backgrounds, no eyebrows, no accent bars or accent borders (2026-11-22)
Status: accepted (user, 2026-11-22; updates D-010)
Decision: Icons come from Lucide (`lucide-static`, ISC) through the `icon()` helper; only the paths of the icons in use are copied into the helper, so production needs no Node. Icons sit directly next to text in `currentColor`, with no tinted square or circle behind them. No eyebrow labels (small uppercase or tracked text above a heading), no short decorative lines under or above headings, no cards with a coloured top or side border. Heavier pages are accepted: illustrations, animation and rich visuals are welcome as long as every feature works on mobile and desktop and `prefers-reduced-motion` is respected. Charts must never overlap text at 360 px (labels shortened or rotated, data labels off on small screens, legend below). Rules live in `spmb-ui`.

## D-071 — Writing standard: `bahasa-indonesia-plain` (2026-11-22)
Status: accepted (user, 2026-11-22; extends `spmb-copy`)
Decision: The user's `bahasa-indonesia-plain` skill (plain language, EYD, inclusive and trauma-informed writing, number literacy, RASA check) is the writing standard for all Indonesian text. `spmb-copy` keeps SPMB's glossary, the "Anda" form of address and guide structure. New and rewritten text uses sentence case for headings, buttons and labels; existing Title Case labels are changed in a later copy sweep, not piecemeal.

## D-072 — Public Tanya Jawab page (2026-11-22)
Status: accepted (user, 2026-11-22)
Context: The writing skill says "Jangan gunakan FAQ"; the user explicitly asked for a Q&A page for parents.
Decision: The public site gets `/tanya-jawab`: questions grouped by topic, edited by Admin Sekolah in the dashboard (white label, nothing school-specific in code). Starter questions for SMP NU Al Ma'ruf Kudus ship in the client pack (`clients/smp-nu-al-maruf-kudus/`) and are loaded with the branding pack. Answers point to the real pages (jadwal, persyaratan, cek status) instead of repeating them, so the page does not replace clear content elsewhere.

## D-073 — Codex makes the visual assets; identity is santri, Islamic, Nahdlatul Ulama, Indonesian and Bhinneka (2026-11-22)
Status: accepted (user, 2026-11-22; replaces ChatGPT in D-027/D-028)
Decision: Codex (with repository access) makes all illustrations, special buttons, art, patterns and other visuals from briefs Claude writes in `ai/`; Claude installs and wires them in. The visual identity for this product is santri, Islamic, Nahdlatul Ulama, Indonesian and Bhinneka Tunggal Ika: warm, respectful, inclusive of every region, without stereotypes. School-specific marks (logos, names) stay in settings and the client pack; generic assets must work for any school.

## D-074 — Cloud environment setup lives in the repository (2026-11-22)
Status: accepted (user, 2026-11-22)
Context: Several Claude accounts work on SPMB and share nothing but the repository. The old inline setup script in one account's environment broke when PHP 8.5.13 became the minimum (D-069), and other accounts had no record of it.
Decision: `.claude/cloud/setup.sh` is the single setup script for Claude Code cloud environments (PHP 8.5.13+ from the ondrej PPA, Node.js 24 from NodeSource, MySQL, dependencies, build, demo install). Each account's environment runs it through a one-line bootstrap; `docs/cloud-environment.md` lists the settings (name, repository, network, variables `COMPOSER_ALLOW_SUPERUSER=1` and `TZ=Asia/Jakarta`, no secrets).
Consequences: Runtime changes are made once in the repository and reach every account at its next fresh session.

## D-075 — How Codex delivers visual assets (2026-11-22)
Status: accepted (coordinator, platform package D; details of D-073)
Decision: Codex delivers ready-to-install files under `ai/generated/install/` (mirroring `public/assets/img/` and `resources/css/visual/`) plus untrimmed WebP masters in `ai/generated/`, one pull request per batch into `main`, never merged by Codex. Patterns and dividers are `#000` SVG masks painted with `var(--color-primary-*)`, so the brand colour stays in settings; buttons and loaders use `currentColor` and custom properties. Paintings keep the swappable blue accent (D-027) and every new one also gets `-dark`, `-hijau` and `-dark-hijau` variants. `ai/tools/check_assets.py` checks names, sizes, budgets, alpha and SVG/CSS safety before Claude installs. A root `AGENTS.md` points Codex to `ai/CODEX.md`.
Consequences: Installing needs a `resources/css/visual/` folder imported from `app.css`, a loader partial, `.tombol-daftar` on the main call to action, a fallback share image, and (later) `--tombol-teks` from the brand colour's luminance for schools with a light brand colour.

## D-076 — Staging runs as production (2026-11-22)
Status: accepted (coordinator, platform package A)
Decision: The staging stack runs with `CI_ENVIRONMENT = production`, so it behaves exactly like production (errors, caching, security headers). As a result `spmb:install --demo` is refused on staging; test accounts are created in the admin screens. Staging holds test data only, never a copy of production personal data (D-067).
Consequences: If demo accounts are wanted on staging later, `spmb:install` needs an explicit opt-in (for example `--demo --allow-staging`).
