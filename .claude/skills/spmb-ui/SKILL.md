---
name: spmb-ui
description: Build views for SPMB with Tailwind v4 + Flowbite — full-width responsive admin layout, public site layout, breadcrumbs, tables, forms, empty states, white-label theming. Use for any page, component or layout work.
---

# UI conventions

## Layouts

- `app/Views/layouts/admin.php` — **full width** (no max-width container on the
  content area), collapsible sidebar (drawer on mobile), top navbar with user
  menu, breadcrumb bar under the navbar, content slot, toast area.
- `app/Views/layouts/public.php` — public site for parents/students: header,
  footer, large tap targets, mobile first.
- `app/Views/layouts/auth.php` — login, 2FA, password reset.
- Shared partials in `app/Views/partials/`: `breadcrumbs`, `flash`, `empty_state`,
  `form_field`, `status_badge`, `pagination`, `school_brand`, `head`. Each takes **one array**
  (`view('partials/form_field', ['field' => [...]])`, `['badge' => ...]`, `['empty' => ...]`)
  because CI shares view data between renders; never rely on loose variables in a partial.
- Admin pages pass `title`, optional `subtitle`, `breadcrumbs`; buttons next to the title go in
  section `actions`. Destructive forms use `data-confirm="..."`; copy buttons `data-copy="..."`.
- Illustrations: `illustration('state/kosong-data', lang('App.altNoData'), 'h-32 w-auto')` prints the painted
  WebP and its dark variant; empty states pass `'image' => 'state/...'` to `partials/empty_state`. New images
  are requested through `spmb-ai-image`.
- Icons: `icon('users', 'h-5 w-5')` (Flowbite Icons, MIT) from `ui_helper`. Dates: `tanggal_indo()`.
- After adding classes in views run `npm run build` (Tailwind scans `app/Views`, `app/Modules`).

## Breadcrumbs (required on every page except the home page)

Controllers pass `$breadcrumbs = [['label' => 'Beranda', 'url' => '/admin'], ['label' => 'Data Pendaftar', 'url' => '/admin/pendaftar'], ['label' => 'Detail']]`.
Rules: first item is always the home of the area (`Beranda`/`Dasbor`); last item
is the current page and not a link; labels are plain Indonesian nouns, max ~3 words;
on mobile collapse middle items into "…". Use Flowbite breadcrumb markup with `aria-label="Breadcrumb"`.

## Responsive

Mobile first. Test at 360, 768, 1280, 1920 px. Tables become stacked cards
under `md` or scroll horizontally inside their own container — never the page.
Admin content uses the full width on large screens; forms cap at a readable width (`max-w-3xl`).

## White label / theming

- Colours come from settings as CSS variables (`--color-primary-*`) set in the
  layout `<head>`; Tailwind v4 `@theme` maps `primary` to those variables.
  Never hard-code a school colour or name in a view.
- Logo, favicon, school name, footer text from settings.
- Support dark mode via Flowbite's `dark:` classes.

## Components

Prefer Flowbite components (open-source `flowbite` package). Flowbite Pro
templates in `flowbite/` (private repo, licensed) may be used as a markup
reference: copy the minimum, adapt to our partials, never copy whole pages or assets.
Charts: ApexCharts. Tables: server-side pagination + filters in the query string so
filtered views are shareable and exportable.

## States every screen must handle

Loading, empty (friendly illustration from `ai/` + one clear action), error
(plain message + what to do), success (toast), no-permission (explain, link back).

## Accessibility

Labels on every input, visible focus, contrast AA, status never by colour alone
(badge text + colour), buttons say the action (`Simpan Data`, not `Submit`).

Copy rules: see `spmb-copy`. Missing illustration: see `spmb-ai-image`.
