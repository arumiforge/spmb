# Illustration Style Guide

All UI illustrations are generated with ChatGPT (image generation) from the
prompts in `prompts/`. Paste this **base style block** at the start of every prompt
(the prompt files already include it).

## Base style block

```
Flat vector illustration, modern and friendly, soft rounded shapes, subtle
gradients, clean white or transparent background, generous empty space.
Limited neutral palette: slate grey, soft white, with ONE accent colour in
medium blue (#2563EB) used sparingly so it can be recoloured for other schools.
Characters: Indonesian junior-high students (age 12–13) and parents, diverse
skin tones, some girls wearing a white hijab, school uniform white shirt with
dark navy trousers/skirt, no visible school badge or logo.
No text, no letters, no numbers, no logos, no watermarks, no brand names.
Consistent line weight, no photorealism, no 3D render.
```

## Rules

- **White label**: no school names, logos, building signs, flags with text or
  religious calligraphy that would tie the image to one school.
- **No text inside images** — all text is rendered by the app in Indonesian.
- Calm, welcoming mood for parents; avoid exam stress imagery.
- Provide both light and dark variants only when the prompt asks for it
  (dark: same illustration on transparent background with lighter line colour).
- Delivered as PNG at the requested pixel size into `ai/output/<file-name>.png`.

## Sizes

| Usage | Aspect | Request size | Final asset (WebP) |
| --- | --- | --- | --- |
| Public hero | 16:9 | 1792×1024 | max 1600 px wide |
| Side illustration (auth, form) | 3:4 | 1024×1365 | max 800 px wide |
| Empty / success / error state | 1:1 | 1024×1024 | 480 px |
| Guide cover per role | 16:9 | 1792×1024 | 1200 px |
