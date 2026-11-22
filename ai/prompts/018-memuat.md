---
id: 018
name: memuat
kind: svg
batch: svg-kit
files: img/memuat/memuat.svg, img/memuat/memuat-kecil.svg, css/memuat.css
status: requested
---

# 018 — memuat (loading animation, SVG + CSS, no Lottie)

## Purpose

A calm, recognisable "please wait" mark for slow actions (importing schools, generating cards and PDFs,
searching previous schools, loading charts), built from the eight-point star so it carries the identity.

## Where it is used

- Large (64–96 px), centred in a panel while content loads: dashboard charts, the school importer,
  card generation, the `/cek-status` lookup.
- Small (16–20 px), inline inside buttons in the busy state (017 and normal buttons).

Markup the app will use (the visible text is HTML, in Indonesian):

```html
<div class="memuat" role="status">
  <svg class="memuat-gambar" aria-hidden="true">…inline copy of memuat.svg…</svg>
  <span class="memuat-teks">Sedang memuat data…</span>
</div>
```

The large version is **inlined** (so it can use `currentColor`); the small one is used as a CSS mask.

## Design

- `memuat.svg` (`viewBox="0 0 96 96"`): an eight-point star outline (two overlapping rounded squares)
  made of eight separate segments, plus four small kawung petals in the centre. Animation: the segments
  light up one after another (opacity 0.25 → 1 → 0.25), 1.6 s loop, staggered by 0.2 s; the whole star
  turns slowly (one turn per 8 s). Fills/strokes use `currentColor`; the app sets the colour with
  `text-primary-600 dark:text-primary-300`.
- `memuat-kecil.svg` (`viewBox="0 0 20 20"`): the same star simplified to one stroke ring with a gap,
  rotating once per 0.9 s; `#000` only (it is a mask).
- Animation lives in a `<style>` inside each SVG (CSS keyframes, no SMIL), wrapped in
  `@media (prefers-reduced-motion: no-preference)`. Under `reduce` the large loader shows the full star
  at steady opacity and the small one a static ring; the HTML text still says what is happening.
- No crescent, no dome, no Arabic letters.

## Files

| File (`ai/generated/install/…`) | What |
| --- | --- |
| `img/memuat/memuat.svg` | large loader, `currentColor`, inline use |
| `img/memuat/memuat-kecil.svg` | small loader, mask |
| `css/memuat.css` | `.memuat` (flex column, centred, gap 0.75 rem), `.memuat-gambar` (size via `--memuat-ukuran`, default 72 px), `.memuat-teks` (grey-600 / dark grey-300, 0.875 rem), `.memuat-kecil` (inline-block 1.1em mask in `currentColor`, for buttons) |

## Alt text / accessibility

`role="status"` on the wrapper with the HTML text (e.g. "Sedang memuat data…"); the SVG is
`aria-hidden="true"`. Never rely on the animation alone to tell the user something.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 018` passes
- [ ] Demo shows both sizes animating, blue and green, light and dark, and still frames with reduced motion
- [ ] No flashing: no element changes opacity more than 3 times per second
- [ ] CPU friendly: only `opacity` and `transform` are animated
- [ ] The small loader sits on the text baseline inside a 48 px button without changing its height
