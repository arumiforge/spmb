# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Phase 3 — Placement test**: done and merged into `main` (PR #23; foundation #18, three roles #19, packages
B #20, A #22, C #21; 424 tests green). Module Tes: rooms, calendar with
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
| `pw/zealous-albattani-2rz2z1` | coordinator (session docker) | Integration branch for the platform round (`docs/platform-plan.md`): Docker, PHP 8.5/Node 24, design rules, Tanya Jawab, Codex; includes PR #25 | foundation done, workers running |
| `feat/plat-tanya-jawab` | worker C | Tanya Jawab page | in progress |
| `feat/plat-docker` | worker A | Docker and server | merged into the integration branch (#29) |
| `feat/plat-ui` | worker B | Lucide icons and visual rules | merged into the integration branch (#28) |
| `feat/plat-codex` | worker D | Codex briefs and identity | merged into the integration branch (#27) |
| `nty/exciting-mendel-ukwgth` | session go-live | Domain typo fix, `provision.sh` lock-out guards (D-065) | PR #25 open; merged into `pw/zealous-albattani-2rz2z1` |
| `phase-3` | coordinator | Phase 3 integration branch | merged into `main` (PR #23) |
| `nty/nifty-mendel-u2jf9s` | coordinator | SD/MI default data + foundation (#18), three roles (#19), phase 3 docs | merged into `phase-3` |
| `feat/p3-jadwal`, `feat/p3-penjadwalan`, `feat/p3-kehadiran` | workers A, B, C | Phase 3 packages | merged into `phase-3` (#22, #20, #21) |
| `phase-2` and `feat/p2-*` | coordinator, workers A–D | Phase 2 | merged into `main` (PR #14) |

## Next steps

Start a new session from `origin/main` (`spmb-session`; `composer install`, `npm ci && npm run build`,
`php spark spmb:install --demo` for local work; in cloud containers run `service mysql start` first — MySQL stops
when the container idles).

1. **Go-live on `spmb-prod` (D-056, D-065…D-068, D-076)**: after the user merges PR #26, follow `docs/deployment.md` →
   "First server": upload `deploy/server/setup.sh` with Bitvise SFTP, `sudo DOMAIN=spmbsmpnualmarufkudus.web.id
   ADMIN_USER=<name> bash setup.sh`, connect the Cloudflare tunnel (`ssh.`, `konsol.`, `staging.` behind Access), test
   Cockpit and SSH through the tunnel, give the server read access to the repository (developer's own key or token),
   clone to `/opt/apps/spmb/src`, `spmb init staging staging.<domain>`, `spmb deploy staging` (first real clean image
   build), `spmb spark staging spmb:install`, then `spmb init production <domain>`, `spmb deploy production`,
   `spmb spark production spmb:install` and `spmb spark production spmb:branding clients/smp-nu-al-maruf-kudus/branding`.
   Only two staff roles exist: create the school's Admin Sekolah, who adds Panitia accounts. Keep the direct SSH port
   open until the user decides (see Blockers).
2. **Phase 4 — Re-registration & reports** (FR-DU-01, FR-SRG-01, dashboard stats, filters, XLSX/CSV/PDF export, XLSX
   import): same protocol (`docs/coordination.md`, integration branch `phase-4`, foundation first, workers). Panitia does
   re-registration and uniform pickup (D-051, D-063); reports are for Admin Sekolah.
3. **Visual assets (Codex, D-073/D-075)**: the user runs the Codex starter prompt in `ai/README.md`, batch `svg-kit` first (015–018), then `paintings-pages`, `paintings-states`, `variants-001-012` (013/014). Review each Codex PR with `python3 ai/tools/check_assets.py` and install per `spmb-ai-image` (needs `resources/css/visual/`, a loader partial, `.tombol-daftar` on the main CTA, fallback share image).
4. **Copy sweep (D-071)**: sentence case for existing headings, buttons and labels in all `Language/id` files (for example `Situs.academicYear` "Tahun Ajaran {0}", "Daftar Sekarang", "Cek Status"), in one pass.
5. **Small follow-ups**: Indonesian "Pilih berkas" button instead of the browser's "Choose File" on the applicant page;
   use the Lucide icons `camera`, `printer`, `undo-2` (now available) on the attendance page instead of `eye`/`clipboard`/`x`; update the
   share image year each academic year.

## Blockers / waiting on

- School still to confirm (phase 3 works with any answer): test days, sessions per day, rooms and capacity; tests per wave or after all waves.
- Each Claude account needs its cloud environment set up once from `docs/cloud-environment.md` (old inline scripts fail on PHP 8.5.13).
- Check the ApexCharts v4 licence terms (revenue threshold) before launch; buy a licence or switch library if needed (D-017).
- **Decision for the user**: keep the direct SSH port open with key-only login as the last way in (the VPS has no
  provider console), or close it once the tunnel works (nothing public, but a tunnel outage locks everyone out).
- Go-live (D-056): VPS ready; `spmbsmpnualmarufkudus.web.id` still has to be added to the developer's Cloudflare account; then `docs/deployment.md` → "Go-live" steps.
- Codex batches (briefs 013–035) are queued in `ai/README.md`; waiting for the user to start Codex.

## Session log (newest first, keep the last 10)

- 2026-11-22 · coordinator (session docker) · `pw/zealous-albattani-2rz2z1` — first workers failed at environment setup (old inline script on PHP 8.3); cloud setup moved into the repo (`.claude/cloud/setup.sh`, `docs/cloud-environment.md`, D-074), the SPMB environment now runs it, Composer platform set to 8.5.13 (CI green); workers A–D restarted. Next: review worker PRs.
- 2026-11-22 · coordinator (session docker) · `pw/zealous-albattani-2rz2z1` — user's answers: Docker on the VPS with Cockpit + host cloudflared (no Proxmox), staging + production stacks on one VPS from `main`, server pulls and builds (no deploy key), public Tanya Jawab page; foundation landed (PHP ^8.5.13 / Node 24 / MySQL 8.4 in CI, 424 tests green on 8.5.13, lucide-static, `bahasa-indonesia-plain` skill, visual and chart rules, D-066…D-073, `docs/platform-plan.md`); PR #25 merged in; workers A–D started. Next: review worker PRs, integrate, PR to `main`.
- 2026-11-19 · session go-live · `nty/exciting-mendel-ukwgth` — domain corrected to `spmbsmpnualmarufkudus.web.id` (docs, branding pack, share image re-rendered); `provision.sh` keeps the SSH port (custom too) open until the tunnel runs and turns password login off only when an admin key exists; deployment doc: 127.0.0.1 ingress, real SSH port, known_hosts from the server, Bitvise via `cloudflared access tcp` (D-065). Next: user merges, go-live.
- 2026-11-18 · coordinator · `main` — PR #23 (phase 3) merged into `main` on the user's request; workers A–C done. Next: go-live, phase 4.
- 2026-11-18 · coordinator · `phase-3` — merged B (#20), A (#22), C (#21) after combined local runs (424 tests, demo install seats all applicants, pages checked at 360 px); docs: D-064, requirements FR-TEST-01…04 built, changelog, roadmap; PR `phase-3` → `main` opened. Next: user merges, go-live, phase 4.
- 2026-11-18 · coordinator · `nty/nifty-mendel-u2jf9s` → `phase-3` — only three roles (Admin Sekolah, Panitia, Siswa; D-063): removed Kepala Sekolah and Petugas roles, accounts migrated to Panitia, guide `/panduan/panitia`, tests use per-user permissions for read-only staff; workers told. Next: worker PRs.
- 2026-11-18 · coordinator · `nty/nifty-mendel-u2jf9s` → `phase-3` — national SD/MI list shipped as default data (D-058, 177,422 schools, loaded by `spmb:install` and deploys); phase 3 foundation (module Tes, D-059…D-062, `phase-3-plan.md`); workers A–C started. Next: review worker PRs.
- 2026-11-18 · coordinator · `phase-2` → `main` — merged #17 into `phase-2` and PR #14 into `main` (user's request); handoff: JSON import, go-live, illustrations, phase 3. Coordinator session ended.
- 2026-11-18 · coordinator · `feat/p2-branding` — JSON import for the national SD/MI list (D-057, resolves D-040); brand images + branding pack command, SMP NU Al Ma'ruf Kudus pack (logos, favicon, icon, share image, green), illustration accent setting, ChatGPT briefs 013/014, `deploy/provision.sh` + go-live checklist (D-054…D-056). Next: merge, user merges #14, go-live steps 1–9.
- 2026-11-18 · coordinator · `feat/p2-round1-answers` — interview round 1 answers: one installation per school, roles (Panitia also tests/re-registration/uniform), no-password student access + messages instead of live chat, compact analytics, domain and school name (D-050…D-053). Next: merge into `phase-2`; phase 3.
- 2026-11-18 · coordinator · `feat/p2-school-answers` — school answers applied: quota per wave (D-047), KK/occupation/income dropdowns + staff-only field mode (D-048), random number suffix (D-049); 288 tests. Next: merge into `phase-2`, user merges #14.
- 2026-11-18 · coordinator · `phase-2` — merged B (#10) after integration with A/C/D (282 tests); docs: requirements status, roadmap, architecture tables, D-044…D-046; opened PR `phase-2` → `main`; workers archived. Next: user merges, then phase 3.
