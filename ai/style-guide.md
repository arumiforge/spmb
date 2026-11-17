# Illustration Style Guide

All UI illustrations are generated with ChatGPT image generation from the prompt files in
`prompts/`. ChatGPT follows [CHATGPT.md](CHATGPT.md), which points here first. Style decision: D-027.

## Look: hand-painted storybook illustration

The images must look **painted by an illustrator**, like a modern Indonesian children's
educational book — not like photos, not like 3D renders, not like real people.

- Medium: soft **gouache and watercolour** painting, visible but gentle brush texture, slightly uneven
  painted edges, light paper grain inside the painted areas.
- People are **stylised**: simple rounded faces, small dot or almond eyes, a short curved line for the
  mouth, rosy cheeks, no nose detail or only a tiny stroke, no realistic skin pores, wrinkles or hair strands.
  Proportions slightly chunky and friendly (heads a little large, hands simplified).
- Shapes are simplified; objects are drawn with a few confident painted strokes.
- Soft, even morning light; gentle painted shadows, no hard specular highlights.

**Never**: photorealism, semi-realistic portraits, 3D / CGI / clay / Pixar-like rendering, glossy
plastic shading, anime or manga style, vector clip-art with hard flat edges, lens blur, depth-of-field
photography effects.

## Palette

Warm, calm and muted, so the school colour in the app stays the strongest colour on the page.

| Role | Colours |
| --- | --- |
| Base neutrals | cream `#F7F1E3`, warm sand `#E9DCC3`, slate grey `#64748B`, soft charcoal `#334155` for lines |
| Nature | muted sage green `#9CB79A`, soft olive `#7D9471`, muted terracotta `#C98B6B` (roof tiles, pots) |
| Accent (only one) | medium blue `#2563EB`, used sparingly on 1–3 small elements (an icon, a card, a block) so it can be recoloured for other schools |
| Uniform | white shirts, navy (biru dongker) `#1E2A4A` trousers and skirts |

No large areas of saturated colour; no rainbow palettes; no red-and-white flags.

## Cast (keep the same characters across images)

Names are only references for consistency; never write them in an image.

| Name | Who | Look |
| --- | --- | --- |
| Aisyah | applicant girl, 12 | white hijab, white school shirt, navy skirt, small backpack in sage green |
| Raka | applicant boy, 12 | short black hair, white school shirt, navy trousers |
| Ibu Sari | mother | white hijab with a soft sand-coloured long tunic |
| Pak Hadi | father | short hair, simple batik-like shirt with an abstract sage and sand pattern (no letters) |
| Bu Nur | school staff | white hijab, navy cardigan over a cream blouse, lanyard with a blank card |
| Pak Budi | school staff | short hair, light batik-like shirt with an abstract pattern, glasses |

Skin tones: warm Indonesian tones, varied between characters.

## Rules for every image

- **No text at all**: no letters, numbers, logos, watermarks, signatures, school names, signboards with
  writing, calligraphy or brand names. Screens, papers, cards and signs show only abstract blocks and lines.
- **White label**: no school badge on uniforms, no recognisable building, no flags.
- Calm and welcoming mood; no stress, tears, exams or anything broken or dangerous.
- Generous empty space as described in each prompt (the app puts text next to the image, never on it).
- **Transparent** background means a real transparent PNG (alpha channel) with the painting as an
  isolated vignette with soft painted edges — no paper texture or colour wash outside the subject,
  no drawn checkerboard. **White** background means plain white `#FFFFFF`.

## Dark variants

Some images need a second version for dark mode (`<name>-dark.png`): the same painting and composition
on a transparent background, with the outlines and light areas lifted slightly and a faint light rim around
the subject so it reads on a very dark navy page (`#111827`). Do not invert colours.

## Sizes

ChatGPT offers three shapes; exact pixels do not matter, Claude crops and resizes to the final asset.

| Usage | Shape to request | ChatGPT size | Final asset (WebP) |
| --- | --- | --- | --- |
| Public hero, guide cover | landscape | 1536×1024 | max 1600 px / 1200 px wide |
| Side illustration (sign-in, form) | portrait | 1024×1536 | max 800 px wide |
| Empty / success / error state | square | 1024×1024 | 480 px |

## Base style block

Every prompt file already starts with this block; it is repeated here for reference.

```
Hand-painted storybook illustration in soft gouache and watercolour, visible gentle brush texture,
slightly uneven painted edges, light paper grain inside the painted areas. Stylised friendly characters
with simple rounded faces, small dot eyes, rosy cheeks and simplified hands, like a modern Indonesian
children's educational book. Not photorealistic, not semi-realistic, not 3D, not CGI, no glossy shading,
no anime. Warm muted palette: cream, warm sand, slate grey, muted sage green and soft terracotta, with ONE
small accent in medium blue #2563EB. Soft even morning light. Absolutely no text, letters, numbers,
logos, signatures or watermarks anywhere.
```
