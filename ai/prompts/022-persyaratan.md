---
id: 022
name: persyaratan
kind: painting
area: section
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-pages
status: done
---

# 022 — persyaratan

## Purpose

Makes the documents list feel manageable: a family calmly prepares the papers together at home.

## Where it is used

Public page `/persyaratan` (required documents), beside the page heading. Installed trimmed to the subject, longest side ≤ 800 px. Desktop (≥ 1024 px): beside the page heading, about 320–380 px wide, heading and intro text on the left. Phones (360 px): above the heading, about 200 px wide, centred. It must read clearly at 200 px.

## Composition

- Square vignette, subject centred, generous margin.
- A low table seen slightly from above with neat piles of papers.

## Characters (cast in `ai/style-guide.md`)

- Ibu Sari and Raka sit at the table; Raka photographs one paper with a phone, Ibu Sari sorts papers into a folder; both relaxed.

## Setting and props

- Papers with only abstract lines and blocks (a family card, a birth certificate, a report book, a small passport-style photo with a simple silhouette).
- A paper clip tray, a glass of tea, a small plant. A woven rattan chair behind.

## Colour and light

Cream papers, sand table, green family plant and cushion; blue accent on the folder and the phone case.

## Avoid

- Readable forms, stamps or seals
- Garuda or any official emblem on papers
- Piles that look overwhelming
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. At a low wooden table at home, seen slightly from above, a mother in a white kerudung and a sand-coloured long tunic calmly sorts a few neat papers into a blue folder, while her 12-year-old son in a black peci, white shirt and navy trousers photographs one paper with a phone in a blue case. The papers show only abstract lines and blocks (no writing, no stamps, no emblems); one is a small photo card with a simple silhouette. A glass of tea, a small clip tray, a potted plant and a woven rattan chair behind them. Calm, organised, friendly. Compact centred composition with generous empty margin.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/persyaratan.webp` | `section/persyaratan.webp` |
| dark | `ai/generated/persyaratan-dark.webp` | `section/persyaratan-dark.webp` |
| hijau | `ai/generated/persyaratan-hijau.webp` | `section/persyaratan-hijau.webp` |
| dark-hijau | `ai/generated/persyaratan-dark-hijau.webp` | `section/persyaratan-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Ibu dan anak menyiapkan berkas pendaftaran bersama di rumah.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 022` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] No official emblem, stamp or seal shapes on papers
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
