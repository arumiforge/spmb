# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Phase 2 — Registration**: built on integration branch `phase-2` (foundation #9, packages A–D #10–#13,
`docs/phase-2-plan.md`); one PR `phase-2` → `main` waits for the user. 282 tests green.
Modules: Referensi, Pendaftaran, Berkas, Kartu, Situs (`docs/modules/`).

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
| `feat/phase-1-ui` | session 2 | Phase 1 UI: frontend build, layouts, login/2FA screens, settings, users, panduan | merged (PR #2) |
| `feat/phase-1-finish` | session 3 | Finish phase 1: module on/off page, audit log viewer, 404/500 pages, illustration prompts | merged (PR #4) |
| `phase-2` | coordinator | Phase 2 integration branch; only the coordinator commits/merges here | PR open → `main` (user merges) |
| `feat/p2-foundation` | coordinator | Phase 2 foundation (schema, models, permissions, settings, plan) | merged into `phase-2` (PR #9) |
| `feat/p2-referensi` | worker A | Previous-school reference data + importer | merged into `phase-2` (PR #12) |
| `feat/p2-pendaftaran` | worker B | Waves, numbering, online/offline form, duplicates, status check | merged into `phase-2` (PR #10) |
| `feat/p2-berkas` | worker C | Document types, upload/resize/storage, verification | merged into `phase-2` (PR #13) |
| `feat/p2-kartu-situs` | worker D | PDF card + QR, card check page, public pages | merged into `phase-2` (PR #11) |

## Next steps

0. Phase 2 is complete on `phase-2`: after the user merges it into `main`, start phase 3 (placement test) with the same
   coordination protocol (`docs/coordination.md`). Card "Belum dijadwalkan" line gets the test schedule in phase 3.
1. New session: branch from `origin/main`, then `composer install`, `npm ci && npm run build`,
   create `.env` and run `php spark spmb:install --demo` per `docs/development.md` (no new migrations since PR #1).
2. Phase 2 pages use the prepared assets `form/daftar-online.webp` (registration form) and `state/sukses-pendaftaran.webp` (success page); set briefs 002/006 to `done` then.
3. Phase 2 (registration): waves, online/offline form, numbering, school reference data, uploads,
   PDF card + QR — start with `docs/interview/` answers on waves and documents. Each new module adds
   `Config/Module.php` (D-025) and its audit labels in `app/Modules/Riwayat/Language/id/Riwayat.php`.

## Blockers / waiting on

- Dapodik API needs a bearer key (D-040): decide whether the importer may use the portal's published key; until then schools come from CSV/XLSX.
- Check the ApexCharts v4 licence terms (revenue threshold) before launch; buy a licence or switch library if needed (D-017).
- Interview round 1 answers (tenancy, timeline, applicant access, roles, chat, analytics, school data, branding). Defaults in use: D-004, D-008, D-009, D-014.
- School committee answers (waves, documents, card rule, number format, form fields): defaults in `docs/phase-2-plan.md` (D-031, D-035, D-036, D-037).

## Session log (newest first, keep the last 10)

- 2026-11-18 · coordinator · `feat/p2-school-answers` — school answers applied: quota per wave (D-047), KK/occupation/income dropdowns + staff-only field mode (D-048), random number suffix (D-049); 288 tests. Next: merge into `phase-2`, user merges #14.
- 2026-11-18 · coordinator · `phase-2` — merged B (#10) after integration with A/C/D (282 tests); docs: requirements status, roadmap, architecture tables, D-044…D-046; opened PR `phase-2` → `main`; workers archived. Next: user merges, then phase 3.
- 2026-11-18 · coordinator · `phase-2` — workers resumed after the usage limit; merged D (#11), A (#12), C (#13) after local tests on the combined branch (240 tests); decisions D-039…D-043; Dapodik key question open (D-040). Next: B (#10), then PR `phase-2` → `main`.
- 2026-11-17 · coordinator · `feat/p2-foundation` — phase 2 foundation: shared migrations/models, settings, `referensi.kelola`, extension points (D-033), FPDF + PhpSpreadsheet (D-030), coordination protocol and plan (D-029…D-038). Next: workers A–D.
- 2026-11-17 · session 3 · `feat/illustrations` — illustrations 001–012 converted to WebP and installed with dark variants (`illustration()` helper); checked at 360/1280 px light and dark. Next: phase 2.
- 2026-11-17 · session 3 · `docs/chatgpt-delivery-folder` — ChatGPT's role limited to making images and opening a PR into `ai/generated/`; Claude reviews and installs (D-028). Originals 001–012 merged (PR #7). Next: install them.
- 2026-11-17 · session 3 · `feat/phase-1-finish` — Fitur page (`sistem.modul`), Riwayat Perubahan audit viewer (`audit.view`), friendly 404/500 pages with fallback, illustration prompts 008–012; D-025, D-026; merged as PR #4; painted illustration style and ChatGPT instructions `ai/CHATGPT.md` (D-027). Next: images, then phase 2.
- 2026-11-17 · session 2 · `feat/phase-1-ui` — Admin Sekolah may manage other Admin Sekolah accounts (D-024, confirmed by the user); CI fix for routes before migrations.
- 2026-11-17 · session 2 · `feat/phase-1-ui` — Phase 1 UI: frontend build, layouts, Indonesian sign-in, 2FA step + setup, settings (identity, security), user management, module registry/menu, Panduan; docs D-018…D-023; PR #2. Next: module on/off UI, audit viewer.
- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Proprietary `LICENSE` + `THIRD_PARTY_NOTICES.md` (D-017); Flowbite Pro licence proof kept outside the repo; reseller shortcuts removed. Next: check ApexCharts v4 licence terms before launch.
- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Dev install (MySQL 8, `.env`), phase 1 core: Shield + roles, Indonesian auth routes, audit trail, TOTP service, migrations, installer + demo accounts, tests. Next: frontend build and layouts.
- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Phase 0: appstarter layout, CI/commit-policy/deploy workflows, commit-msg hook, CLAUDE.md, 8 skills, docs, `ai/` prompts, interview round 1. Next: merge PR #1, interview answers.
