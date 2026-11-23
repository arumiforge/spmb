---
id: 049
name: tombol-seni
kind: svg
batch: tombol-seni
files: img/tombol/tombol-seni-bentuk.svg, img/tombol/tombol-seni-tepi.svg, img/tombol/lencana-tengah.svg, css/tombol-seni.css
status: requested
---

# 049 — tombol-seni (art buttons and the raised bottom-bar badge)

## Purpose

The home page buttons with a unique function ("Cek Status", "Lihat Persyaratan", "Baca Panduan Pendaftaran")
get a crafted, organic shape instead of a plain box (D-082): a leaf-like outline with a fine tumpal/kawung
edge, and a painted icon (036–041) rising out of the left end. The raised "Daftar" button in the middle of the
public mobile bottom bar gets a matching round art badge with the painted backpack (041) inside. Both stay real,
accessible HTML links. Uses 015 (`pola-kawung.svg`); sits next to 017 (`.tombol-daftar`) and must look like the
same family, one step quieter.

## Where it is used

- Home hero (`app/Views/home.php`), next to the "Daftar sekarang" button (017): up to three `.tombol-seni`
  links. Full width on phones (< 640 px), inline from 640 px.
- Public bottom bar on phones (`app/Views/partials/bottom_nav.php`, D-081): the raised middle item
  `.nav-bawah-tengah-bulat` (68 px circle, brand colour, raised 28 px above the bar).

The interim CSS lives in `resources/css/komponen.css` (`.tombol-seni …`, `.nav-bawah-tengah-bulat …`); read it
first. Your `tombol-seni.css` **replaces** those two blocks (Claude removes them when installing), so keep every
class name and custom property below and everything the interim CSS already does (fallback icon, 640 px rule,
focus, reduced motion).

Markup the app uses (the text is real HTML, in Indonesian, written by Claude):

```html
<a href="/cek-status" class="tombol-seni" data-ikon-3d-area>
  <span class="tombol-seni-gambar"><span class="ikon-3d h-full w-full" data-ikon-3d aria-hidden="true"><picture>…painted icon…</picture></span></span>
  <span class="tombol-seni-teks">Cek Status</span>
</a>
<a href="/persyaratan" class="tombol-seni tombol-seni-lembut" data-ikon-3d-area>…</a>

<!-- until the painting exists, art_icon() renders the Lucide fallback instead: -->
<span class="tombol-seni-gambar"><span class="ikon-cadangan …" aria-hidden="true"><svg class="lucide …">…</svg></span></span>

<!-- bottom bar, middle item -->
<a href="/daftar" class="nav-bawah-tengah is-aktif" aria-current="page">
  <span class="nav-bawah-tengah-bulat" aria-hidden="true"><img src="/assets/img/ikon/ikon-daftar.webp" alt=""> <!-- or a Lucide svg --></span>
  <span class="nav-bawah-label">Daftar</span>
</a>
```

## Design

**Button shape (`tombol-seni-bentuk.svg`).** A horizontal leaf/pebble outline: rounded, slightly asymmetric
(fuller on the left where the icon sits, tapering a little to the right), drawn as a `#000` mask that CSS
stretches over the whole button with `mask-size: 100% 100%`. It must still look right from 220×52 px to
360×64 px (no visible distortion of the curve; keep the shape simple). `viewBox="0 0 240 60"`,
`preserveAspectRatio="none"`. Exposed as `--tombol-seni-bentuk` so the app can swap it.

**Edge (`tombol-seni-tepi.svg`).** A fine repeating border strip of small tumpal triangles alternating with tiny
kawung ovals, `#000` mask, tile `viewBox="0 0 24 8"`, painted along the inside of the bottom edge only (one strip,
5–6 px high) in `--tombol-seni-garis`, at about 45 % opacity. Quiet texture, not a line under a heading. Not on
the top or side edges (D-070: no coloured top or side borders).

**Background.** As now: `--tombol-seni-latar` with a soft light radial highlight at the upper left, the kawung
tile (015) at 7 % opacity, a full 1.5 px inner outline in `--tombol-seni-garis` that follows the leaf shape
(draw it as a second, slightly inset copy of the shape mask, or with `drop-shadow`, not with `border`).

**Icon slot.** `.tombol-seni-gambar` is 68 px square, positioned so the painted icon overlaps the left end and
rises about 12 px above the button's top edge; the text keeps `padding-inline-start: 4.25rem`. When the slot
holds `.ikon-cadangan` (no painting yet) the Lucide icon sits inside the button at 24 px, as in the interim CSS.

**Badge (`lencana-tengah.svg`).** A round frame for the raised bottom-bar item: a circle whose edge is a ring of
eight soft rounded petals (a frangipani-like rosette on an eight-point grid, the same family as 017's
`tombol-ornamen.svg`), `#000` mask, `viewBox="0 0 72 72"`. CSS paints it in white at 90 % on light pages and
`--color-gray-800` on dark pages as the ring between the bar and the brand-coloured disc (replacing today's
plain 5 px white `box-shadow` ring). Inside, the disc keeps the brand gradient and the kawung texture; the painted
`ikon-daftar` (041) sits centred at 48–52 px, or the Lucide icon at 28 px in white.

Avoid: mosque or dome silhouettes, arches that read as a mihrab, stars in a ring (no nine-star arrangements),
anything from style guide §2; parang motifs; uppercase or letter-spaced text; a coloured top or side border;
a tinted circle behind the Lucide fallback icon.

## Files

| File (`ai/generated/install/…`) | What |
| --- | --- |
| `img/tombol/tombol-seni-bentuk.svg` | leaf/pebble button shape, mask (`#000` on transparency), `viewBox="0 0 240 60"`, `preserveAspectRatio="none"` |
| `img/tombol/tombol-seni-tepi.svg` | tumpal/kawung edge tile, mask, `viewBox="0 0 24 8"` |
| `img/tombol/lencana-tengah.svg` | eight-petal ring for the bottom-bar badge, mask, `viewBox="0 0 72 72"` |
| `css/tombol-seni.css` | `.tombol-seni`, `.tombol-seni-lembut`, `.tombol-seni-gambar`, `.tombol-seni-teks`, `.nav-bawah-tengah-bulat` and all states |

Each SVG ≤ 6 KB; only `#000` fills (the checker treats everything in `img/tombol/` as a mask).

## CSS contract

- Custom properties (keep names): `--tombol-seni-latar`, `--tombol-seni-teks`, `--tombol-seni-garis`, new
  `--tombol-seni-bentuk` (default `url("/assets/img/tombol/tombol-seni-bentuk.svg")`). Values only from
  `var(--color-primary-*)`, the grey scale and white; never a hard-coded brand colour.
- `.tombol-seni` (primary-tinted) and `.tombol-seni-lembut` (white / grey-800 in dark) as in the interim CSS;
  text contrast ≥ 4.5:1 in light and dark for blue `#2563EB` and green `#005C2F`.
- Min height 56 px; touch target ≥ 44 px; full width under 640 px, centred content.

| State | `.tombol-seni` look |
| --- | --- |
| Rest | leaf shape, inner outline, bottom edge strip, soft shadow (as now) |
| Hover | lifts 2 px, shadow grows, the edge strip slides 6 px sideways, icon tilts −6° and scales 1.06 (≤ 250 ms) |
| Focus visible | 3 px outline in `var(--color-primary-500)` with 3 px offset, following the box (outlines cannot follow a mask; that is fine), visible on white and `#111827` |
| Active | moves down 1 px, shadow smaller |
| Disabled (`[aria-disabled="true"]`) | grey-100 / grey-800 background, grey-500 text, no texture, no edge strip, icon at 50 % opacity and greyscale, `cursor: not-allowed` |
| Dark (`.dark`) | values from the interim CSS; edge strip in `--color-primary-700` |
| Forced colours | plain `ButtonFace` box with a `ButtonText` 1 px border, no masks, icon still visible |

| State | `.nav-bawah-tengah-bulat` look |
| --- | --- |
| Rest | petal ring (white / grey-800 in dark) around the brand disc, soft shadow, slow "breathing" shadow loop (≥ 3 s) only on light pages, as now |
| Active page (`.nav-bawah-tengah.is-aktif`) | ring gains a second thin ring in `var(--color-primary-200)` (`--color-primary-700` in dark) |
| Pressed | scale 0.94 |
| Focus visible | the existing outline on `.nav-bawah-tengah` (do not remove it) |

Every transition and animation inside `@media (prefers-reduced-motion: no-preference)`. Wrapped in
`@layer components { … }`, plain CSS, `-webkit-mask-*` next to `mask-*`.

## Alt text / accessibility

All shapes, edges and the badge ring are pseudo-elements or masks: invisible to screen readers. The button label
is the HTML text; painted icons inside are `aria-hidden` (the label sits next to them). The badge image has
`alt=""` because the "Daftar" label is HTML.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 049` passes
- [ ] Demo shows `.tombol-seni` and `.tombol-seni-lembut` with a painted icon placeholder (any transparent PNG/WebP
      in the demo folder) **and** with the Lucide fallback, in rest, hover, focus, active and disabled; blue and
      green; light and dark; at 360 and 1280 px; nothing overflows at 360 px
- [ ] Demo shows the bottom bar badge (rest, active, pressed) on a 360 px wide mock bar, light and dark
- [ ] Text contrast ≥ 4.5:1 for both buttons in light and dark, blue and green (write the ratios in the PR)
- [ ] The leaf shape does not look stretched between 220 and 360 px width
- [ ] With reduced motion nothing moves; colours still change on hover
- [ ] Looks like the same family as 017 `.tombol-daftar` when shown side by side, and clearly secondary to it
