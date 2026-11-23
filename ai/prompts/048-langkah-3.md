---
id: 048
name: langkah-3
kind: painting
area: langkah
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: langkah
status: requested
---

# 048 — langkah-3

## Purpose

Step 3 of "Cara mendaftar": the registration card, ready to bring to school.

## Where it is used

Home page (`/`), "Cara mendaftar": one step card each (white card in light mode, `gray-900` in dark mode), the painting above the step number and title, shown 128 px (phones) to 144 px (desktop) square, with a small three.js tilt (D-082). The three paintings sit side by side on desktop and must look like one set. Installed trimmed to the subject, longest side ≤ 640 px, budget ≤ 90 KB. Shown as delivered on light and dark pages (no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- Square. A small still life of one to three objects, centred, filling about 85 % of the frame.
- Gentle three-quarter view from slightly above, light from the upper left (same as the `ikon-beranda` set, a little richer in detail).
- No ground plane and no cast shadow; at most a very faint painted contact shadow directly under the objects.
- Must read at 128 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A cream registration card in a clear holder on a deep green lanyard that curls around it.
- The card shows a medium-blue band across the top, a simple head-and-shoulders photo silhouette, a few grey lines and a small abstract square-block pattern in one corner (not a working QR code).
- One frangipani flower lying beside the card.

## Colour and light

Cream card, deep pesantren green lanyard, cream and soft gold frangipani. Blue accent: the band across the card.

## Avoid

- People, hands or faces
- Numbers (the step number is HTML)
- A background shape or tile behind the objects
- A school name, logo or badge on the card
- A readable or scannable QR code
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

Square image. A small still life, centred and filling about 85% of the frame, gentle three-quarter view from slightly above, light from the upper left, no ground, no cast shadow, no people, no hands. A cream registration card in a clear holder on a deep green lanyard that curls around it; the card has a medium-blue band across the top, a simple head-and-shoulders photo silhouette, a few plain grey lines and a small abstract pattern of square blocks in one corner. One cream frangipani flower with a soft gold centre lies beside the card.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/langkah-3.webp` | `langkah/langkah-3.webp` |
| hijau | `ai/generated/langkah-3-hijau.webp` | `langkah/langkah-3-hijau.webp` |

## Alt text (Indonesian, used in the view)

Kartu pendaftaran yang siap dibawa ke sekolah. (Already in `Situs.homeStepAlts[2]`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 048` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Reads at 128 px; the three steps look like one set side by side (same light, outline and scale)
- [ ] Clearly different from the `ikon-beranda` icons for Persyaratan (036) and Pesan (044)
