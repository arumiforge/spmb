---
id: 037
name: ikon-jadwal
kind: painting
area: ikon
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: ikon-beranda
status: requested
---

# 037 — ikon-jadwal

## Purpose

A painted icon for "Jadwal" (registration dates): a desk calendar.

## Where it is used

Home page (`/`), "Informasi pendaftaran" cards: the painted icon sits above the card title, shown 80 px (phones) to 96 px (desktop) square. Installed trimmed to the subject, longest side ≤ 512 px, budget ≤ 60 KB. Shown as delivered on light and dark pages (no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- Square. One subject (one object, or one object with the small companion named below), centred, filling about 80 % of the frame; nothing else in the picture.
- Gentle three-quarter view from slightly above, light from the upper left (the same for the whole set).
- No ground, no cast shadow, no table: the app floats the icon with a small three.js tilt (D-082), so a drawn floor would look wrong.
- Simple, bold shapes and few details: it must read at 48 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A small standing desk calendar: a wooden base, two soft gold rings at the top, a cream page with a grid of blank squares (four columns, three rows).
- One square is medium blue; two squares carry a small soft green dot.
- No numbers, no month names, no day names.

## Colour and light

Cream page, warm-sand wooden base, soft gold rings, slate grid lines. Blue accent: the one highlighted square.

## Avoid

- People, hands or faces
- Any background shape, circle or tile behind the object (D-070: icons without backgrounds)
- A brand or app logo of any kind
- Numbers or letter shapes in the squares
- A wall calendar on a cork board (used by 032)
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

Square image. One painted subject, centred and filling about 80% of the frame, in a gentle three-quarter view from slightly above, light from the upper left, no ground, no cast shadow, no people, no hands, no background shape behind it. Simple bold shapes with few details so it reads as a small icon. A small standing desk calendar with a warm-sand wooden base and two soft gold rings at the top; its cream page shows a simple grid of blank squares, four across and three down, with no numbers or names. One square is filled medium blue and two squares carry a small soft green dot.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/ikon-jadwal.webp` | `ikon/ikon-jadwal.webp` |
| hijau | `ai/generated/ikon-jadwal-hijau.webp` | `ikon/ikon-jadwal-hijau.webp` |

## Alt text (Indonesian, used in the view)

Kalender meja dengan satu tanggal yang ditandai. (Shown next to its label, so the view uses `alt=""`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 037` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Reads clearly at 48 px (scale it down and look)
- [ ] Looks like one family with the other nine `ikon-beranda` icons: same light, outline weight and view angle
