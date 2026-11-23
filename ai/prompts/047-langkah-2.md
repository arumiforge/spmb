---
id: 047
name: langkah-2
kind: painting
area: langkah
generate: landscape 1536x1152
background: transparent
variants: light, hijau
batch: langkah
status: generated
---

# 047 — langkah-2

Rewritten on 2026-11-24 (D-090): the step pictures are small scenes with people, no longer still-life icons.

## Purpose

Step 2: fill in the online form and keep the registration number. Card title "Isi formulir". The picture tells the step at a glance for parents who read little.

## Where it is used

Home page (`/`), section "Cara mendaftar": the top of step card 2 of 3, above the step number and title.
The card has the art background of brief 072 (`.kartu-langkah`). Shown about 300 × 225 px on phones
(cards stacked) and about 340 × 255 px on desktop (three cards side by side; the three paintings must look
like one set: same light, outline thickness, scale of the figures and amount of empty edge). Installed
trimmed to the subject, longest side ≤ 800 px, budget ≤ 110 KB. Shown as delivered on light and dark cards
(no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- Landscape 4:3 vignette, the scene filling about 90 % of the frame, soft painted edges, no hard frame.
- Two figures large enough to read at 300 px wide (faces at least 1/8 of the height); eye level, gentle light from the upper left.
- Keep the bottom edge calm (the step number and title sit right under it).

## Characters (cast in `ai/style-guide.md`)

- Mak Ijah (grandmother)
- Daeng Arif (applicant boy, Bugis)

## Setting and props

A low wooden table on a woven pandan mat inside a simple home with a window and a hanging fern. Daeng Arif sits cross-legged and fills in a form on a phone (plain screen with abstract grey bars and one round blue button shape); Mak Ijah sits beside him on a low stool, a hand on his shoulder, smiling proudly. A glass of tea and a small notebook on the table.

## Colour and light

Cream, sand and sage with warm skin tones; soft morning light. Blue accent: the round button shape on the phone screen.

## Avoid

- Text, numbers or letter-like marks on papers, screens, cards and walls (the step number is HTML)
- Anything that repeats 021 (three steps in one picture), 022 (mother and child at a table with documents), 025 (phone check on a sofa) or 026 (boy and father with the card)
- Disability or assistive devices (D-089), hearing aids, wheelchairs, canes
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

Real transparent background (true alpha): no white, paper or colour background, no glow, no halo, no
drawn checkerboard. The painting must read on a white page and on a very dark navy page (#111827) alike:
give every shape a soft painted outline in soft charcoal, keep the outer edges mid-toned (neither
near-white nor near-black) and do not add a light rim.

Landscape 4:3 image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges, the subject filling about 90% of the frame. Inside a simple Indonesian home: a low wooden table on a woven pandan mat, a window with a hanging fern behind. Daeng Arif, a 12-year-old Bugis boy with wavy hair, black peci and white baju koko, sits cross-legged at the table and fills in a form on a phone; the phone screen shows only abstract grey bars and one round medium-blue button shape. Mak Ijah, a grandmother in a dark green long kerudung and kain batik in brown and cream, sits upright beside him on a low wooden stool, one hand on his shoulder, smiling proudly. A glass of tea and a small closed notebook on the table. Warm, patient, at home.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/langkah-2.webp` | `langkah/langkah-2.webp` |
| hijau | `ai/generated/langkah-2-hijau.webp` | `langkah/langkah-2-hijau.webp` |

## Alt text (Indonesian, used in the view)

Nenek menemani cucunya mengisi formulir pendaftaran lewat HP. (In `Situs.homeStepAlts[1]`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 047` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and walls)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827`; edges neither glow nor disappear
- [ ] Blue accent only on the round button shape on the phone screen; the `-hijau` copy changes only that
- [ ] Reads at 300 px wide; the three steps look like one set side by side
- [ ] No disability or assistive devices (D-089)
