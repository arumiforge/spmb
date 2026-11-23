---
id: 019
name: hero-beranda-santri
kind: painting
area: hero
generate: landscape 1536x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-pages
status: requested
---

# 019 — hero-beranda-santri

## Purpose

A new home hero that shows the identity at first sight: a madrasah-pesantren school in the morning where families from many backgrounds arrive to register. It sits next to the existing 001 (which stays available); Claude chooses per school.

## Where it is used

Public home page (`/`), hero section. Headline, one sentence and the "Daftar sekarang" button (017) sit on the LEFT on desktop; on phones the picture sits below the text. Installed trimmed to the painted subject, longest side ≤ 1100 px; shown up to ~720 px wide at 1280 px and full width (about 360 px) on phones, below the heading.

## Composition

- Wide landscape. All subjects in the RIGHT 58 %; the LEFT 42 % stays empty (transparent), because the headline goes there.
- Eye level; full bodies visible; a gentle path leads from the lower left of the subject area towards the school.
- A calm, open sky area at the top right; no subject touches the top edge.

## Characters (cast in `ai/style-guide.md`)

- Ibu Sari with Aisyah (holding a smartphone whose screen shows only abstract blocks), both smiling, walking towards the gate.
- Pak Rahmat walking with Nurul beside him, chatting happily.
- Further back, smaller: Raka and Daeng Arif greeting Pak Budi at the gate by bowing slightly and shaking hands (salim).

## Setting and props

- A single-storey Indonesian school with cream walls, muted terracotta roof tiles, a wide serambi (veranda) with simple arches and a geometric wooden screen; no sign, name or flag.
- Far behind, small and soft, a simple musholla roof with a small dome among trees (optional; leave it out if it draws attention).
- A frangipani tree with cream flowers, a mango tree, potted plants, a row of shoes neatly lined up at the veranda steps.

## Colour and light

Mostly cream, sand and the green family (walls in cream, trees in sage and leaf green, a deep green sarung or kerudung here and there), warm gold morning light. The only blue accent is the phone screen.

## Avoid

- Any sign, banner, calligraphy or name on the building
- Flags
- A crowded scene
- Anyone praying or raising hands in du'a
- Realistic faces
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

Landscape image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. Keep the LEFT 42% completely empty and transparent, because a headline will be placed there. In the RIGHT 58%: a single-storey Indonesian madrasah school with cream walls, muted terracotta roof tiles, a wide veranda with simple arches and a geometric wooden screen, a row of shoes neatly lined up at the veranda steps (no sign, no name, no flag). A frangipani tree with cream flowers and a mango tree frame the building. On a gentle path in front, a mother in a white kerudung and a sand-coloured long tunic walks with her 12-year-old daughter, who wears a white kerudung, a white school shirt, a navy skirt and a small sage-green backpack and holds a smartphone whose screen shows only abstract blue and white blocks. Beside them a father in a light grey peci and an olive batik-like shirt walks along while his daughter from Papua, with darker brown skin, a cream kerudung and round glasses, walks next to him chatting; everyone smiles. Further back, small and soft, two boys in white shirts, navy trousers and black peci greet a male teacher in a black peci and batik-like shirt at the gate. Calm, welcoming morning mood, warm golden light.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/hero-beranda-santri.webp` | `hero/hero-beranda-santri.webp` |
| dark | `ai/generated/hero-beranda-santri-dark.webp` | `hero/hero-beranda-santri-dark.webp` |
| hijau | `ai/generated/hero-beranda-santri-hijau.webp` | `hero/hero-beranda-santri-hijau.webp` |
| dark-hijau | `ai/generated/hero-beranda-santri-dark-hijau.webp` | `hero/hero-beranda-santri-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Keluarga dari berbagai daerah datang ke sekolah untuk mendaftar pada pagi hari.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 019` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Left 42 % is fully transparent (a headline must fit there at 1280 px)
- [ ] No wheelchair, hearing aid, cane or other assistive device anywhere (D-089)
- [ ] Salim at the gate reads as respectful greeting, not as prayer
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
