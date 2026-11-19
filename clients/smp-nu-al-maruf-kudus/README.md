# Client: SMP NU Al Ma'ruf Kudus

First client of SPMB (academic year 2027/2028). This folder is **data for one
installation**, not code: the application stays white label (D-050, D-054).

| Item | Value |
| --- | --- |
| Domain | `spmbsmpnualmarufkudus.web.id` |
| Parent organisation | LP Ma'arif NU (logo in the website footer) |
| Brand colour | `#005c2f` (green of the school logo; NU green family), accent yellow `#f5c400` from the logo, used in the share image |
| Cloudflare | Arumi Studios' account (developer) |

## Files

- `originals/` — logos as delivered by the school (do not edit): square logo, landscape logo for light
  and dark backgrounds, LP Ma'arif NU logo.
- `branding/` — the branding pack loaded into the app: trimmed/resized logos, `favicon.png` (64 px),
  `icon.png` (512 px, white background, home-screen icon), `share.png` (1200 × 630 link preview) and
  `branding.json` (settings + image file names).
- `source/share.html` — source of `share.png`. Re-render with Playwright at 1200 × 630 from inside
  `source/` after changing the year or text.

## Load into an installation

```bash
php spark spmb:install                       # migrations, Super Admin
php spark spmb:branding clients/smp-nu-al-maruf-kudus/branding
```

Then the school completes Pengaturan → Identitas Sekolah (address, phone, WhatsApp, email) and enters
its own waves (Pengaturan → Gelombang). Everything stays editable in the admin.
