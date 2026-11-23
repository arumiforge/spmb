---
id: 035
name: bagikan-umum
kind: painting
area: share
generate: landscape 1536x1024
background: painted
variants: light, hijau
batch: paintings-pages
status: done
---

# 035 — bagikan-umum

## Purpose

A default link-preview image for schools that have not uploaded their own: warm, generic, clearly about school admission, with no logo or text.

## Where it is used

The `og:image` / `twitter:image` fallback in the public layout. Exported as 1200×630 WebP and JPEG for link previews (WhatsApp, Facebook) when the school has not uploaded its own share image. Previews often crop to a square in the centre, so the important part sits in the middle 630×630.

## Composition

- Full-bleed landscape scene; generate 1536×1024 and crop the centre band to 1200×630.
- The key subjects sit in the middle 630×630 square of the crop; the rest is soft sky and garden.

## Characters (cast in `ai/style-guide.md`)

- In the centre: Aisyah and Raka walking towards the viewer side by side, waving, with Nurul and Fajar just behind them.

## Setting and props

- A school courtyard with a frangipani tree, a covered walkway and terracotta roofs; morning sky in soft cream and pale blue-green.

## Colour and light

Cream sky, green family trees, terracotta roofs, warm gold light; blue accent on Aisyah's phone or a backpack strap.

## Avoid

- Logos, school names, text of any kind
- Important details outside the centre square
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

Landscape image. Full-bleed painted scene that fills the whole frame (no transparency, no white border). A sunny school courtyard in the morning with a frangipani tree, a covered walkway and terracotta roof tiles; the sky in soft cream with a hint of pale blue-green. In the centre, four 12-year-old students walk towards the viewer, smiling and waving: a girl in a white kerudung with a sage backpack, a boy in a black peci, a girl from Papua with darker brown skin, cream kerudung and round glasses, and a Chinese-Indonesian boy in a black peci; all in white shirts with navy skirts or trousers. One small medium-blue element (a backpack strap). Keep all people inside the middle square of the image; the left and right thirds are only garden and sky.
```

Variants: green accent only (§9). No dark variant: the share image is never shown on the page.

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/bagikan-umum.webp` | `share/bagikan-umum.webp` + `.jpg`, 1200×630 |
| hijau | `ai/generated/bagikan-umum-hijau.webp` | `share/bagikan-umum-hijau.webp` + `.jpg`, 1200×630 |

## Alt text (Indonesian, used in the view)

Murid-murid berjalan bersama di halaman sekolah pada pagi hari.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 035` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] After cropping to 1200×630 and then to a centre square, all four students stay visible
- [ ] JPEG and WebP both delivered
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
