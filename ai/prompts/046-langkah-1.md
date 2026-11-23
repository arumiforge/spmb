---
id: 046
name: langkah-1
kind: painting
area: langkah
generate: landscape 1536x1152
background: transparent
variants: light, hijau
batch: langkah
status: requested
---

# 046 — langkah-1

Rewritten on 2026-11-24 (D-090): the step pictures are small scenes with people, no longer still-life icons.

## Purpose

Step 1 of "Cara mendaftar": read the requirements and prepare the documents. Card title "Siapkan berkas". The picture tells the step at a glance for parents who read little.

## Where it is used

Home page (`/`), section "Cara mendaftar": the top of step card 1 of 3, above the step number and title.
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

- Pak Rahmat (Minang father)
- Nurul (applicant girl from Papua, round glasses)

## Setting and props

A wooden veranda bench in front of a cream house wall with a potted pandan plant. Pak Rahmat and Nurul sit side by side; Nurul holds a neat warm-sand folder open on her lap and points at a cream sheet with plain grey lines, Pak Rahmat leans in and nods. On the bench beside them: two more cream papers and a small passport-style photo held by a soft gold paper clip.

## Colour and light

Cream, sand and sage with warm skin tones; soft morning light. Blue accent: the folder's index tab.

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

Landscape 4:3 image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges, the subject filling about 90% of the frame. A wooden veranda bench in front of a short piece of cream house wall with a potted pandan plant. Pak Rahmat, a Minang father in a light grey peci and an olive batik-like shirt with a calm smile, and Nurul, a 12-year-old girl from Papua with darker brown skin, a cream kerudung over curly hair at the hairline, round glasses, white school shirt and navy skirt, sit side by side. Nurul holds an open warm-sand folder with a medium-blue index tab on her lap and points at a cream sheet with plain grey lines; Pak Rahmat leans in and nods. On the bench beside them lie two more cream papers and a small passport-style photo with a simple head-and-shoulders silhouette held by a soft gold paper clip. Calm, organised, encouraging.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/langkah-1.webp` | `langkah/langkah-1.webp` |
| hijau | `ai/generated/langkah-1-hijau.webp` | `langkah/langkah-1-hijau.webp` |

## Alt text (Indonesian, used in the view)

Ayah dan anak menyiapkan berkas pendaftaran di bangku teras. (In `Situs.homeStepAlts[0]`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 046` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and walls)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827`; edges neither glow nor disappear
- [ ] Blue accent only on the folder's index tab; the `-hijau` copy changes only that
- [ ] Reads at 300 px wide; the three steps look like one set side by side
- [ ] No disability or assistive devices (D-089)
