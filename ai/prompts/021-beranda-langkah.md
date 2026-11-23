---
id: 021
name: beranda-langkah
kind: painting
area: section
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-pages
status: done
---

# 021 — beranda-langkah

## Purpose

Shows that registering takes three easy steps: prepare documents, fill in the form, get the card — as one journey, without numbers or text (the app lists the steps in HTML).

## Where it is used

Public home page (`/`), "how to register" section; the three steps are HTML text next to it. Installed trimmed to the subject, longest side ≤ 800 px. Desktop (≥ 1024 px): beside the page heading, about 320–380 px wide, heading and intro text on the left. Phones (360 px): above the heading, about 200 px wide, centred. It must read clearly at 200 px.

## Composition

- Square vignette. A winding path from the lower left to the upper right with three small stops along it.
- Stop 1 (lower left): a small house porch with a neat stack of papers. Stop 2 (middle): a bench under a tree where a family fills in a form on a phone. Stop 3 (upper right): the school gate where a student holds up a card.
- Generous empty margin; small figures.

## Characters (cast in `ai/style-guide.md`)

- Stop 1: Mak Ijah handing Raka a folder.
- Stop 2: Pak Hadi and Fajar on the bench looking at a phone.
- Stop 3: Aisyah holding up a blank card, smiling.

## Setting and props

- Village-style path with grass edges, banana leaves, a frangipani tree, a low hill far behind.
- The card is blank with abstract blocks only.

## Colour and light

Cream path, green family plants, terracotta roofs; blue accent on the folder (stop 1) and the phone screen (stop 2).

## Avoid

- Numbers or arrows on the path
- Signposts with writing
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. A winding village path runs from the lower left to the upper right with three small stops, like a gentle journey: at the lower left, on the porch of a small house with a terracotta roof, a grandmother in a dark green long kerudung and brown batik cloth hands a blue folder of papers to a 12-year-old boy in a black peci, white shirt and navy trousers; in the middle, on a wooden bench under a frangipani tree, a father in a black peci and a batik-like shirt and his Chinese-Indonesian son in a black peci look together at a phone whose screen shows abstract blue blocks; at the upper right, beside a simple school gate, a girl in a white kerudung, white shirt and navy skirt happily holds up a blank card. Banana leaves, grass edges and a soft low hill far behind. Small figures, generous empty margin, no numbers, arrows or signs.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/beranda-langkah.webp` | `section/beranda-langkah.webp` |
| dark | `ai/generated/beranda-langkah-dark.webp` | `section/beranda-langkah-dark.webp` |
| hijau | `ai/generated/beranda-langkah-hijau.webp` | `section/beranda-langkah-hijau.webp` |
| dark-hijau | `ai/generated/beranda-langkah-dark-hijau.webp` | `section/beranda-langkah-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Tiga langkah mendaftar: menyiapkan berkas, mengisi formulir, lalu menerima kartu.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 021` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] The three stops read in order from lower left to upper right
- [ ] No numbers, arrows or signposts
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
