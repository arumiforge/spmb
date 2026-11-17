---
id: NNN
file: <kebab-name>.png            # ChatGPT output, saved as ai/output/<file>
target: public/assets/img/<area>/<kebab-name>.webp
usage: <page / component where it appears, and where UI text sits next to it>
shape: landscape | portrait | square
size: 1536x1024 | 1024x1536 | 1024x1024
background: transparent | white
variants: light | light+dark
status: requested | generated | done
---

# NNN — <kebab-name>

## Purpose

<What the image should make the viewer feel or understand.>

## Composition

- <Shape, where the subject sits, which area stays empty for UI text.>

## Characters (see cast in `ai/style-guide.md`)

- <Cast member, clothing, pose, expression. "None." for object-only images.>

## Setting and props

- <Objects, background elements; screens and papers show only abstract shapes.>

## Colour and light

<Main colours and where the single blue accent goes.>

## Avoid

- <Things specific to this image>
- Everything listed under "Never" and "Rules for every image" in the style guide

## Prompt (paste into ChatGPT)

```
<base style block from ai/style-guide.md, unchanged>

<one paragraph: shape, background, composition, characters, setting, mood — self-contained>
```

## Dark variant prompt (only for light+dark; save as `<kebab-name>-dark.png`)

```
<dark variant block from ai/prompts/003-masuk-dasbor.md>
```

## Alt text (Indonesian, used in the view)

<short description in Indonesian>
