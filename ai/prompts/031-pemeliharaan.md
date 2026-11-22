---
id: 031
name: pemeliharaan
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-states
status: requested
---

# 031 — pemeliharaan

## Purpose

Planned maintenance (the site is being tidied up): reassuring that it will be back soon.

## Where it is used

Maintenance page (503) shown during deploys. Installed trimmed to the subject, longest side ≤ 480 px; shown about 160–240 px wide, centred above a short heading, one sentence and a button (all HTML).

## Composition

- A teacher tidying a veranda, plants and tools neatly arranged.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- Pak Budi watering potted plants with a small can, smiling.

## Setting and props

- A small wooden tool box, a broom leaning on a pillar, neatly lined-up pots.

## Colour and light

Green family plants, cream veranda; blue accent on the watering can.

## Avoid

- Broken things, construction hazard tape, sparks
- Everything in the style guide: "Respect for religious elements", "Never" and "Rules for every painting"

## Prompt

```
Hand-painted storybook illustration in soft gouache and watercolour, visible gentle brush texture,
slightly uneven painted edges, light paper grain inside the painted areas. Stylised friendly characters
with simple rounded faces, small dot eyes, rosy cheeks and simplified hands, like a modern Indonesian
children's educational book. Not photorealistic, not semi-realistic, not 3D, not CGI, no glossy shading,
no anime. Warm muted palette: cream, warm sand, slate grey, muted sage and leaf greens, a deep pesantren
green, soft terracotta and a touch of soft gold, with ONE small accent in medium blue #2563EB. Soft even
morning light. Modest Indonesian Muslim school life: girls in kerudung, boys in black peci. Absolutely no
text, letters, numbers, Arabic script, calligraphy, logos, emblems, flags, signatures or watermarks anywhere.

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. A male teacher in a black peci, glasses and a light batik-like shirt waters a row of potted plants on a school veranda with a small medium-blue watering can, smiling; a small wooden tool box and a broom leaning on a pillar, everything neat. Small simple vignette, centred, generous empty margin, calm and friendly.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/pemeliharaan.webp` | `state/pemeliharaan.webp` |
| dark | `ai/generated/pemeliharaan-dark.webp` | `state/pemeliharaan-dark.webp` |
| hijau | `ai/generated/pemeliharaan-hijau.webp` | `state/pemeliharaan-hijau.webp` |
| dark-hijau | `ai/generated/pemeliharaan-dark-hijau.webp` | `state/pemeliharaan-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Petugas sedang merapikan sekolah sebentar.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 031` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] The man's clothing matches the cast (Pak Budi)
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
