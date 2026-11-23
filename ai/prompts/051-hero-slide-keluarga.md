---
id: 051
name: hero-slide-keluarga
kind: painting
area: hero
generate: landscape 1536x1024
background: transparent
variants: light, hijau
batch: slide-beranda
status: requested
---

# 051 — hero-slide-keluarga

## Purpose

A default hero slide: parents bringing their children to meet the teachers in the schoolyard.

## Where it is used

Home hero slideshow (D-084, `[data-slide-hero]` in `app/Views/home.php`): default slides when the school has uploaded none, shown with `object-contain` in the right-hand column, up to about 600 px wide at 1280 px and about 360 px wide on phones; the page also fades every edge with a CSS mask (`.slide-hero`). Crossfades with 019 `hero-beranda-santri` and 001, so it must match their look and scale of figures. Installed trimmed to the subject, longest side ≤ 1100 px, budget ≤ 220 KB. Shown as delivered on light and dark pages (no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- Wide landscape that fills the frame (no empty text area: the text column is separate).
- Keep every face and the main action inside the central 70 % of the width and 75 % of the height; the outer edges are fading foliage, sky and ground that the mask may cut.
- The painting itself fades softly into transparency at all four edges (no straight cut, no frame).
- Eye level; figures about the same size as in 019.

## Characters (cast in `ai/style-guide.md`)

- Pak Hadi with his son Fajar: Fajar does salim to Pak Budi (bows slightly and touches the back of his hand to his forehead), Pak Hadi smiles beside them.
- Mak Ijah and Nurul: Nurul does salim to Bu Nur, Mak Ijah chats with Bu Nur.
- Kiai Mansur small in the background under the veranda, smiling.
- No physical contact between unrelated adult men and women.

## Setting and props

- A schoolyard with a big mango tree giving shade, a low cream wall with a gate in the background, a bench, potted plants, fallen frangipani flowers.
- Nurul carries a small sand-coloured folder.

## Colour and light

Cream, sand, green family, warm light under the tree. Blue accent: a small water bottle on Fajar's backpack.

## Avoid

- Repeating 019 (families walking towards the gate)
- Handshakes between unrelated adult men and women
- Signs, flags or a school name
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

Landscape image that fills the frame, the painting fading softly into transparency at all four edges, faces and main action in the centre. In a shady Indonesian schoolyard under a big mango tree, with a low cream wall and gate in the background: a father in a black peci and a sage-and-sand batik-like shirt watches his 12-year-old son with lighter skin, a black peci, white shirt and navy trousers greet a male teacher in a black peci, glasses and a light batik-like shirt by bowing slightly and touching the back of the teacher's hand to his forehead (salim); a small medium-blue water bottle hangs on the boy's backpack. Next to them a grandmother in a dark green long kerudung and brown batik cloth chats with a female teacher in a white kerudung and navy cardigan, while a 12-year-old girl with darker brown skin, a cream kerudung and round glasses greets that teacher with salim, holding a sand-coloured folder. Small in the background on a veranda an elderly teacher in a white peci and white baju koko smiles. Fallen frangipani flowers, a bench and potted plants. Warm, respectful, welcoming mood.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/hero-slide-keluarga.webp` | `hero/hero-slide-keluarga.webp` |
| hijau | `ai/generated/hero-slide-keluarga-hijau.webp` | `hero/hero-slide-keluarga-hijau.webp` |

## Alt text (Indonesian, used in the view)

Orang tua mengantar anaknya bertemu guru di halaman sekolah. (Already in `App.altHeroSlides`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 051` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Faces and action inside the central 70 % × 75 %; looks good with the `.slide-hero` mask at 360 and 1280 px
- [ ] Salim reads as a respectful greeting; men greet men, women greet women
- [ ] Clearly a different scene from 019 and 050
