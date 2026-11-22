---
id: NNN
name: <kebab-name>                      # file base name, Indonesian kebab-case
kind: painting
area: hero | section | guide | state | share
generate: landscape 1536x1024 | portrait 1024x1536 | square 1024x1024
background: transparent | white
variants: light, dark, hijau, dark-hijau
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

<one self-contained paragraph: shape, background, composition, characters, setting, mood>
```

Variants: dark (§8), green accent (§9), dark green accent (§9 on the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/<name>.webp` | `<area>/<name>.webp` |
| dark | `ai/generated/<name>-dark.webp` | `<area>/<name>-dark.webp` |
| hijau | `ai/generated/<name>-hijau.webp` | `<area>/<name>-hijau.webp` |
| dark-hijau | `ai/generated/<name>-dark-hijau.webp` | `<area>/<name>-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

<One short plain sentence that says what the picture shows. No "Gambar …" or "Ilustrasi …". Use `alt=""` only when the brief says the image is decorative.>

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py NNN` passes
- [ ] Empty area really empty; no text, letters, Arabic-like marks or emblems anywhere
- [ ] <brief-specific checks>
