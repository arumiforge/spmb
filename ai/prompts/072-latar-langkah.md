---
id: 072
name: latar-langkah
kind: svg
batch: langkah
files: img/langkah/latar-langkah-1.svg, img/langkah/latar-langkah-2.svg, img/langkah/latar-langkah-3.svg, css/latar-langkah.css
status: generated
---

# 072 — latar-langkah (art backgrounds of the "Cara mendaftar" cards)

## Purpose

The three step cards on the home page get their own art background, so the section feels made for this
school instead of three plain white boxes (D-090). Together with the step paintings 046–048 they form one set.

## Where it is used

Home page (`/`), section "Cara mendaftar": `<li class="kartu-langkah kartu-langkah-1">` (and `-2`, `-3`).
The card is white (`gray-900` in dark mode) with a 1 px border and rounded corners (1rem). Inside, from top
to bottom: the painting (046–048, about 300 × 225 px), "Langkah N", the title and one sentence. Cards are
stacked on phones (about 328 px wide, 420–460 px tall) and three side by side from `md` (about 300–390 px wide).
An interim version already ships in `resources/css/komponen.css` (`.kartu-langkah`, using the kawung pattern);
this brief replaces it.

## Design

- One SVG per card, used as a **mask** (`#000` shapes on transparency, never a colour), painted by CSS with
  `var(--color-primary-*)`, so it follows the school colour and dark mode. viewBox `0 0 400 480`,
  `preserveAspectRatio="none"` is **not** allowed: the CSS uses `mask-size: cover` with `mask-position: top`.
- Composition per card: a soft wavy band across the top third (where the painting sits) that fades out with a
  few organic brush-like edges, plus one botanical corner motif at the bottom right, drawn with simple
  filled shapes (no hairlines thinner than 2 px at 400 px wide):
  - card 1: frangipani (kamboja) flowers and two leaves;
  - card 2: mango leaves with one small mango;
  - card 3: jasmine (melati) sprig.
- Leave the text area (the middle and lower left, about 60 % of the card) free of shapes so text keeps full contrast.
- Style guide §5: Nusantara and pesantren feel, calm, hand-cut-paper look. No mosque silhouettes, no Arabic
  script, no NU emblem or nine stars, no stars, no lines that look like letters.
- Visual rules (D-070): no coloured top or side borders, no short decorative lines, no eyebrows.
- Each file ≤ 6 KB after SVGO (the build minifies a copy, D-086; keep the original readable).

## Files

| File (`ai/generated/install/…`) | What |
| --- | --- |
| `img/langkah/latar-langkah-1.svg` | Card 1 mask: top band + frangipani corner |
| `img/langkah/latar-langkah-2.svg` | Card 2 mask: top band + mango leaf corner |
| `img/langkah/latar-langkah-3.svg` | Card 3 mask: top band + jasmine corner |
| `css/latar-langkah.css` | The contract below; replaces the interim `.kartu-langkah` block in `resources/css/komponen.css` |

## CSS contract

- `.kartu-langkah` — `position: relative; isolation: isolate; overflow: hidden`. A `::before` layer
  (`position: absolute; inset: 0; z-index: -1; pointer-events: none`) painted with
  `background-color: var(--kartu-langkah-warna)` and masked with `var(--kartu-langkah-gambar)`
  (`mask-size: cover; mask-position: top center; mask-repeat: no-repeat`).
- `.kartu-langkah-1/-2/-3` set `--kartu-langkah-gambar: url("/assets/img/langkah/latar-langkah-N.svg")`.
- Colours: light `--kartu-langkah-warna: var(--color-primary-100)` at full opacity; card 2 uses
  `--color-primary-50` plus a second layer `::after` with the corner motif in `--color-primary-200` (optional).
  Dark mode (`.dark` class, not the media query): `color-mix(in oklab, var(--color-primary-700) 35%, transparent)`.
- Hover (the card is not a link): none. Motion: none; nothing to switch off under `prefers-reduced-motion`.
- Print (`@media print`): hide the `::before` layer.

## Alt text / accessibility

Decorative only: the layer is a pseudo-element, so there is nothing to announce and no alt text. Text
contrast inside the card must stay ≥ 4.5:1 in light and dark mode with the band behind the painting.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 072` passes
- [ ] Demo page shows the three cards in light/dark and blue/green at 360 and 1280 px without overflow
- [ ] SVGs are `#000` masks only (no `fill` colours other than black, no `<text>`, no embedded images)
- [ ] The text area stays free of shapes; text contrast ≥ 4.5:1 in both themes
- [ ] Corner motifs are clearly frangipani, mango leaves and jasmine; nothing letter-like or religious
