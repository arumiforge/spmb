---
id: 024
name: tanya-jawab
kind: painting
area: section
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-pages
status: requested
---

# 024 — tanya-jawab

## Purpose

Says "ask us anything, we are happy to help": a friendly help desk on the school veranda.

## Where it is used

Public page `/tanya-jawab` (questions and answers), beside the heading. Installed trimmed to the subject, longest side ≤ 800 px. Desktop (≥ 1024 px): beside the page heading, about 320–380 px wide, heading and intro text on the left. Phones (360 px): above the heading, about 200 px wide, centred. It must read clearly at 200 px.

## Composition

- Square vignette. A small help desk in the centre; empty speech-bubble shapes float above (no text inside).

## Characters (cast in `ai/style-guide.md`)

- Pak Budi behind the desk answers with a warm smile and an open-hand gesture.
- Ibu Sari in front of the desk, asking, holding a phone.
- On a bench to the side: Mak Ijah with Daeng Arif, waiting and chatting.

## Setting and props

- A wooden desk with a small potted plant and a closed notebook; two or three empty rounded speech bubbles in cream and sage; veranda arches behind.

## Colour and light

Cream and sand with the green family; blue accent on one speech bubble only.

## Avoid

- Question marks or any symbol inside the bubbles
- Physical contact between the male teacher and the mother
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. On a school veranda with simple arches, a male teacher in a black peci, glasses and a light batik-like shirt sits behind a small wooden desk with a potted plant and a closed notebook, answering with a warm smile and an open-hand gesture. In front of the desk a mother in a white kerudung and a sand-coloured long tunic asks a question, holding a phone. Above them float three empty rounded speech bubbles, two in cream and sage and one in medium blue, with nothing inside. On a bench to the side, a grandmother in a dark green long kerudung and a Bugis boy in a white baju koko, black peci and deep green check sarung wait and chat. Friendly, helpful mood, compact composition, generous empty margin.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/tanya-jawab.webp` | `section/tanya-jawab.webp` |
| dark | `ai/generated/tanya-jawab-dark.webp` | `section/tanya-jawab-dark.webp` |
| hijau | `ai/generated/tanya-jawab-hijau.webp` | `section/tanya-jawab-hijau.webp` |
| dark-hijau | `ai/generated/tanya-jawab-dark-hijau.webp` | `section/tanya-jawab-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Petugas sekolah menjawab pertanyaan orang tua dengan ramah.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 024` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Speech bubbles are completely empty (no question marks, dots or letters)
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
