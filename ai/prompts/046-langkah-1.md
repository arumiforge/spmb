---
id: 046
name: langkah-1
kind: painting
area: langkah
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: langkah
status: requested
---

# 046 — langkah-1

## Purpose

Step 1 of "Cara mendaftar": prepare the documents. A neat folder with the registration papers ready.

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

- An open warm-sand folder with a medium-blue index tab.
- Papers peeking out neatly: a sheet with abstract table rows (like a family card), a sheet with a narrow border of tiny leaves and grey lines (like a birth certificate), a small passport-style photo with a simple head-and-shoulders silhouette.
- A soft gold paper clip holding the photo.

## Colour and light

Sand folder, cream papers, sage leaf border, soft gold clip. Blue accent: the folder's index tab.

## Avoid

- People, hands or faces
- Numbers (the step number is HTML)
- A background shape or tile behind the objects
- Writing, numbers, seals or a Garuda on the papers
- A clipboard (036 uses one)
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

Square image. A small still life, centred and filling about 85% of the frame, gentle three-quarter view from slightly above, light from the upper left, no ground, no cast shadow, no people, no hands. An open warm-sand folder with a medium-blue index tab; neat papers peek out of it: a cream sheet with plain table rows, a cream sheet with a narrow border of tiny sage leaves and a few grey lines, and a small passport-style photo with a simple head-and-shoulders silhouette held by a soft gold paper clip. Tidy and ready.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/langkah-1.webp` | `langkah/langkah-1.webp` |
| hijau | `ai/generated/langkah-1-hijau.webp` | `langkah/langkah-1-hijau.webp` |

## Alt text (Indonesian, used in the view)

Map berisi berkas pendaftaran yang sudah rapi. (Already in `Situs.homeStepAlts[0]`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 046` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Reads at 128 px; the three steps look like one set side by side (same light, outline and scale)
- [ ] Clearly different from the `ikon-beranda` icons for Persyaratan (036) and Pesan (044)
