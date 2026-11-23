---
id: 047
name: langkah-2
kind: painting
area: langkah
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: langkah
status: requested
---

# 047 — langkah-2

## Purpose

Step 2 of "Cara mendaftar": fill in the online form on a phone.

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

- A smartphone leaning on a small wooden phone stand, turned slightly.
- Its screen shows a simple form: four rounded field bars, the top two holding short grey lines (filled in), and a medium-blue button block at the bottom.
- Beside the stand a small glass of tea and a stylus-like pencil lying flat.

## Colour and light

Slate phone, cream screen, warm wooden stand, amber tea. Blue accent: the form's button block.

## Avoid

- People, hands or faces
- Numbers (the step number is HTML)
- A background shape or tile behind the objects
- Letters, numbers or a keyboard on the screen
- Message bubbles (044 uses them)
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

Square image. A small still life, centred and filling about 85% of the frame, gentle three-quarter view from slightly above, light from the upper left, no ground, no cast shadow, no people, no hands. A smartphone with a slate frame leans on a small warm-wood phone stand; its cream screen shows a simple form made of four rounded field bars, the top two holding short grey lines, and a medium-blue button block at the bottom. Beside the stand stand a small glass of amber tea and a pencil lying flat.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/langkah-2.webp` | `langkah/langkah-2.webp` |
| hijau | `ai/generated/langkah-2-hijau.webp` | `langkah/langkah-2-hijau.webp` |

## Alt text (Indonesian, used in the view)

Formulir pendaftaran yang sedang diisi lewat HP. (Already in `Situs.homeStepAlts[1]`.)

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 047` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Reads at 128 px; the three steps look like one set side by side (same light, outline and scale)
- [ ] Clearly different from the `ikon-beranda` icons for Persyaratan (036) and Pesan (044)
