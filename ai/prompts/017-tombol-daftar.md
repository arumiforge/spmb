---
id: 017
name: tombol-daftar
kind: svg
batch: svg-kit
files: img/tombol/tombol-ornamen.svg, css/tombol-daftar.css
status: requested
---

# 017 — tombol-daftar (special "Daftar sekarang" button)

## Purpose

The one call to action that matters most on the public site gets a crafted look — the school colour with
a soft woven texture and a small rosette ornament — while staying a real, accessible HTML link or button.
Depends on 015 (`pola-kawung.svg`) and 018 (`memuat-kecil.svg`).

## Where it is used

- Home hero (`/`), large size; end of `/persyaratan`, `/jadwal` and `/tanya-jawab`; the public header on
  desktop (normal size); full width on phones in the hero.
- Only for the main registration action ("Daftar sekarang", "Lanjutkan pendaftaran"). Every other button
  stays a normal Flowbite button.

Markup the app will use (the text is real HTML, in Indonesian, written by Claude):

```html
<a href="/daftar" class="tombol-daftar tombol-daftar-besar">
  <span class="tombol-daftar-isi">Daftar sekarang <svg class="lucide …">…</svg></span>
</a>
<button type="submit" class="tombol-daftar" aria-busy="true">
  <span class="tombol-daftar-isi">Menyimpan…</span>
</button>
```

## Design

Layers, from back to front, all inside the button's rounded shape (radius 0.75rem, `overflow:hidden`,
`isolation:isolate`):

1. Background: `linear-gradient(135deg, var(--color-primary-600), var(--color-primary-700))`.
2. Texture: the kawung tile from 015 as a mask, painted white at 12 % opacity, 40 px tile, fading out
   towards the text side (`::before`).
3. Ornament: `tombol-ornamen.svg`, a small eight-petal rosette (a frangipani-like flower drawn on an
   eight-point star grid) at the right end, 56 px, white at 18 %, partly cut off by the edge (`::after`).
4. Content: text in white, semibold, `1rem` (normal) / `1.125rem` (large), Lucide arrow icon after it in
   `currentColor` — no circle or tile behind the icon (D-070).

No coloured side or top border, no uppercase, no letter-spacing.

| State | Look |
| --- | --- |
| Rest | as above, soft shadow `0 6px 16px -6px` in primary-700 at 45 % |
| Hover | gradient shifts darker (primary-700 → primary-800), ornament rotates 20° and the texture slides 8 px, arrow moves 3 px right (200 ms ease-out) |
| Focus visible | 3 px outline in `var(--color-primary-300)` with 3 px offset (works on white and on dark) |
| Active | moves down 1 px, shadow smaller |
| Disabled (`:disabled`, `[aria-disabled="true"]`) | grey-200 background, grey-500 text, no texture, no ornament, no shadow, `cursor:not-allowed` |
| Busy (`[aria-busy="true"]`) | arrow hidden, the small loader from 018 shown before the text, pointer events off |
| Dark (`.dark`) | same colours, plus a 1 px full ring in `var(--color-primary-400)` at 60 % so the button stands out from `#111827`; focus outline in `primary-300` |
| Forced colours | system `ButtonText` border and `ButtonFace` background, no layers |

All transitions and the hover movement only inside `@media (prefers-reduced-motion: no-preference)`.

## Files

| File (`ai/generated/install/…`) | What |
| --- | --- |
| `img/tombol/tombol-ornamen.svg` | rosette, mask (`#000` on transparency), `viewBox="0 0 56 56"` |
| `css/tombol-daftar.css` | `.tombol-daftar`, `.tombol-daftar-besar`, `.tombol-daftar-penuh` (full width under 640 px), `.tombol-daftar-isi`, all states |

CSS custom properties the app can override: `--tombol-teks` (default `#fff`), `--tombol-dari`,
`--tombol-ke` (gradient stops). Minimum height 48 px (normal) and 56 px (large); horizontal padding
1.25 rem / 1.75 rem. Use `url("/assets/img/pola/pola-kawung.svg")` and
`url("/assets/img/tombol/tombol-ornamen.svg")`; the busy state uses
`url("/assets/img/memuat/memuat-kecil.svg")` as a mask painted in `currentColor`.

## Alt text / accessibility

All layers are pseudo-elements, so they are invisible to screen readers. The label is the HTML text.
The busy state needs `aria-busy="true"` and a visible text change from the app ("Menyimpan…").

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 017` passes
- [ ] Demo shows rest, hover, focus, active, disabled and busy for both sizes, blue and green, light and dark
- [ ] White text on the button background has contrast ≥ 4.5:1 for `#2563EB` and `#005C2F` (write the
      ratios in the pull request)
- [ ] Keyboard: Tab shows the focus outline clearly on white and on `#111827`
- [ ] At 360 px `.tombol-daftar-penuh` spans the width, text does not wrap awkwardly, height ≥ 48 px
- [ ] With reduced motion nothing moves on hover; colours still change
- [ ] Works on `<a>` and on `<button>`; no layout shift between states
