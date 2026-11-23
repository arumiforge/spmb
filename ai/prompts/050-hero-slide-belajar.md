---
id: 050
name: hero-slide-belajar
kind: painting
area: hero
generate: landscape 1536x1024
background: transparent
variants: light, hijau
batch: slide-beranda
status: requested
---

# 050 — hero-slide-belajar

## Purpose

A default hero slide: students from different backgrounds learning together happily on the school veranda.

## Where it is used

Home hero slideshow (D-084, `[data-slide-hero]` in `app/Views/home.php`): default slides when the school has uploaded none, shown with `object-contain` in the right-hand column, up to about 600 px wide at 1280 px and about 360 px wide on phones; the page also fades every edge with a CSS mask (`.slide-hero`). Crossfades with 019 `hero-beranda-santri` and 001, so it must match their look and scale of figures. Installed trimmed to the subject, longest side ≤ 1100 px, budget ≤ 220 KB. Shown as delivered on light and dark pages (no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- Wide landscape that fills the frame (no empty text area: the text column is separate).
- Keep every face and the main action inside the central 70 % of the width and 75 % of the height; the outer edges are fading foliage, sky and ground that the mask may cut.
- The painting itself fades softly into transparency at all four edges (no straight cut, no frame).
- Eye level; figures about the same size as in 019.

## Characters (cast in `ai/style-guide.md`)

- Aisyah and Nurul sitting side by side at a low wooden table (dampar), sharing one exercise book, Nurul pointing at it and both smiling.
- Raka and Fajar opposite them, Fajar explaining something with an open hand, Raka laughing gently.
- Bu Nur kneeling at the end of the table, looking on kindly.
- Girls and boys sit on different sides of the table.

## Setting and props

- A wide serambi (veranda) with simple arches and a geometric wooden screen behind, cream walls, terracotta roof edge above.
- On the table: exercise books with plain lines, pencils, a pencil case, a glass of water.
- Sandals and shoes neatly lined up at the veranda step; potted plants; a frangipani branch at one side.

## Colour and light

Cream, sand and the green family; warm morning light. Blue accent: the pencil case only.

## Avoid

- A mushaf or any book on a rehal (this is an ordinary lesson)
- Books or shoes on the floor near each other
- Writing on the books or a blackboard with letters
- Figures in prayer
- Realistic faces
- A crowded scene
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

Landscape image that fills the frame, the painting fading softly into transparency at all four edges, faces and main action in the centre. On the wide veranda of an Indonesian school with simple arches, a geometric wooden screen, cream walls and a terracotta roof edge, four 12-year-old students sit around a low wooden table: on one side two girls in white kerudung and cream kerudung (the second with darker brown skin and round glasses), white shirts and navy skirts, sharing one exercise book and smiling; on the other side two boys in black peci, white shirts and navy trousers, one explaining with an open hand while the other laughs gently. A female teacher in a white kerudung and navy cardigan kneels at the end of the table, watching kindly. On the table: exercise books with plain lines, pencils, a medium-blue pencil case and a glass of water. Sandals neatly lined up at the step, potted plants and a frangipani branch at one side. Warm, happy morning mood.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/hero-slide-belajar.webp` | `hero/hero-slide-belajar.webp` |
| hijau | `ai/generated/hero-slide-belajar-hijau.webp` | `hero/hero-slide-belajar-hijau.webp` |

## Alt text (Indonesian, used in the view)

Murid belajar bersama dengan gembira di serambi sekolah. (Already in `App.altHeroSlides`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 050` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Faces and action inside the central 70 % × 75 %; looks good with the `.slide-hero` mask at 360 and 1280 px
- [ ] No book on the floor; nothing that reads as a mushaf
