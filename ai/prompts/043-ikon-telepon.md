---
id: 043
name: ikon-telepon
kind: painting
area: ikon
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: ikon-beranda
status: requested
---

# 043 — ikon-telepon

## Purpose

A painted icon for "Telepon" (call the school).

## Where it is used

Home page "Lokasi dan kontak" cards: shown 56 px square to the left of the label. Installed trimmed to the subject, longest side ≤ 512 px, budget ≤ 60 KB. Shown as delivered on light and dark pages (no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- Square. One subject (one object, or one object with the small companion named below), centred, filling about 80 % of the frame; nothing else in the picture.
- Gentle three-quarter view from slightly above, light from the upper left (the same for the whole set).
- No ground, no cast shadow, no table: the app floats the icon with a small three.js tilt (D-082), so a drawn floor would look wrong.
- Simple, bold shapes and few details: it must read at 48 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A classic rounded desk telephone in cream with a soft terracotta handset resting on top and a short coiled cord.
- A keypad of round blank buttons (no numbers or symbols); one button medium blue.

## Colour and light

Cream body, terracotta handset, slate details. Blue accent: one keypad button.

## Avoid

- People, hands or faces
- Any background shape, circle or tile behind the object (D-070: icons without backgrounds)
- A brand or app logo of any kind
- Numbers or symbols on the buttons
- A smartphone (044 uses one)
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

Square image. One painted subject, centred and filling about 80% of the frame, in a gentle three-quarter view from slightly above, light from the upper left, no ground, no cast shadow, no people, no hands, no background shape behind it. Simple bold shapes with few details so it reads as a small icon. A classic rounded desk telephone in cream with a soft terracotta handset resting on top and a short coiled cord. Its keypad has round blank buttons with no numbers or symbols, and one of them is medium blue.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/ikon-telepon.webp` | `ikon/ikon-telepon.webp` |
| hijau | `ai/generated/ikon-telepon-hijau.webp` | `ikon/ikon-telepon-hijau.webp` |

## Alt text (Indonesian, used in the view)

Telepon meja untuk menghubungi sekolah. (Shown next to its label, so the view uses `alt=""`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 043` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Reads clearly at 48 px (scale it down and look)
- [ ] Looks like one family with the other nine `ikon-beranda` icons: same light, outline weight and view angle
