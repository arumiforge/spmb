# Platform round — Docker, design rules, Tanya Jawab, Codex briefs

Integration branch: `pw/zealous-albattani-2rz2z1` (coordinator session "session docker").
Protocol: `docs/coordination.md`. Decisions: D-066…D-073 (`docs/decisions.md`).
Base: `main` + PR #25 (domain `spmbsmpnualmarufkudus.web.id`, SSH lock-out guards, D-065).

## Foundation (coordinator, done before workers start)

- PHP `^8.5.13`, Node `>=24`, CI on PHP 8.5 + MySQL 8.4 + Node 24 (D-069). 424 tests green on PHP 8.5.13 / MySQL 8.4.
- `lucide-static` (ISC) added as a dev dependency (D-070); `THIRD_PARTY_NOTICES.md` updated.
- Skills: `bahasa-indonesia-plain` added (D-071); `spmb-copy` points to it; `spmb-ui` has the visual
  rules and chart rules (D-070); `CLAUDE.md` stack and skills table updated.
- Decisions D-066…D-073; this plan.

## Work packages

Workers branch from the integration branch, touch only their files and open one PR into it.
Workers never edit `docs/progress.md`, `docs/decisions.md`, `CHANGELOG.md`, `docs/requirements.md`,
`docs/roadmap.md`, `docs/architecture.md`, `CLAUDE.md`, `composer.*`, `package*.json` or
`.github/workflows/ci.yml`: put proposals in the PR description under "Needs from coordinator".

### A — Docker and server (`feat/plat-docker`)

Owns: `docker/**`, `deploy/**`, `.dockerignore`, `.github/workflows/deploy.yml` (remove it),
`docs/deployment.md`, `docs/development.md`, `.claude/skills/spmb-deploy/**`, `clients/*/README.md` (deploy lines only).

Build (D-066…D-069):
1. `docker/Dockerfile`, multi-stage: `node:24` builds assets (`npm ci && npm run build`); a PHP stage on
   `php:8.5-fpm` with intl, mysqli, gd (jpeg, webp, freetype), zip, exif, opcache settings for production;
   Composer `--no-dev --optimize-autoloader`; targets `app` (PHP-FPM) and `web` (Nginx with only `public/`).
   Exclude `flowbite/` (licensed Pro source, D-018), `node_modules`, `.git`, tests, docs, `writable/*` content.
   Run as `www-data`; no `spark optimize` (it edits `app/Config/Optimize.php`).
2. `deploy/compose.yaml`: services `db` (mysql:8.4, utf8mb4_unicode_ci, healthcheck), `app`, `web`
   (`127.0.0.1:${SPMB_PORT}:80`), named volumes `db` and `writable`, the app `.env` mounted read-only,
   `restart: unless-stopped`, log rotation. Nginx: `real_ip_header CF-Connecting-IP` from the Docker
   bridge ranges, `HTTPS on` when `X-Forwarded-Proto` is https, `client_max_body_size 12m`, deny dotfiles,
   long cache for `/build/` and `/assets/`. PHP: `upload_max_filesize 10M`, `post_max_size 12M`,
   `memory_limit 256M`, `expose_php Off`, `date.timezone Asia/Jakarta`.
3. `deploy/server/setup.sh` — one run on a fresh Ubuntu 24.04 as root, idempotent: base packages,
   unattended security updates (no auto reboot), swap if none, Docker Engine + compose plugin from
   Docker's apt repo (`live-restore`, log limits), cloudflared from Cloudflare's apt repo (host service,
   token added by the developer later), Cockpit bound to `127.0.0.1:9090` with `Origins` for the console
   hostname, an optional `ADMIN_USER` sudo account (Cockpit refuses root), ufw deny-incoming with the
   D-065 guards (keep the real SSH port(s) open until `cloudflared` runs; password login off only when a key
   exists; never close the session in use), `/opt/apps` with a port list (`/opt/apps/PORTS`).
   Must not need a provider console at any step, and must print the next steps.
4. `deploy/server/spmb` — server CLI (bash), installed by symlink to `/usr/local/bin/spmb`:
   `init <env> <domain>` (creates `/opt/apps/spmb/<env>/` with `stack.env`, the app `.env`, generated DB
   passwords and `encryption.key`, a free port from `/opt/apps/PORTS`), `deploy staging [ref]` (default
   `origin/main`), `deploy production [ref]` (default: the commit running on staging, same image, no rebuild),
   `rollback <env>`, `status`, `logs <env>`, `backup <env>` (mysqldump + `writable/uploads`, keep 14 days),
   `restore <env> <file>`, `spark <env> <args…>`. Deploy order: build → backup → `up -d` → `migrate --all`
   → `referensi:import --bawaan` → health check (HTTP 200 on `/`) → keep the last 5 image tags.
   The checkout lives in `/opt/apps/spmb/src`; git access there is set up by the developer (no key handling
   in scripts). A daily backup timer (systemd) for every initialised env.
5. Remove `deploy/provision.sh` and `deploy/activate.sh` (keep their D-065 guards in `setup.sh`), remove
   `.github/workflows/deploy.yml`.
6. Docs: `docs/deployment.md` rewritten for this layout (topology, first server, Cloudflare Tunnel public
   hostnames: domain → `http://127.0.0.1:<prod port>`, `staging.<domain>` → staging port, `konsol.<domain>` →
   Cockpit, `ssh.<domain>` → sshd; Access policies; go-live checklist for SMP NU Al Ma'ruf Kudus; server move
   = setup + restore; Bitvise via `cloudflared access tcp`). `docs/development.md`: Windows + Laragon with PHP
   8.5.13+, Node 24, Nginx, MySQL 8.4, and optional local Docker.
7. Skill `.claude/skills/spmb-deploy/SKILL.md`: how to build, deploy, roll back, back up and move servers.
8. Verify in the cloud container: `dockerd` can be started; build both images, run the stack with
   `spmb init/deploy` against a local bare clone (or a `SPMB_SRC` override), `spark spmb:install --demo`,
   load `/`, `/masuk`, `/admin` through the web port, screenshot at 360 and 1280 px; `bash -n` and
   `shellcheck` on every script. Docker Hub may answer 429: retry with backoff.

### B — Lucide icons and visual rules (`feat/plat-ui`)

Owns: `app/Helpers/ui_helper.php`, `app/Views/**`, every module's existing `Views/**` (including
`Situs/Views/jadwal.php` and `persyaratan.php`), `resources/js/**`, `resources/css/**`, a generator script
under `scripts/`, tests for the helper.

1. `icon()` returns Lucide SVGs (stroke icons, `stroke="currentColor"`, `fill="none"`, stroke width 2).
   Keep the existing names as aliases mapped to Lucide names (e.g. `school` → `school`, `warning` →
   `circle-alert`, `check` → `circle-check`, `logout` → `log-out`, `cog` → `settings`, `chart` → `chart-pie`,
   `ban` → `ban`, `inbox` → `inbox`, `grid` → `layout-grid`, `clipboard` → `clipboard-list`), and add icons the
   follow-ups need (`camera`, `printer`, `undo-2`, `upload`, `download`, `message-circle-question`…).
   A script (`npm run icons`) copies only the listed icons from `node_modules/lucide-static` into a PHP
   file, which is committed, so production does not need Node.
2. Remove every icon background (tinted square/circle behind an icon), every eyebrow (uppercase tracked
   label above a heading, including the sidebar group labels: make them sentence case, normal tracking)
   and any accent bar or accent border (D-070). Keep meters and progress bars.
3. Charts (`resources/js/charts.js` and the dashboard views): responsive options so nothing overlaps at
   360 px (see `spmb-ui` → Charts).
4. Tasteful motion: subtle entrance and hover transitions on public pages and dashboard tiles, all off
   under `prefers-reduced-motion`.
5. Playwright screenshots of the home page, sign-in, dashboard, applicant list and a chart page at 360 and
   1280 px, light and dark, before the PR. All tests green, `composer lint`, `npm run build`.

### C — Tanya Jawab page (`feat/plat-tanya-jawab`)

Owns: new files in `app/Modules/Situs/**` plus `Situs/Controllers/Situs.php`, `Config/*`, `Language/id/Situs.php`,
`Guide/`; `app/Commands/Branding.php` (load the Q&A from the pack); `clients/smp-nu-al-maruf-kudus/tanya-jawab.json`;
tests for all of it.

1. Table (Situs migration) for questions: topic, question, answer (Markdown, rendered safely with the
   existing CommonMark setup), order, published; audited changes.
2. Admin screen under "Website" (permission `situs.kelola`, already Admin Sekolah's): list grouped by topic,
   add, edit, reorder, hide; breadcrumbs, empty state, sentence-case copy (`bahasa-indonesia-plain`).
3. Public `/tanya-jawab`: topics as sections, questions as an accessible accordion, links to the real pages
   (`/persyaratan`, `/jadwal`, `/cek-status`, `/daftar`); public menu entry; works with no questions (page hidden
   from the menu). Use the Lucide icons through `icon()` (B changes the helper; names stay the same).
4. Starter questions for SMP NU Al Ma'ruf Kudus in `clients/smp-nu-al-maruf-kudus/tanya-jawab.json`, loaded by
   `spark spmb:branding <folder>` when the file is next to `branding.json` (skips when questions already exist).
   Content: registration steps, documents, waves and quota, test, cost questions answered as "ask the
   committee" unless known, contact, previous-school data, card and status check — no invented facts:
   mark unknown school facts for the school to fill in. Plain Indonesian, RASA-checked.
5. Permission tests (Admin Sekolah allowed, Panitia denied, guest), public page tests, screenshots at 360/1280 px.

### D — Codex briefs and identity (`feat/plat-codex`)

Owns: `ai/**`, `.claude/skills/spmb-ai-image/**`.

1. Switch the pipeline from ChatGPT to Codex (D-073): `ai/CODEX.md` (replaces `CHATGPT.md`: how Codex
   finds briefs, delivers files by PR into `ai/generated/`, naming, sizes, light/dark, WebP/SVG rules),
   `ai/README.md` queue, `spmb-ai-image` skill updated. Keep the existing briefs that are still useful,
   re-addressed to Codex.
2. `ai/style-guide.md`: the identity — santri, Islamic, Nahdlatul Ulama, Indonesian, Bhinneka Tunggal Ika
   (diverse regions, skin tones, clothing incl. sarung, peci, kerudung/jilbab, batik, pesantren life),
   respectful use of religious elements (no Qur'an verses or the name of Allah as decoration, no figures in
   prayer used as ornament, nothing that would be stepped on or thrown away when printed), NU green family
   with the school colour from settings, no stereotypes. Matches D-070 (no eyebrows, accent bars,
   accent borders, icon backgrounds).
3. Briefs for Codex: hero and section illustrations (home, persyaratan, jadwal, tanya jawab, cek status,
   kartu, tes), empty/error states not yet painted, a subtle geometric pattern set (SVG, tileable, light/dark,
   inspired by Islamic geometry and Indonesian textile motifs such as kawung), special buttons (primary CTA
   "Daftar sekarang" art treatment that still works as an HTML button: SVG/CSS background layers, states
   hover/focus/disabled), page dividers that are not short lines, a loading animation (Lottie-free: SVG +
   CSS, reduced-motion fallback), favicon-free generic app art. Each brief: purpose, where it is used, size,
   formats, light/dark, alt text, acceptance checks.
4. No application code changes; the coordinator installs delivered assets.

## Integration order

D (docs only) → C → B (helper change touches C's views: rebase on C if needed) → A (independent).
The coordinator then runs the full suite on PHP 8.5, `composer lint`, `npm run build`, builds the Docker
image, and opens the PR from the integration branch into `main` (it supersedes PR #25).
