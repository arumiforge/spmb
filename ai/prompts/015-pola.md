---
id: 015
name: pola
kind: svg
batch: svg-kit
files: img/pola/pola-kawung.svg, img/pola/pola-khatam.svg, img/pola/pola-anyaman.svg, img/pola/pola-ceplok-daun.svg, css/pola.css
status: done
---

# 015 — pola (tileable background patterns)

## Purpose

A quiet texture that carries the identity (Islamic geometry and Indonesian textile motifs) behind hero
sections, the sign-in page, the registration card header and empty areas, in the school's own colour.

## Where it is used

- Public home hero and page headers (`/`, `/persyaratan`, `/jadwal`, `/tanya-jawab`, `/cek-status`): behind
  the heading area, full width.
- Staff sign-in (`/masuk`): the side panel behind the painting.
- Registration card preview and printout (`/cek-status/kartu`, PDF later): header band only — therefore
  purely geometric (style guide §2: nothing sacred on printed things).
- Dashboard welcome band (`/admin`).

The HTML adds an empty decorative element: `<div class="pola pola-kawung" aria-hidden="true"></div>`
positioned absolutely inside a `relative overflow-hidden` section.

## Design

Four single-colour tiles, each seamless on all four sides. Shapes are filled `#000` on transparency; CSS
colours them. Low density: about 15–25 % of the tile area covered. Rounded joins, no hairlines thinner
than 1.5 units at a 64-unit tile.

| File | Motif | Tile `viewBox` | Notes |
| --- | --- | --- | --- |
| `pola-kawung.svg` | Kawung: four oval petals around a small centre, on a diagonal grid | `0 0 64 64` | Petals as outlines (stroke converted to filled paths) with a tiny dot in the centre; classic Javanese motif, gentle and open |
| `pola-khatam.svg` | Eight-point star (two overlapping squares) linked by short bands into an octagon grid | `0 0 72 72` | Outline only, no filled stars; no nine-star arrangements |
| `pola-anyaman.svg` | Bamboo weaving (anyaman): over-under strips | `0 0 48 48` | Two directions of rounded strips with small gaps showing the weave |
| `pola-ceplok-daun.svg` | Ceplok grid of four small leaves (banana/frangipani) around a point | `0 0 64 64` | Botanical, softest of the four; good for applicant pages |

Avoid: parang, mosque or dome silhouettes, crescents and stars as a symbol, Arabic letters, the NU
emblem, anything that reads as a picture rather than a texture.

## Files

| File (`ai/generated/install/…`) | What |
| --- | --- |
| `img/pola/pola-kawung.svg` | tile, mask |
| `img/pola/pola-khatam.svg` | tile, mask |
| `img/pola/pola-anyaman.svg` | tile, mask |
| `img/pola/pola-ceplok-daun.svg` | tile, mask |
| `css/pola.css` | utilities below |

## CSS contract (`css/pola.css`)

```
.pola                 position:absolute; inset:0; pointer-events:none;
                      background-color: var(--pola-warna, var(--color-primary-600));
                      opacity: var(--pola-opasitas, .07);
                      mask-image / -webkit-mask-image: var(--pola-gambar);
                      mask-repeat: repeat; mask-size: var(--pola-ukuran, 64px);
.pola-kawung          --pola-gambar: url("/assets/img/pola/pola-kawung.svg");
.pola-khatam          --pola-gambar: url("/assets/img/pola/pola-khatam.svg"); --pola-ukuran: 72px;
.pola-anyaman         --pola-gambar: url("/assets/img/pola/pola-anyaman.svg"); --pola-ukuran: 48px;
.pola-ceplok-daun     --pola-gambar: url("/assets/img/pola/pola-ceplok-daun.svg");
.pola-pudar           adds a mask fade so the pattern disappears towards the text side
                      (second mask layer: linear-gradient(to left, #000 0%, transparent 70%)), mask-composite: intersect
.pola-terang          --pola-warna: #fff (for use on a primary-coloured band)
.dark .pola           --pola-warna: var(--color-primary-300); --pola-opasitas: .09;
```

- Paths in `url()` are root-relative to the installed location (`/assets/img/pola/…`). The demo page may
  override `--pola-gambar` to point at `../install/img/pola/…`.
- Opacity stays between 0.05 and 0.12 so body text on top keeps WCAG AA contrast.
- No animation.

## Alt text / accessibility

Decorative only: the element has `aria-hidden="true"` and no alt text.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 015` passes
- [ ] Each tile is seamless: a 4×4 repeat in the demo shows no seams or doubled lines
- [ ] Readable: a paragraph of grey-700 text over `.pola` (light) and grey-300 (dark) looks calm, not busy
- [ ] Works in blue and green brand, light and dark, at 360 and 1280 px
- [ ] `.pola-pudar` fades towards the left on a hero with text on the left
- [ ] No motif reads as a mosque, crescent, emblem or letter
