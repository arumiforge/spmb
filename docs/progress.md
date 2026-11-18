# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Phase 2 — Registration**: done and merged into `main` (PR #14; packages #9–#13, school answers #15–#16,
branding/go-live/JSON import #17). 300 tests green. Modules: Referensi, Pendaftaran, Berkas, Kartu, Situs
(`docs/modules/`). Interview round 1 fully answered (`docs/interview/round-01.md`, D-047…D-057).
Next: national school list import, go-live for the school's review, then phase 3 (placement test).

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
| `phase-2` | coordinator | Phase 2 integration branch | merged into `main` (PR #14) |
| `feat/p2-school-answers`, `feat/p2-round1-answers`, `feat/p2-branding` | coordinator | School answers, interview answers, branding + go-live + JSON import | merged into `phase-2` (PRs #15, #16, #17) |
| `feat/p2-foundation` | coordinator | Phase 2 foundation (schema, models, permissions, settings, plan) | merged into `phase-2` (PR #9) |
| `feat/p2-referensi` | worker A | Previous-school reference data + importer | merged into `phase-2` (PR #12) |
| `feat/p2-pendaftaran` | worker B | Waves, numbering, online/offline form, duplicates, status check | merged into `phase-2` (PR #10) |
| `feat/p2-berkas` | worker C | Document types, upload/resize/storage, verification | merged into `phase-2` (PR #13) |
| `feat/p2-kartu-situs` | worker D | PDF card + QR, card check page, public pages | merged into `phase-2` (PR #11) |

## Next steps

Start a new session from `origin/main` (`spmb-session`; `composer install`, `npm ci && npm run build`,
`php spark spmb:install --demo` for local work; in cloud containers run `service mysql start` first).

1. **National SD/MI list (D-057)**: when Arumi Studios delivers the JSON, check a sample of its keys against the table in
   `docs/modules/referensi.md`, run `php spark referensi:import --file <file> --dry-run` locally, fix any key/level
   mapping, then import on the server. Do not commit the file (tens of MB).
2. **Go-live for the school's review (D-056)**: `docs/deployment.md` → "Go-live", steps 2–9 (VPS, domain on the
   developer's Cloudflare, `deploy/provision.sh`, tunnel, GitHub `production` environment, Deploy workflow,
   `spmb:install`, `spmb:branding clients/smp-nu-al-maruf-kudus/branding`, Admin Sekolah account). No `--demo` on the server.
   `provision.sh` has only been syntax-checked: watch its first run.
3. **Illustrations**: when ChatGPT delivers briefs 013 (green accent) and 014 (dark versions), review and install them per
   `spmb-ai-image`; the school's `illustrationAccent` is already `hijau`, so green versions show up automatically.
4. **Small follow-ups**: replace the browser's English file input ("Choose File") on the applicant page with an Indonesian
   button; update the share image year from `clients/smp-nu-al-maruf-kudus/source/share.html` each academic year.
5. **Phase 3 — Placement test** (calendar, sessions, rooms, quotas set by the school, allocation, attendance by QR/search,
   reschedule log): same protocol as phase 2 (`docs/coordination.md`: integration branch `phase-3`, foundation first, workers).
   Panitia records attendance (D-051); the card's "Belum dijadwalkan" line gets the schedule.

## Blockers / waiting on

- National SD/MI JSON list (D-057): Arumi Studios prepares it; import on the server with `php spark referensi:import --file <file>`.
- Check the ApexCharts v4 licence terms (revenue threshold) before launch; buy a licence or switch library if needed (D-017).
- Go-live (D-056): needs a VPS from Arumi Studios and the domain added to the developer's Cloudflare account; then `docs/deployment.md` → "Go-live" steps.
- ChatGPT briefs 013 (green accent set) and 014 (dark versions) are queued in `ai/README.md`.

## Session log (newest first, keep the last 10)

- 2026-11-18 · coordinator · `phase-2` → `main` — merged #17 into `phase-2` and PR #14 into `main` (user's request); handoff: JSON import, go-live, illustrations, phase 3. Coordinator session ended.
- 2026-11-18 · coordinator · `feat/p2-branding` — JSON import for the national SD/MI list (D-057, resolves D-040); brand images + branding pack command, SMP NU Al Ma'ruf Kudus pack (logos, favicon, icon, share image, green), illustration accent setting, ChatGPT briefs 013/014, `deploy/provision.sh` + go-live checklist (D-054…D-056). Next: merge, user merges #14, go-live steps 1–9.
- 2026-11-18 · coordinator · `feat/p2-round1-answers` — interview round 1 answers: one installation per school, roles (Panitia also tests/re-registration/uniform), no-password student access + messages instead of live chat, compact analytics, domain and school name (D-050…D-053). Next: merge into `phase-2`; phase 3.
- 2026-11-18 · coordinator · `feat/p2-school-answers` — school answers applied: quota per wave (D-047), KK/occupation/income dropdowns + staff-only field mode (D-048), random number suffix (D-049); 288 tests. Next: merge into `phase-2`, user merges #14.
- 2026-11-18 · coordinator · `phase-2` — merged B (#10) after integration with A/C/D (282 tests); docs: requirements status, roadmap, architecture tables, D-044…D-046; opened PR `phase-2` → `main`; workers archived. Next: user merges, then phase 3.
- 2026-11-18 · coordinator · `phase-2` — workers resumed after the usage limit; merged D (#11), A (#12), C (#13) after local tests on the combined branch (240 tests); decisions D-039…D-043; Dapodik key question open (D-040). Next: B (#10), then PR `phase-2` → `main`.
- 2026-11-17 · coordinator · `feat/p2-foundation` — phase 2 foundation: shared migrations/models, settings, `referensi.kelola`, extension points (D-033), FPDF + PhpSpreadsheet (D-030), coordination protocol and plan (D-029…D-038). Next: workers A–D.
- 2026-11-17 · session 3 · `feat/illustrations` — illustrations 001–012 converted to WebP and installed with dark variants (`illustration()` helper); checked at 360/1280 px light and dark. Next: phase 2.
- 2026-11-17 · session 3 · `docs/chatgpt-delivery-folder` — ChatGPT's role limited to making images and opening a PR into `ai/generated/`; Claude reviews and installs (D-028). Originals 001–012 merged (PR #7). Next: install them.
- 2026-11-17 · session 3 · `feat/phase-1-finish` — Fitur page (`sistem.modul`), Riwayat Perubahan audit viewer (`audit.view`), friendly 404/500 pages with fallback, illustration prompts 008–012; D-025, D-026; merged as PR #4; painted illustration style and ChatGPT instructions `ai/CHATGPT.md` (D-027). Next: images, then phase 2.
