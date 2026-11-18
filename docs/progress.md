# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Phase 3 — Placement test**: done on the integration branch `phase-3` (foundation #18, three roles #19, packages
B #20, A #22, C #21; 424 tests green). PR `phase-3` → `main` is open for the user to merge. Module Tes: rooms, calendar with
holidays, sessions + generator, automatic allocation, seat moves with a change log, attendance by QR/search with an
attendance sheet PDF (`docs/modules/tes*.md`, D-059…D-064). Only three roles now: Admin Sekolah, Panitia, Siswa (D-063).
The national SD/MI list (177,422 schools) ships as default data (D-058).

**Phase 2 — Registration**: done and merged into `main` (PR #14; packages #9–#13, school answers #15–#16,
branding/go-live/JSON import #17). Modules: Referensi, Pendaftaran, Berkas, Kartu, Situs (`docs/modules/`).

**Phase 0 — Foundation**: done. **Phase 1 — Platform core**: core merged in
[PR #1](https://github.com/arumiforge/spmb/pull/1); **phase 1 UI** merged in
[PR #2](https://github.com/arumiforge/spmb/pull/2); **phase 1 finish** (Fitur page, audit log viewer,
404/500 pages, illustration prompts) merged in [PR #4](https://github.com/arumiforge/spmb/pull/4). Only the illustrations themselves remain
(installed on the home page, sign-in, empty states, error and guide pages; 002/006 wait for phase 2).

Done in phase 1: Shield auth with 7 roles, audit trail, TOTP 2FA (service, sign-in step, setup UI,
per-role enforcement), installer, frontend build (Tailwind v4 + Flowbite), admin/auth/public layouts,
school identity + branding settings, user management, module registry + permission-based sidebar,
Panduan skeleton, Fitur page (module on/off), Riwayat Perubahan (audit log viewer), friendly 404/500 pages.
95 tests green on MySQL 8.0.

## Active work

One row per open branch. Do not work on a branch claimed here by another session;
create your own branch from `main` instead.

| Branch | Session label | Task | Status |
| --- | --- | --- | --- |
| `phase-3` | coordinator | Phase 3 integration branch | PR into `main` (user merges) |
| `nty/nifty-mendel-u2jf9s` | coordinator | SD/MI default data + foundation (#18), three roles (#19), phase 3 docs | merged into `phase-3` |
| `feat/p3-jadwal`, `feat/p3-penjadwalan`, `feat/p3-kehadiran` | workers A, B, C | Phase 3 packages | merged into `phase-3` (#22, #20, #21) |
| `phase-2` and `feat/p2-*` | coordinator, workers A–D | Phase 2 | merged into `main` (PR #14) |

## Next steps

Start a new session from `origin/main` (`spmb-session`; `composer install`, `npm ci && npm run build`,
`php spark spmb:install --demo` for local work; in cloud containers run `service mysql start` first — MySQL stops
when the container idles).

1. **User merges** the PR `phase-3` → `main` (phase 3 + D-058 + D-063).
2. **Go-live for the school's review (D-056)**: `docs/deployment.md` → "Go-live", steps 2–9. `spmb:install` also loads the
   national SD/MI list (~1 min); deploys run `referensi:import --bawaan` (no-op once loaded). Only two staff roles exist:
   create the school's Admin Sekolah, who adds Panitia accounts.
3. **Phase 4 — Re-registration & reports** (FR-DU-01, FR-SRG-01, dashboard stats, filters, XLSX/CSV/PDF export, XLSX
   import): same protocol (`docs/coordination.md`, integration branch `phase-4`, foundation first, workers). Panitia does
   re-registration and uniform pickup (D-051, D-063); reports are for Admin Sekolah.
4. **Illustrations**: when ChatGPT delivers briefs 013 (green accent) and 014 (dark versions), install them per `spmb-ai-image`.
5. **Small follow-ups**: Indonesian "Pilih berkas" button instead of the browser's "Choose File" on the applicant page;
   optional icons `camera`, `printer`, `undo` in `ui_helper` (attendance page uses `eye`/`clipboard`/`x` for now); update the
   share image year each academic year.

## Blockers / waiting on

- School still to confirm (phase 3 works with any answer): test days, sessions per day, rooms and capacity; tests per wave or after all waves.
- Check the ApexCharts v4 licence terms (revenue threshold) before launch; buy a licence or switch library if needed (D-017).
- Go-live (D-056): needs a VPS from Arumi Studios and the domain added to the developer's Cloudflare account; then `docs/deployment.md` → "Go-live" steps.
- ChatGPT briefs 013 (green accent set) and 014 (dark versions) are queued in `ai/README.md`.

## Session log (newest first, keep the last 10)

- 2026-11-18 · coordinator · `phase-3` — merged B (#20), A (#22), C (#21) after combined local runs (424 tests, demo install seats all applicants, pages checked at 360 px); docs: D-064, requirements FR-TEST-01…04 built, changelog, roadmap; PR `phase-3` → `main` opened. Next: user merges, go-live, phase 4.
- 2026-11-18 · coordinator · `nty/nifty-mendel-u2jf9s` → `phase-3` — only three roles (Admin Sekolah, Panitia, Siswa; D-063): removed Kepala Sekolah and Petugas roles, accounts migrated to Panitia, guide `/panduan/panitia`, tests use per-user permissions for read-only staff; workers told. Next: worker PRs.
- 2026-11-18 · coordinator · `nty/nifty-mendel-u2jf9s` → `phase-3` — national SD/MI list shipped as default data (D-058, 177,422 schools, loaded by `spmb:install` and deploys); phase 3 foundation (module Tes, D-059…D-062, `phase-3-plan.md`); workers A–C started. Next: review worker PRs.
- 2026-11-18 · coordinator · `phase-2` → `main` — merged #17 into `phase-2` and PR #14 into `main` (user's request); handoff: JSON import, go-live, illustrations, phase 3. Coordinator session ended.
- 2026-11-18 · coordinator · `feat/p2-branding` — JSON import for the national SD/MI list (D-057, resolves D-040); brand images + branding pack command, SMP NU Al Ma'ruf Kudus pack (logos, favicon, icon, share image, green), illustration accent setting, ChatGPT briefs 013/014, `deploy/provision.sh` + go-live checklist (D-054…D-056). Next: merge, user merges #14, go-live steps 1–9.
- 2026-11-18 · coordinator · `feat/p2-round1-answers` — interview round 1 answers: one installation per school, roles (Panitia also tests/re-registration/uniform), no-password student access + messages instead of live chat, compact analytics, domain and school name (D-050…D-053). Next: merge into `phase-2`; phase 3.
- 2026-11-18 · coordinator · `feat/p2-school-answers` — school answers applied: quota per wave (D-047), KK/occupation/income dropdowns + staff-only field mode (D-048), random number suffix (D-049); 288 tests. Next: merge into `phase-2`, user merges #14.
- 2026-11-18 · coordinator · `phase-2` — merged B (#10) after integration with A/C/D (282 tests); docs: requirements status, roadmap, architecture tables, D-044…D-046; opened PR `phase-2` → `main`; workers archived. Next: user merges, then phase 3.
- 2026-11-18 · coordinator · `phase-2` — workers resumed after the usage limit; merged D (#11), A (#12), C (#13) after local tests on the combined branch (240 tests); decisions D-039…D-043; Dapodik key question open (D-040). Next: B (#10), then PR `phase-2` → `main`.
- 2026-11-17 · coordinator · `feat/p2-foundation` — phase 2 foundation: shared migrations/models, settings, `referensi.kelola`, extension points (D-033), FPDF + PhpSpreadsheet (D-030), coordination protocol and plan (D-029…D-038). Next: workers A–D.
