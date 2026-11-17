# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Phase 0 — Foundation**: done. **Phase 1 — Platform core**: core merged in
[PR #1](https://github.com/arumiforge/spmb/pull/1); **phase 1 UI** merged in
[PR #2](https://github.com/arumiforge/spmb/pull/2); **phase 1 finish** (Fitur page, audit log viewer,
404/500 pages, illustration prompts) merged in [PR #4](https://github.com/arumiforge/spmb/pull/4). Only the illustrations themselves remain
(originals delivered by ChatGPT in `ai/generated/`, PR #7; installation pending).

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

## Next steps

1. New session: branch from `origin/main`, then `composer install`, `npm ci && npm run build`,
   create `.env` and run `php spark spmb:install --demo` per `docs/development.md` (no new migrations since PR #1).
2. Install the illustration originals in `ai/generated/` (001–012 + dark variants, reviewed: fine): convert them (spmb-ai-image) and wire them into the empty states, sign-in, error pages (`errors/friendly`) and guide pages.
3. Phase 2 (registration): waves, online/offline form, numbering, school reference data, uploads,
   PDF card + QR — start with `docs/interview/` answers on waves and documents. Each new module adds
   `Config/Module.php` (D-025) and its audit labels in `app/Modules/Riwayat/Language/id/Riwayat.php`.

## Blockers / waiting on

- Check the ApexCharts v4 licence terms (revenue threshold) before launch; buy a licence or switch library if needed (D-017).
- Interview round 1 answers (tenancy, timeline, applicant access, roles, chat, analytics, school data, branding). Defaults in use: D-004, D-008, D-009, D-014.
- Dev environment network does not allow `dapo.kemendikdasmen.go.id` / `sekolah.data.kemendikdasmen.go.id` (needed in phase 2).

## Session log (newest first, keep the last 10)

- 2026-11-17 · session 3 · `docs/chatgpt-delivery-folder` — ChatGPT's role limited to making images and opening a PR into `ai/generated/`; Claude reviews and installs (D-028). Originals 001–012 merged (PR #7). Next: install them.
- 2026-11-17 · session 3 · `feat/phase-1-finish` — Fitur page (`sistem.modul`), Riwayat Perubahan audit viewer (`audit.view`), friendly 404/500 pages with fallback, illustration prompts 008–012; D-025, D-026; merged as PR #4; painted illustration style and ChatGPT instructions `ai/CHATGPT.md` (D-027). Next: images, then phase 2.
- 2026-11-17 · session 2 · `feat/phase-1-ui` — Admin Sekolah may manage other Admin Sekolah accounts (D-024, confirmed by the user); CI fix for routes before migrations.
- 2026-11-17 · session 2 · `feat/phase-1-ui` — Phase 1 UI: frontend build, layouts, Indonesian sign-in, 2FA step + setup, settings (identity, security), user management, module registry/menu, Panduan; docs D-018…D-023; PR #2. Next: module on/off UI, audit viewer.
- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Proprietary `LICENSE` + `THIRD_PARTY_NOTICES.md` (D-017); Flowbite Pro licence proof kept outside the repo; reseller shortcuts removed. Next: check ApexCharts v4 licence terms before launch.
- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Dev install (MySQL 8, `.env`), phase 1 core: Shield + roles, Indonesian auth routes, audit trail, TOTP service, migrations, installer + demo accounts, tests. Next: frontend build and layouts.
- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Phase 0: appstarter layout, CI/commit-policy/deploy workflows, commit-msg hook, CLAUDE.md, 8 skills, docs, `ai/` prompts, interview round 1. Next: merge PR #1, interview answers.
