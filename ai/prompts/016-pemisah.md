---
id: 016
name: pemisah
kind: svg
batch: svg-kit
files: img/pemisah/pemisah-lengkung.svg, img/pemisah/pemisah-tumpal.svg, img/pemisah/pemisah-mega.svg, img/pemisah/pemisah-bukit.svg, css/pemisah.css
status: generated
---

# 016 — pemisah (full-width section dividers)

## Purpose

Soft transitions between page sections that replace flat edges, so long public pages read as a calm
sequence. They are **full-width shapes**, never short lines (D-070).

## Where it is used

Public pages between a coloured band and the next section: home hero → steps, steps → schedule teaser,
the footer top edge; the top of the `/tanya-jawab` and `/jadwal` headers. The HTML is:

```html
<div class="pemisah pemisah-tumpal" aria-hidden="true"></div>
```

placed at the bottom of the upper section (or the top of the lower one with `.pemisah-balik`). The shape
is painted in the **colour of the section it flows into**, set with `--pemisah-warna`.

## Design

| File | Shape | `viewBox` | Repeat |
| --- | --- | --- | --- |
| `pemisah-lengkung.svg` | One gentle asymmetric curve, like the edge of a veranda roof | `0 0 1440 64` | stretched (`preserveAspectRatio="none"`) |
| `pemisah-tumpal.svg` | A row of tumpal triangles (batik/songket border) with a small kawung-like dot between them | `0 0 96 40` | repeated horizontally |
| `pemisah-mega.svg` | Mega mendung clouds: layered rounded cloud bands | `0 0 1440 72` | stretched, height fixed |
| `pemisah-bukit.svg` | Two overlapping low hills (rice field horizon), the back hill as a second subpath at 50 % opacity via `fill-opacity` | `0 0 1440 80` | stretched |

The bottom edge of every shape is a straight full-width fill (so it joins the next section without a
gap); the top edge carries the motif. Minimum visual height 24 px at 360 px wide, 48–80 px at 1280 px.
Avoid mosque/dome skylines, minarets, crescents, Arabic letters and parang.

## Files

| File (`ai/generated/install/…`) | What |
| --- | --- |
| `img/pemisah/pemisah-lengkung.svg` | mask |
| `img/pemisah/pemisah-tumpal.svg` | mask tile |
| `img/pemisah/pemisah-mega.svg` | mask |
| `img/pemisah/pemisah-bukit.svg` | mask |
| `css/pemisah.css` | utilities below |

## CSS contract (`css/pemisah.css`)

```
.pemisah              display:block; width:100%; height: var(--pemisah-tinggi, clamp(24px, 5vw, 64px));
                      background-color: var(--pemisah-warna, #fff);
                      mask / -webkit-mask: var(--pemisah-gambar) bottom / 100% 100% no-repeat;
                      margin-top: -1px;  (no hairline gap)
.dark .pemisah        --pemisah-warna: #111827 (gray-900) unless the next section sets its own
.pemisah-lengkung     --pemisah-gambar: url("/assets/img/pemisah/pemisah-lengkung.svg");
.pemisah-tumpal       repeat-x with mask-size: auto 100%; --pemisah-tinggi: clamp(20px, 3vw, 40px);
.pemisah-mega         --pemisah-tinggi: clamp(28px, 5vw, 72px);
.pemisah-bukit        --pemisah-tinggi: clamp(32px, 6vw, 80px);
.pemisah-balik        transform: scaleY(-1);  (for the top edge of a section)
.pemisah-cermin       transform: scaleX(-1);
```

No animation. `forced-colors: active` → `display:none` (high-contrast users get plain edges).

## Alt text / accessibility

Decorative only: `aria-hidden="true"`, no alt text, never focusable.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 016` passes
- [ ] In the demo, a primary-600 band followed by a white section joins without a hairline gap at 360,
      768 and 1280 px (zoom 100 % and 125 %), light and dark
- [ ] `pemisah-tumpal` repeats with no seam; triangles stay whole at every width (no squashed tile)
- [ ] Nothing reads as a short decorative line or a skyline of mosques
