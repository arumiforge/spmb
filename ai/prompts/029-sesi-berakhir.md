---
id: 029
name: sesi-berakhir
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-states
status: requested
---

# 029 — sesi-berakhir

## Purpose

The session expired (signed out after a while, form token expired): nothing is wrong, just sign in again.

## Where it is used

Sign-in page notice after a timeout, and the "session expired" error page (CSRF failure). Installed trimmed to the subject, longest side ≤ 480 px; shown about 160–240 px wide, centred above a short heading, one sentence and a button (all HTML).

## Composition

- A closed laptop and a cup of tea on a small table.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A glass of warm tea with a little steam, a closed laptop with a blue sticker-free lid, a small wall clock without numerals, a jasmine sprig in a small vase.

## Colour and light

Cream and sand, green family sprig; blue accent on the laptop.

## Avoid

- Alarm clocks, hourglasses running out, anything urgent
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. On a small wooden table, a closed laptop in medium blue next to a glass of warm tea with a little steam and a small vase with a jasmine sprig; behind, a small round wall clock with no numerals. Restful break mood. Small simple vignette, centred, generous empty margin, calm and friendly.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/sesi-berakhir.webp` | `state/sesi-berakhir.webp` |
| dark | `ai/generated/sesi-berakhir-dark.webp` | `state/sesi-berakhir-dark.webp` |
| hijau | `ai/generated/sesi-berakhir-hijau.webp` | `state/sesi-berakhir-hijau.webp` |
| dark-hijau | `ai/generated/sesi-berakhir-dark-hijau.webp` | `state/sesi-berakhir-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Laptop tertutup dan segelas teh di meja.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 029` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
