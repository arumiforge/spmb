---
id: NNN
name: <kebab-name>                      # file base name, Indonesian kebab-case
kind: painting
area: hero | section | guide | state | share | ikon | langkah
generate: landscape 1536x1024 | portrait 1024x1536 | square 1024x1024
background: transparent                 # new paintings are always transparent (D-083)
variants: light, hijau                  # no dark copies from brief 036 on (D-083)
batch: <batch-slug>                     # pull request group, e.g. paintings-pages
status: requested                       # requested → generated (Codex) → done (Claude)
---

# NNN — <kebab-name>

## Purpose

<What the viewer should feel or understand.>

## Where it is used

<Route and component; where the HTML text sits; how big it shows at 360 px and at 1280 px.>

## Composition

- <Where the subject sits, which area stays empty.>

## Characters (cast in `ai/style-guide.md`)

- <Cast members, clothing, pose, expression. "None." for object-only images.>

## Setting and props

- <Objects and background; screens and papers show only abstract shapes.>

## Colour and light

<Main colours and where the single blue accent goes.>

## Avoid

- <Things specific to this image>
- Everything in the style guide: "Respect for religious elements", "Never" and "Rules for every painting"

## Prompt

```
<base style block, style guide §7, unchanged>

<light-and-dark block, style guide §10, unchanged>

<one self-contained paragraph: shape, background, composition, characters, setting, mood>
```

Variant: green accent (§9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/<name>.webp` | `<area>/<name>.webp` |
| hijau | `ai/generated/<name>-hijau.webp` | `<area>/<name>-hijau.webp` |

## Alt text (Indonesian, used in the view)

<One short plain sentence that says what the picture shows. No "Gambar …" or "Ilustrasi …". Use `alt=""` only when the brief says the image is decorative.>

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py NNN` passes
- [ ] Empty area really empty; no text, letters, Arabic-like marks or emblems anywhere
- [ ] Reads on `#FFFFFF` and on `#111827`; no glow, halo or light rim
- [ ] <brief-specific checks>
