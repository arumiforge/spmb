---
id: 048
name: langkah-3
kind: painting
area: langkah
generate: landscape 1536x1152
background: transparent
variants: light, hijau
batch: langkah
status: requested
---

# 048 — langkah-3

Rewritten on 2026-11-24 (D-090): the step pictures are small scenes with people, no longer still-life icons.

## Purpose

Step 3: check the status and download the registration card. Card title "Simpan kartu". The picture tells the step at a glance for parents who read little.

## Where it is used

Home page (`/`), section "Cara mendaftar": the top of step card 3 of 3, above the step number and title.
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

- Aisyah (applicant girl, Javanese)
- Ibu Sari (mother)
- Bu Nur (school staff, in the background)

## Setting and props

In front of the school gate with cream pillars and a frangipani tree. Aisyah holds up a printed registration card (blank photo square, grey bars, one coloured header band) with both hands and smiles; Ibu Sari stands beside her with a phone in her hand, happy. Bu Nur waves kindly from the gate in the background.

## Colour and light

Cream, sand and sage with warm skin tones; soft morning light. Blue accent: the card's header band.

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

Landscape 4:3 image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges, the subject filling about 90% of the frame. In front of a simple school gate with cream pillars, a low green fence and a frangipani tree with white flowers. Aisyah, a 12-year-old Javanese girl in a white kerudung, white school shirt, navy skirt and a small sage-green backpack, holds up a printed registration card with both hands and smiles: the card has a blank photo square, a few plain grey bars and one medium-blue header band, no writing. Ibu Sari, her mother in a white kerudung and a soft sand-coloured long tunic, stands beside her holding a phone, happy. Bu Nur, the school ustadzah in a white kerudung and navy cardigan with a lanyard and a blank card, waves kindly from the gate in the background. Proud, ready, welcoming.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/langkah-3.webp` | `langkah/langkah-3.webp` |
| hijau | `ai/generated/langkah-3-hijau.webp` | `langkah/langkah-3-hijau.webp` |

## Alt text (Indonesian, used in the view)

Calon murid menunjukkan kartu pendaftaran di depan gerbang sekolah bersama ibunya. (In `Situs.homeStepAlts[2]`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 048` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and walls)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827`; edges neither glow nor disappear
- [ ] Blue accent only on the card's header band; the `-hijau` copy changes only that
- [ ] Reads at 300 px wide; the three steps look like one set side by side
- [ ] No disability or assistive devices (D-089)
