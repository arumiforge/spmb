---
id: NNN
name: <kebab-name>
kind: svg
batch: <batch-slug>
files: img/<area>/<file>.svg, css/<file>.css   # paths under ai/generated/install/, comma separated
status: requested
---

# NNN — <kebab-name>

## Purpose

<What it adds to the page.>

## Where it is used

<Routes, components, how the HTML uses it (class names, inline or mask).>

## Design

<Motif, geometry, tile size, density, references to style guide §5. What to avoid.>

## Files

| File (`ai/generated/install/…`) | What |
| --- | --- |
| `img/<area>/<file>.svg` | … |
| `css/<file>.css` | … |

## CSS contract

<Class names, custom properties, light/dark behaviour, states, reduced motion.>

## Alt text / accessibility

<Decorative assets: `aria-hidden="true"`, no alt. Anything that carries meaning: the Indonesian text the HTML provides.>

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py NNN` passes
- [ ] Demo page shows it in light/dark and blue/green at 360 and 1280 px without overflow
- [ ] <brief-specific checks>
