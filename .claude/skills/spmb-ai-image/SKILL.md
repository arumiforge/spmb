---
name: spmb-ai-image
description: Request visual assets for SPMB (paintings, heroes, empty/error states, SVG patterns, dividers, special buttons, loading animation) by writing Codex briefs in ai/, then review and install what Codex delivers (WebP into public/assets/img, SVG originals into resources/img, CSS into resources/css). Use whenever a page needs an illustration, pattern, decorative visual, special button or animation.
---

# Visual asset workflow (Codex, D-073)

Claude never draws assets itself. It writes briefs in `ai/prompts/`; **Codex** (with repository access)
makes the assets and delivers them by pull request into `ai/generated/`; Claude reviews and installs
them. Read `ai/style-guide.md` (identity, respect rules, palette, cast, sizes) and `ai/CODEX.md`
(what Codex does) before writing a brief.

## 1. Write the brief

- Next free number: `ls ai/prompts`. File `ai/prompts/<NNN>-<name>.md`, name in Indonesian kebab-case.
- Painting → copy `ai/prompts/_template.md`; SVG/CSS (pattern, divider, button, loader) →
  `ai/prompts/_template-svg.md`. Keep the front matter keys exact: `ai/tools/check_assets.py` reads them
  (`kind`, `area`, `background`, `variants`, `files`, `status`).
- A brief must let Codex finish without asking: purpose, route and component where it is used, how big it
  shows at 360 and 1280 px, composition with the empty area, cast members, props, where the single blue
  accent goes, things to avoid, the full prompt (base style block from style guide §7, unchanged, plus one
  self-contained paragraph), files table, Indonesian alt text, acceptance checks.
- Identity (D-073): santri, Islamic, Nahdlatul Ulama, Indonesian, Bhinneka. Respect rules in style guide §2
  are strict: no Arabic script or calligraphy, the mushaf never as decoration or near the floor, no figures
  in prayer, no mosque silhouettes in patterns/dividers/printed things, no NU emblem or nine stars, no
  regional-costume parade, modest clothing. School logos and names are never painted into generic art.
- Visual rules (D-070) apply to assets and their CSS: no eyebrows, no short decorative lines, no
  coloured top/side borders, no icon backgrounds, motion off under `prefers-reduced-motion`.
- SVG never hard-codes the brand colour: patterns and dividers are `#000` masks painted with
  `var(--color-primary-*)`; inline SVG uses `currentColor`; dark mode is the `.dark` class.
- Paintings get variants `light, hijau` (D-083: on real transparency that reads on light and dark pages, no
  `-dark` copy, no plate behind images); older paintings keep their `-dark` files.
- Alt text: one short plain Indonesian sentence that says what the picture shows (`bahasa-indonesia-plain`);
  no "Gambar …"/"Ilustrasi …"; decorative SVGs are `aria-hidden="true"` with no alt. Never put words into an
  asset — every word is HTML.
- Add a queue row in `ai/README.md` (ID, brief link, batch, kind, used on, `requested`). Group briefs into
  batches that make sense as one pull request.

## 2. Codex delivers

The user starts Codex with the starter prompt in `ai/README.md`. Codex works on `art/codex-<date>-<batch>`,
adds files only under `ai/generated/` (masters, `install/`, `demo/`), sets the delivered statuses to
`generated`, runs `python3 ai/tools/check_assets.py` and opens one pull request into `main` without
merging. Its commits must be authored by Arumi Studios with no AI attribution (commit-policy CI).

## 3. Review (Claude)

- Find it: `git fetch origin` and look for `origin/art/codex-*`, or list open pull requests.
- Run `python3 ai/tools/check_assets.py <IDs>` on the branch.
- **Look at every file** (Read the WebP/PNG; open `ai/generated/demo/index.html` with Playwright at 360 and
  1280 px, light/dark, blue/green, and with reduced motion). Check against the brief and style guide:
  painted not 3D; no text or letter-like marks (zoom in); respect rules; empty areas empty; real alpha;
  variants share one crop box; SVG tiles seamless; button contrast ≥ 4.5:1 and visible focus.
- Ask for fixes as a pull request comment with one line per file (what is wrong, what to change). Do not
  edit Codex's files yourself, except trivial SVG minification.
- When approved, the user (or the coordinator) merges the Codex pull request.

## 4. Install (Claude, own branch and pull request)

- Copy the WebP files `ai/generated/install/img/<area>/*.webp` → `public/assets/img/<area>/`, the SVG files
  `ai/generated/install/img/<area>/*.svg` → `resources/img/<area>/` (originals; `npm run build:img` writes the
  minified public copies, D-086 — never put an SVG straight into `public/assets/img/`), and
  `ai/generated/install/css/*.css` → `resources/css/visual/` (import them in `resources/css/app.css`),
  then `npm run build`.
- Paintings: `illustration('<area>/<name>', $alt, $classes)` in `app/Helpers/ui_helper.php` already picks
  `-hijau` (and older `-dark`/`-dark-hijau`) when the files exist. Painted icons: `art_icon()`; empty states:
  the unique `'image'` with `'fallback'`, alt text in `App.altStates`. Put the alt text in the module's language file.
- SVG/CSS: use the class names from the brief's CSS contract; decorative elements get `aria-hidden="true"`;
  the loader is inlined in a partial with `role="status"` and HTML text.
- Screenshots of every changed page at 360 and 1280 px, light and dark, before the pull request.
- Set the status to `done` in the brief and in the queue. Keep masters and the old PNG originals in
  `ai/generated/` as the source for re-exports.
