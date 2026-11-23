# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Platform round**: done and merged into `main` (PR #26, 2026-11-23; supersedes #25, which can be closed). Docker
on the VPS (staging + production, server pulls and builds, Cockpit + cloudflared on the host), PHP 8.5.13 / Node 24 /
MySQL 8.4, Lucide icons and visual rules, public Tanya Jawab page, Codex briefs, cloud environment setup in the repo
(D-066…D-076, `docs/platform-plan.md`). 442 tests green on PHP 8.5.13.

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
| `pw/eager-archimedes-s119yf` | session go-live 2 | Go-live support: environment check, server rehearsal, docs | in progress |
| `pw/zealous-albattani-2rz2z1` | coordinator (session docker) | Integration branch for the platform round (`docs/platform-plan.md`) | merged into `main` (PR #26) |
| `feat/plat-tanya-jawab` | worker C | Tanya Jawab page | merged into the integration branch (#30) |
| `feat/plat-docker` | worker A | Docker and server | merged into the integration branch (#29) |
| `feat/plat-ui` | worker B | Lucide icons and visual rules | merged into the integration branch (#28) |
| `feat/plat-codex` | worker D | Codex briefs and identity | merged into the integration branch (#27) |
| `nty/exciting-mendel-ukwgth` | session go-live | Domain typo fix, `provision.sh` lock-out guards (D-065) | in `main` through PR #26; PR #25 can be closed |
| `phase-3` | coordinator | Phase 3 integration branch | merged into `main` (PR #23) |
| `nty/nifty-mendel-u2jf9s` | coordinator | SD/MI default data + foundation (#18), three roles (#19), phase 3 docs | merged into `phase-3` |
| `feat/p3-jadwal`, `feat/p3-penjadwalan`, `feat/p3-kehadiran` | workers A, B, C | Phase 3 packages | merged into `phase-3` (#22, #20, #21) |
| `phase-2` and `feat/p2-*` | coordinator, workers A–D | Phase 2 | merged into `main` (PR #14) |

## Next steps

0. PR #26 is merged; the domain is active on Cloudflare (go-live checklist step 1 done). Go-live continues at
   checklist step 2 (`setup.sh` on `spmb-prod`). Server flow rehearsed in a cloud container on 2026-11-23 with
   `SPMB_ROOT` (clean image build, `init`/`deploy` staging, `spmb:install`, branding, `--demo` refused, production
   promote, backup, restore, production-backup-on-staging refused, ports on 127.0.0.1 only): all passed. In cloud
   containers the image build needs the sandbox proxy CA in the build (`/root/.ccr/README.md`); the VPS does not.
   The school fills in the 4 hidden Tanya Jawab answers (`[ISI SEKOLAH: …]`: costs, contacts) in Website → Tanya jawab.

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
   Only two staff roles exist: create the school's Admin Sekolah, who adds Panitia accounts. The direct SSH port stays open,
   key-only (D-077): add the key, sign in with it, run `setup.sh` again to switch password login off.
2. **Phase 4 — Re-registration & reports** (FR-DU-01, FR-SRG-01, dashboard stats, filters, XLSX/CSV/PDF export, XLSX
   import): same protocol (`docs/coordination.md`, integration branch `phase-4`, foundation first, workers). Panitia does
   re-registration and uniform pickup (D-051, D-063); reports are for Admin Sekolah.
3. **Visual assets (Codex, D-073/D-075)**: batch `svg-kit` (015–018) installed (patterns, dividers, `.tombol-daftar`, loaders;
   home hero and the registration buttons). Still to wire: the loader (`.memuat`, `role="status"`) where content loads
   (charts, school importer, card generation, `/cek-status`), the busy state of submit buttons, patterns on `/masuk`,
   the card header and the dashboard welcome band. Next Codex batches: `paintings-pages`, `paintings-states`,
   `variants-001-012` (013/014); review each with `python3 ai/tools/check_assets.py` and install per `spmb-ai-image`.
4. **Copy sweep (D-071)**: sentence case for existing headings, buttons and labels in all `Language/id` files (for example `Situs.academicYear` "Tahun Ajaran {0}", "Daftar Sekarang", "Cek Status"), in one pass.
5. **Small follow-ups**: Indonesian "Pilih berkas" button instead of the browser's "Choose File" on the applicant page;
   use the Lucide icons `camera`, `printer`, `undo-2` (now available) on the attendance page instead of `eye`/`clipboard`/`x`; update the
   share image year each academic year.

## Blockers / waiting on

- School still to confirm (phase 3 works with any answer): test days, sessions per day, rooms and capacity; tests per wave or after all waves.
- Each Claude account needs its cloud environment set up once from `docs/cloud-environment.md` (old inline scripts fail on PHP 8.5.13).
- Check the ApexCharts v4 licence terms (revenue threshold) before launch; buy a licence or switch library if needed (D-017).
- Go-live (D-056): staging and production run `main` on `spmb-prod`; staging, `konsol.` and `ssh.` behind Access; off-site
  backups to R2 set up. Left: Admin Sekolah account (handed over in person), the school fills in contacts, waves, documents
  and the 4 hidden Tanya Jawab answers; external uptime monitor; 2FA on Cloudflare/GitHub/email; Cloudflare rate limiting on
  `/masuk` and `/daftar`.
- Codex batches (briefs 013–035) are queued in `ai/README.md`; waiting for the user to start Codex.

## Session log (newest first, keep the last 10)

- 2026-11-23 · session go-live 2 · `pw/eager-archimedes-s119yf` — staging and production live on `spmb-prod` (04b2ccc), staging behind Access, Laragon exposure removed; `spmb domain` (#40); off-site backups to Cloudflare R2 with restic (D-079). Off-site setup done on the server. Next: Admin Sekolah account, school data, uptime monitor.
- 2026-11-23 · session go-live 2 · `pw/eager-archimedes-s119yf` — `setup.sh` waits for first-boot updates (#36, #37), per-repo deploy key docs (#38), light theme by default (D-078), Codex `svg-kit` installed; server: setup, tunnel, key-only SSH, clone done. Next: staging check, production, Admin Sekolah.
- 2026-11-23 · session go-live 2 · `pw/eager-archimedes-s119yf` — new account's cloud environment checked (442 tests green); domain on Cloudflare confirmed; full server flow rehearsed in the container (all steps passed). Next: user runs `setup.sh` on `spmb-prod`, then tunnel.
- 2026-11-23 · coordinator (session docker) · `pw/zealous-albattani-2rz2z1` — PR #32 (example env files) merged; docs refreshed to the current state: README, roadmap (phase 1 done, platform round row, phase 6 partly done), architecture (stack, tables, visual rules), requirements (FR-WEB-03, NFR-02/03/08), module index, third-party server images, CLAUDE.md roles and commands, coordination notes. Next: go-live, Codex `svg-kit`.
- 2026-11-23 · coordinator (session docker) · `main` — CI green on all checks; PR #26 merged into `main` on the user's request. Next: go-live on `spmb-prod` (`docs/deployment.md` → First server), Codex `svg-kit` batch.
- 2026-11-23 · coordinator (session docker) · `pw/zealous-albattani-2rz2z1` — user decided: direct SSH port stays open, key-only (D-077); `setup.sh`, deployment guide (Bitvise key steps, no fail2ban on the NAT VPS), deploy skill and CLAUDE.md updated. Next: user merges #26, go-live.
- 2026-11-22 · coordinator (session docker) · `pw/zealous-albattani-2rz2z1` — merged D (#27 Codex briefs, AGENTS.md, D-075), B (#28 Lucide, visual rules, motion, charts), A (#29 Docker, setup.sh, `spmb` CLI, D-076), C (#30 Tanya Jawab, menu fix); combined run 442 tests green, lint clean, build OK, pages checked at 360/1280 px; Docker web image and PHP base build here (Composer download blocked only by this container's proxy). Next: user merges #26, SSH port decision, go-live.
- 2026-11-22 · coordinator (session docker) · `pw/zealous-albattani-2rz2z1` — first workers failed at environment setup (old inline script on PHP 8.3); cloud setup moved into the repo (`.claude/cloud/setup.sh`, `docs/cloud-environment.md`, D-074), the SPMB environment now runs it, Composer platform set to 8.5.13 (CI green); workers A–D restarted. Next: review worker PRs.
- 2026-11-22 · coordinator (session docker) · `pw/zealous-albattani-2rz2z1` — user's answers: Docker on the VPS with Cockpit + host cloudflared (no Proxmox), staging + production stacks on one VPS from `main`, server pulls and builds (no deploy key), public Tanya Jawab page; foundation landed (PHP ^8.5.13 / Node 24 / MySQL 8.4 in CI, 424 tests green on 8.5.13, lucide-static, `bahasa-indonesia-plain` skill, visual and chart rules, D-066…D-073, `docs/platform-plan.md`); PR #25 merged in; workers A–D started. Next: review worker PRs, integrate, PR to `main`.
- 2026-11-19 · session go-live · `nty/exciting-mendel-ukwgth` — domain corrected to `spmbsmpnualmarufkudus.web.id` (docs, branding pack, share image re-rendered); `provision.sh` keeps the SSH port (custom too) open until the tunnel runs and turns password login off only when an admin key exists; deployment doc: 127.0.0.1 ingress, real SSH port, known_hosts from the server, Bitvise via `cloudflared access tcp` (D-065). Next: user merges, go-live.
