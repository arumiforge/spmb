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

## 2. ChatGPT generates and delivers (D-028)

The user starts ChatGPT with the starter prompt in `ai/README.md`. ChatGPT follows `ai/CHATGPT.md`
and opens one pull request from `art/illustrations-<date>` that only adds files to `ai/incoming/`
(plus `ai/incoming/DELIVERY.md`). It never touches anything else and never merges. If ChatGPT cannot
open a pull request, the user attaches the images in chat or uploads them to `ai/incoming/` on a branch.

## 3. Review and install (Claude, on the same pull request)

- Find it: `git fetch --all` and look for `origin/art/*`, or list open pull requests.
- Check out the branch. Commits made through GitHub by the `arumiforge` account pass the commit policy;
  if the policy check fails, report the author/trailer it names to the user instead of rewriting history.
- Review each file against the brief and `ai/style-guide.md` (look at every image): painted, not
  realistic or 3D; no text; the empty area is empty; real transparency where required; right shape and
  name. For a failing image, ask the user for a regeneration with a one-line correction to paste into
  ChatGPT, and install only the approved ones.
- Convert and resize into each brief's `target` under `public/assets/img/<area>/` (WebP, keeping
  transparency): heroes max 1600 px wide, guide covers 1200 px, side illustrations 800 px, empty/
  success/error states 480 px (see `ai/style-guide.md`). Use Python Pillow or the CI4 Image library.
- Reference the image from the view with the brief's Indonesian `alt` text; dark variants via
  `dark:hidden` / `hidden dark:block` pairs.
- Update the queue row in `ai/README.md` to `done`.
- Delete the installed originals from `ai/incoming/` (keep `README.md`), update the PR description with
  what was installed and what is still waiting, and **squash-merge** when CI is green and the user agrees,
  so the large originals never enter the history of `main`.
