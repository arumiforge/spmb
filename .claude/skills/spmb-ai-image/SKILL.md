---
name: spmb-ai-image
description: Request UI/UX illustrations for SPMB by writing ChatGPT image-generation prompts into the ai/ folder, then process delivered images (resize, WebP) into public assets. Use whenever a page needs an illustration, hero, empty-state or guide image.
---

# AI image workflow

Claude does not generate images. It writes prompts the user runs in ChatGPT,
then integrates the results.

## 1. Write the prompt

Create `ai/prompts/<NNN>-<slug>.md` (next free number) using `ai/prompts/_template.md`.
Follow `ai/style-guide.md` (painted storybook style, D-027; muted palette, recurring cast, no text in image, no logos,
diverse Indonesian students incl. hijab, white-label friendly). Fill in:
target file name, usage (page + component), aspect ratio, pixel size, background
(transparent or solid), light/dark variant needed.

Add a row to `ai/README.md` → "Queue" with status `requested`.

## 2. User generates

The user pastes the prompt into ChatGPT, saves the result as
`ai/output/<target-file-name>.png` and tells you.

## 3. Process

- Verify size/aspect; ask for a regeneration if it breaks the style guide.
- Convert and resize into `public/assets/img/<area>/` (WebP, plus PNG fallback
  only if transparency issues): max 1600 px wide for heroes, 640 px for empty
  states/guide images. Use `php spark image:optimize` once it exists, or
  Python Pillow / CI4 Image library.
- Reference the image from the view with `alt` text in Indonesian.
- Update the queue row to `done` with the final asset path.

Keep originals in `ai/output/` out of git if larger than 2 MB (see `.gitignore`).
