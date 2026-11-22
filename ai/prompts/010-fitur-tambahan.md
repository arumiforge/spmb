---
id: 010
file: fitur-tambahan.png
target: public/assets/img/state/fitur-tambahan.webp
usage: Empty state of the Fitur page (`/admin/pengaturan/fitur`) when no optional features are installed
shape: square
size: 1024x1024
background: transparent
variants: light+dark
status: done
---

# 010 — fitur-tambahan

## Purpose

Suggest that features can be added later, like building blocks.

## Composition

- Square, a 2x2 grid of rounded blocks centred; a hand enters from the upper right.

## Characters (see cast in `ai/style-guide.md`)

- Only a friendly painted hand (warm skin tone, simple) placing the last block.

## Setting and props

- Three blocks in slate grey and sand, already in place.
- The fourth block, painted blue, being lowered into the empty slot.
- A few small sparkles.

## Colour and light

Slate and sand; the fourth block is the blue accent.

## Avoid

- Symbols, letters or icons on the blocks
- Toy brand look (no studs like LEGO)
- Everything listed under "Never" and "Rules for every image" in the style guide

## Prompt

```
Hand-painted storybook illustration in soft gouache and watercolour, visible gentle brush texture,
slightly uneven painted edges, light paper grain inside the painted areas. Stylised friendly characters
with simple rounded faces, small dot eyes, rosy cheeks and simplified hands, like a modern Indonesian
children's educational book. Not photorealistic, not semi-realistic, not 3D, not CGI, no glossy shading,
no anime. Warm muted palette: cream, warm sand, slate grey, muted sage green and soft terracotta, with ONE
small accent in medium blue #2563EB. Soft even morning light. Absolutely no text, letters, numbers,
logos, signatures or watermarks anywhere.

Square image on a real transparent background; isolated vignette with soft painted edges. Four large rounded building blocks arranged in a 2x2 grid in the centre: three in slate grey and warm sand are already in place, and a friendly simple painted hand entering from the upper right lowers the fourth block, painted in blue, into the empty slot. A few small sparkles around. No symbols on the blocks, no studs, no other people.
```

## Dark variant prompt (send right after the accepted light image, save as `fitur-tambahan-dark.png`)

```
Now make the dark-mode version of exactly the same illustration: same painting, composition,
characters and colours, on a real transparent background. Lift the outlines and light areas slightly and add
a faint light rim around the subject so it reads well on a very dark navy page (#111827). Do not invert the
colours. Still no text anywhere.
```

## Alt text (Indonesian, used in the view)

Susunan balok yang menggambarkan fitur yang bisa ditambahkan.
