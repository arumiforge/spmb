---
id: 026
name: kartu
kind: painting
area: section
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-pages
status: generated
---

# 026 — kartu

## Purpose

The registration card is something to be proud of and to bring on test day.

## Where it is used

Public page `/cek-status/kartu` (card view and print) and the card settings page `/admin/pengaturan/kartu`. Installed trimmed to the subject, longest side ≤ 800 px. Desktop (≥ 1024 px): beside the page heading, about 320–380 px wide, heading and intro text on the left. Phones (360 px): above the heading, about 200 px wide, centred. It must read clearly at 200 px.

## Composition

- Square vignette. A student holds the card up with both hands at chest height; the card is big enough to read as a card.

## Characters (cast in `ai/style-guide.md`)

- Raka holds the card; Pak Hadi stands beside him with a hand on his shoulder, proud.

## Setting and props

- The card: blank cream card with a small photo silhouette, a few abstract lines and a small square block pattern in the corner (abstract, must not be a working QR code).
- A small home printer on a side table with one more blank sheet; a potted plant.

## Colour and light

Cream card, green family details; blue accent on a band across the top of the card.

## Avoid

- A real or scannable QR code
- Any name, number or school logo on the card
- Religious symbols on the card (it gets printed)
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. A 12-year-old boy in a black peci, white shirt and navy trousers proudly holds up a blank cream registration card with both hands at chest height; the card has a medium-blue band across the top, a small photo silhouette, a few abstract lines and a small abstract square-block pattern in one corner (not a real QR code, no letters or numbers). His father in a black peci and batik-like shirt stands beside him with a hand on his shoulder, smiling proudly. A small home printer on a side table with a blank sheet, and a potted plant. Proud, happy mood, compact centred composition, generous empty margin.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kartu.webp` | `section/kartu.webp` |
| dark | `ai/generated/kartu-dark.webp` | `section/kartu-dark.webp` |
| hijau | `ai/generated/kartu-hijau.webp` | `section/kartu-hijau.webp` |
| dark-hijau | `ai/generated/kartu-dark-hijau.webp` | `section/kartu-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Calon murid menunjukkan kartu pendaftaran dengan bangga bersama ayahnya.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 026` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] The corner pattern cannot be scanned as a QR code and shows no letters
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
