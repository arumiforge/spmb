# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Admin round (D-098…D-109)** on `igi/gifted-davinci-j917i9`: document examples + SK Aktif, setup checklist, server page,
folding cards, one-column settings with "Isi halaman" rail, wider sidebar, news ticker, home popup, editable footer
links + social icons, visitor statistics (module Analitik), step-by-step registration with the guardian question and
consent, home timeline, complete demo applicant, logo-redirect fix. Merged into `main` as PR #56. Round two (D-110 Tanya Asisten, D-111 region list +
Statistik) merged on the same branch, PR next.

**Public polish round (D-090…D-095)**: merged into `main` (PR #50). Codex paintings 019–027 and 035 installed (019, 020, 027
repainted for D-089; 020 and 027 still need a slot in the views), "Cara mendaftar" art slots (briefs 046–048 rewritten, 072 new), map preview without an
embed, footer/top bar changes, official name "Sistem Penerimaan Murid Baru (SPMB)" (migration), HTML minifier,
dashboard: Pengaturan in the bottom bar, "Lihat halaman depan", aligned side-by-side fields.

**UI round (D-080…D-088, `docs/ui-round-plan.md`)**: done and merged into `main` (PR #47, 2026-11-24) with workers A–D
(465 tests green on PHP 8.5.13). Paintings for the new art slots wait for Codex (briefs 036–071).

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
| `pw/peaceful-tesla-bjooc9` | session server-flow | Server flow (D-112) merged (#59); now: pointer to Fondasi (shared repo `arumiforge/server` → `fondasi`) | PR open |
| `igi/gifted-davinci-j917i9` | session admin-site | Admin round (D-098…D-109), integration branch; round one merged (#56); round two: F (region list), G (Asisten), H (Statistik) merged | PR open |
| `nty/intelligent-curie-pb1lv6` | session guides | Home map/contact layout (D-096), staff guides in the dashboard + in-page search on Panduan and Tanya Jawab (D-097) | PR open |
| `pw/sleepy-hopper-d0a7h5` | session polish | Public polish round (D-090…D-095), Codex #49 and `langkah`, PR #43 notes | #50, #51 merged; follow-up PR open |
| `feat/ui-codex` | worker A (ui-round) | Codex briefs 036–071 (`docs/ui-round-plan.md` A) | merged into the integration branch (#42) |
| `feat/ui-minify` | worker B (ui-round) | Minified assets, SVG originals in `resources/img/` (B) | merged into the integration branch (#44) |
| `feat/ui-admin` | worker C (ui-round) | Dashboard full-width pass, AdminLayoutTest (C) | merged into the integration branch (#45) |
| `feat/ui-publik` | worker D (ui-round) | Public pages pass, home/settings tests (D) | merged into the integration branch |
| `nty/vigilant-wozniak-lvnhku` | coordinator (session ui-round) | Integration branch of the UI round. Foundation: email-or-username login, mobile bottom bar, right drawer, top bar, hero slideshow, three.js icons, date picker, folds/TOC, map + contact, full-width admin, Codex briefs 036+ (D-080…D-088) | merged into `main` (PR #47) |
| `pw/eager-archimedes-s119yf` | session go-live 2 | Go-live support: environment check, server rehearsal, docs | notes carried into `main`; PR #43 can be closed |
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

0000000. **Fondasi** (shared rules/data/skills, `arumiforge/server`, to be renamed `fondasi`; its decisions F-001…F-008):
   server scripts, app contract, settings/storage/Turnstile standards and the region/school data now live there too.
   Later in SPMB: import `wilayah`/`sekolah` via `fondasi-sync`, use Fondasi's `setup.sh`/`check.sh`, copy
   `bahasa-indonesia-plain` with `fondasi-sync skill`, add Turnstile and S3 storage per its `docs/settings.md` and
   `docs/storage.md`.
000000. **Server flow (D-112)**: merged (PR #59). On `spmb-prod`: `spmb deploy staging` (moves the
   checkout to the new commit, so `spmb check` and `spmb up` exist), then `spmb check` — expected state S9 with the earlier-setup marker note;
   run `setup.sh` once more (same variables) to write `/opt/apps/.server-setup` (its `daemon.json` is unchanged, so Docker
   is not restarted). Not yet rehearsed end to end on a VM: `setup.sh` on a server already running Docker containers and
   a public nginx (logic unit-tested: `daemon.json` merge, listener and published-port detection).
   First real run of `check.sh` on `spmb-prod` (2026-11-25): S9, both environments 200, Access on staging; a reboot was
   pending, and a third stack `milklife-staging` runs there (record its port in `/opt/apps/PORTS`, check.sh now flags it).

00000. **Admin round**: round one merged (#56). Round two (region list, Statistik, Tanya Asisten) in the next PR. After merge also: `spmb spark production referensi:cocokkan-alamat --dry-run` then without `--dry-run` (one-time), Super Admin adds the AI connection and switches AI on, Admin Sekolah switches the widget on. Codex batches `asisten-chat` and `kosong-states-2` (073–079) are queued; when 074 lands, copy it into the client pack as `asisten.webp`. After merge: `spmb deploy staging` (migrations: has_guardian, document examples,
   SK Aktif, site_news, analytics tables), check `/`, `/daftar`, `/admin`, Website pages, then production; run
   `spmb spark production spmb:branding clients/smp-nu-al-maruf-kudus/branding` once more to install the SK Aktif
   example. User to confirm two D-107 details (guardian name always required with "Ada wali"; explicit consent box).
   Follow-ups: per-parent "masih hidup / sudah meninggal"; referrer/device totals in Analitik (D-053); Codex
   empty-state paintings `kosong-berita`, `kosong-tautan`, `kosong-pengunjung`; module commands vs Composer `App\`
   mapping (command discovery can include a module command twice in one test run). Languages: translations for
   occupation/income lists and `Tes.instructions`; the client pack's starter Q&A in English/Arabic.
   Discussed with the user, waiting for answers: "Statistik" module (region list for applicant addresses, charts of
   parent occupation/income etc., later test-score analysis) and an AI chatbot limited to SPMB (OpenAI-compatible
   providers, rotation/failover; questions: primary provider + paid key, where shown, WhatsApp handover, daily limit).

0000. **Guides and contacts (D-096, D-097)**: after the PR is merged, `spmb deploy staging`, check `/` (map on the right,
   below the contacts on phones), `/tanya-jawab` and `/panduan/calon-murid` (search box), `/admin/panduan` as Panitia and
   Admin Sekolah, then `spmb deploy production`. No migration.
000. **Public polish round**: after the PR is merged, `spmb deploy staging` (runs the tagline migration), check, then
   `spmb deploy production`. Load the updated branding pack on production
   (`spmb spark production spmb:branding clients/smp-nu-al-maruf-kudus/branding`: new tagline and share image; production
   still shows no address or map) and fill in the address, WhatsApp and email in Identitas Sekolah. PR #49 (Codex) is merged
   through this branch. Codex `langkah` (046–048 + 072) is installed. Give 020 (school character) and 027 (test pages) a slot in the views.
00. **UI round** (merged, PR #47): start Codex with the starter prompt in
   `ai/README.md` (home art first: `tombol-seni`, `ikon-beranda`, `langkah`, `slide-beranda`, then `kosong-states`) and install
   each batch per `spmb-ai-image` (WebP → `public/assets/img/`, SVG → `resources/img/`); when 049 lands, remove the interim
   `.tombol-seni` / `.nav-bawah-tengah-bulat` blocks in `resources/css/komponen.css`. On the server: `spmb deploy staging`,
   check, promote; load the school's map/slogan with `spmb spark production spmb:branding clients/smp-nu-al-maruf-kudus/branding`
   (or fill Identitas Sekolah by hand) and add the social media links there.
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
- Codex: `ikon-beranda`, `tombol-seni`, `slide-beranda`, `kosong-states`, `paintings-states` and 013/014 are queued in `ai/README.md`; waiting for the user to start Codex.

## Session log (newest first, keep the last 10)

- 2026-11-25 · session server-flow · `pw/peaceful-tesla-bjooc9` — PR #59 merged; first `check.sh` on `spmb-prod` (S9); shared repo created (interview F-001…F-008) and SPMB points to it from `CLAUDE.md`. Next: rename to `fondasi`, switch SPMB's data/scripts to Fondasi.
- 2026-11-25 · session server-flow · `pw/peaceful-tesla-bjooc9` — D-112: read-only `check.sh` (states S0…S9 + next command), `setup.sh` for servers already running Docker/other services (keeps packages, merges daemon.json, no restart while containers run, `ALLOW_PORTS`/`FIREWALL`, marker file), `spmb up` and `spmb check`, `docs/server-playbook.md`, `deploy/templates/app.compose.yaml`. Next: merge, run `spmb check` on `spmb-prod`.
- 2026-11-25 · session admin-site · `igi/gifted-davinci-j917i9` — PR #56 merged (CI green); round two with workers F (region list + address picker), G (Tanya Asisten), H (Statistik, also fixed switched-off module routes); Codex briefs 073–079. Next: PR for round two, deploy, one-time address matching, AI connection setup.
- 2026-11-25 · session admin-site · `igi/gifted-davinci-j917i9` — admin round D-098…D-109 with workers A–E: logo 304 redirect fix, server page, setup checklist, folding cards, one-column settings + rail, Berkas examples + SK Aktif, Analitik, ticker/popup/footer links, registration stepper + guardian + consent, home timeline, complete demo applicant; deploy clears cache. Next: merge E (three languages), full suite, PR.
- 2026-11-24 · session guides · `nty/intelligent-curie-pb1lv6` — home "Lokasi dan kontak": contacts card left, one map right / below on phones, map caption + directions button (D-096); staff guides moved to `/admin/panduan` (own role, Super Admin all; old public links redirect), search box inside Panduan and Tanya Jawab (D-097); 475 tests green. Next: merge, deploy staging, check, production.
- 2026-11-24 · session polish · `pw/sleepy-hopper-d0a7h5` — PR #51 merged (019/020/027 repainted); Codex `langkah` batch reviewed and installed (046–048 scenes, 072 card backgrounds); PR #43's go-live notes carried into this file. Next: deploy, load branding on production, school data, uptime monitor.
- 2026-11-24 · session polish · `pw/sleepy-hopper-d0a7h5` — checked production (was on 04b2ccc, then on #47 after the user's deploy); reviewed Codex #49 (019–027 and 035 installed after Codex repainted 019, 020, 027 for D-089); D-090…D-095: step-card art and briefs, map preview, footer/top bar, SPMB name + migration, HTML minifier, dashboard bottom bar Pengaturan and site link, subgrid form alignment; 471 tests green. PR #50 merged. Next: deploy, load branding on production, Codex `langkah` batch.
- 2026-11-24 · coordinator (session ui-round) · `nty/vigilant-wozniak-lvnhku` — UI round: email-or-username sign-in, mobile bottom bar (raised middle button without text), right drawer, top bar, slogan/map/social settings, hero slideshow with uploads, art buttons and painted-icon slots with three.js, date picker, folds and contents highlight, full-width admin; workers A (Codex briefs 036–071, #42), B (minified SVGs, #44), C (dashboard pass, #45), D (public pages, tests) merged; cloud setup script moved to packages.sury.org; 465 tests green. Next: Codex batches. User merged PR #47; CI additions (Docker build check, dependency audit, server-side staging auto-deploy) offered and declined for now.
- 2026-11-23 · session go-live 2 · `pw/eager-archimedes-s119yf` — staging and production live on `spmb-prod` (04b2ccc), staging behind Access, Laragon exposure removed; `spmb domain` (#40); off-site backups to Cloudflare R2 with restic (D-079). Next: `spmb offsite setup` on the server, Admin Sekolah account, school data.
- 2026-11-23 · session go-live 2 · `pw/eager-archimedes-s119yf` — `setup.sh` waits for first-boot updates (#36, #37), per-repo deploy key docs (#38), light theme by default (D-078), Codex `svg-kit` installed; server: setup, tunnel, key-only SSH, clone done. Next: staging check, production, Admin Sekolah.
