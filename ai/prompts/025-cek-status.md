---
id: 025
name: cek-status
kind: painting
area: section
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-pages
status: requested
---

# 025 — cek-status

## Purpose

Checking the registration status feels easy and reassuring, even in the evening at home.

## Where it is used

Public page `/cek-status` (lookup form), beside the heading; the form sits below on phones. Installed trimmed to the subject, longest side ≤ 800 px. Desktop (≥ 1024 px): beside the page heading, about 320–380 px wide, heading and intro text on the left. Phones (360 px): above the heading, about 200 px wide, centred. It must read clearly at 200 px.

## Composition

- Square vignette, subject centred.
- Mother and daughter on a sofa looking at one phone together.

## Characters (cast in `ai/style-guide.md`)

- Aisyah holds the phone; Ibu Sari leans in; both relieved and happy.

## Setting and props

- Phone screen: abstract blocks and one round tick shape (a simple check mark inside a circle, no text).
- A warm table lamp, a cushion with a kawung-like pattern, a small plant, an evening window with a soft dusk colour.

## Colour and light

Warm lamp light, cream and sand, green family cushion; blue accent on the phone screen blocks; the tick shape in soft leaf green.

## Avoid

- Worry, tension or a red cross
- Numbers on the screen
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. In a cosy living room in the early evening, a 12-year-old girl in a white kerudung and a white shirt sits on a sofa holding a phone, and her mother in a white kerudung and sand-coloured tunic leans in to look; both look relieved and happy. The phone screen shows only abstract blue blocks and one round soft-green tick shape (no letters, no numbers). A warm table lamp, a cushion with a simple kawung-like pattern, a small potted plant and a window with a soft dusk sky. Calm, reassuring mood, compact centred composition, generous empty margin.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/cek-status.webp` | `section/cek-status.webp` |
| dark | `ai/generated/cek-status-dark.webp` | `section/cek-status-dark.webp` |
| hijau | `ai/generated/cek-status-hijau.webp` | `section/cek-status-hijau.webp` |
| dark-hijau | `ai/generated/cek-status-dark-hijau.webp` | `section/cek-status-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Ibu dan anak memeriksa status pendaftaran di ponsel dengan tenang.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 025` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
