---
id: 020
name: beranda-kebersamaan
kind: painting
area: section
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-pages
status: requested
---

# 020 — beranda-kebersamaan

## Purpose

Shows Bhinneka Tunggal Ika in school life: children from different regions learning together, happily and as equals.

## Where it is used

Public home page (`/`), section about the school's character (text from settings). Also usable on `/panduan` for applicants. Installed trimmed to the subject, longest side ≤ 800 px. Desktop (≥ 1024 px): beside the page heading, about 320–380 px wide, heading and intro text on the left. Phones (360 px): above the heading, about 200 px wide, centred. It must read clearly at 200 px.

## Composition

- Square vignette, subject centred, about 12 % empty margin on every side.
- Compact group so it reads at 200 px.

## Characters (cast in `ai/style-guide.md`)

- Aisyah, Nurul, Raka, Fajar and Daeng Arif sit around a low wooden table (dampar) on a veranda, sharing books and a tablet, laughing together.
- Nurul wears her round glasses. Everyone in school uniform; Daeng Arif in white baju koko and a deep green check sarung.

## Setting and props

- Wooden veranda floor, a woven pandan mat under the table, a potted plant and a frangipani branch in one corner.
- Books are closed or open with only abstract lines; the tablet screen shows abstract blocks.

## Colour and light

Warm cream and sand with the green family; the blue accent only on the tablet screen and one book cover.

## Avoid

- Regional costume parade
- A mushaf on the floor or on the mat (keep books generic; if any book looks like a Qur'an, put it on a rehal on the table)
- Shoes on the mat
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. Five 12-year-old students sit cross-legged around a low wooden table on a woven pandan mat on a school veranda and learn together, laughing: a Javanese girl in a white kerudung, a girl from Papua with darker brown skin, a cream kerudung and round glasses, a Sundanese boy in a black peci, a Chinese-Indonesian boy in a black peci, and a Bugis boy in a white baju koko and a deep green check sarung. The girls wear white shirts and navy skirts, the boys white shirts and navy trousers. They share generic books with abstract lines and a tablet whose screen shows abstract blue blocks. A potted plant and a frangipani branch at one side. Compact centred group with generous empty margin, joyful and equal.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/beranda-kebersamaan.webp` | `section/beranda-kebersamaan.webp` |
| dark | `ai/generated/beranda-kebersamaan-dark.webp` | `section/beranda-kebersamaan-dark.webp` |
| hijau | `ai/generated/beranda-kebersamaan-hijau.webp` | `section/beranda-kebersamaan-hijau.webp` |
| dark-hijau | `ai/generated/beranda-kebersamaan-dark-hijau.webp` | `section/beranda-kebersamaan-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Murid dari berbagai daerah belajar bersama dengan gembira.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 020` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Five distinct faces, skin tones and hair, none caricatured
- [ ] Reads clearly at 200 px wide
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
