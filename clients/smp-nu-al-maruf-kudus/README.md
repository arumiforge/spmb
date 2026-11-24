# Client: SMP NU Al Ma'ruf Kudus

First client of SPMB (academic year 2027/2028). This folder is **data for one
installation**, not code: the application stays white label (D-050, D-054).

| Item | Value |
| --- | --- |
| Domain | `spmbsmpnualmarufkudus.web.id` |
| Parent organisation | LP Ma'arif NU (logo in the website footer) |
| Brand colour | `#005c2f` (green of the school logo; NU green family), accent yellow `#f5c400` from the logo, used in the share image |
| Cloudflare | Arumi Studios' account (developer) |
| Hostnames | production `spmbsmpnualmarufkudus.web.id`, staging `staging.…`, console `konsol.…`, SSH `ssh.…` (tunnel, `docs/deployment.md`) |

## Files

- `originals/` — logos as delivered by the school (do not edit): square logo, landscape logo for light
  and dark backgrounds, LP Ma'arif NU logo.
- `branding/` — the branding pack loaded into the app: trimmed/resized logos, `favicon.png` (64 px),
  `icon.png` (512 px, white background, home-screen icon), `share.png` (1200 × 630 link preview) and
  `branding.json` (settings + image file names).
- `berkas-contoh/` — example files applicants download next to a document type ("Unduh contoh"),
  named `<document type code>.<pdf|doc|docx|jpg|png>`. `sk-aktif.doc` is the school's letter
  template for "Surat Keterangan Aktif dari SD/MI" (letterhead of the SD/MI, principal's statement
  that the child is still a pupil, school year 2026/2027). `spmb:branding` installs each file and
  switches it on when that type has no example yet; the school can replace or hide it in
  Pengaturan → Berkas Wajib. Only files in this folder: anything else is skipped with a note.
- `source/share.html` — source of `share.png`. Re-render with Playwright at 1200 × 630 from inside
  `source/` after changing the year or text.

## Load into an installation

On the server (`docs/deployment.md`; the pack is inside the image):

```bash
spmb spark production spmb:install          # migrations, Super Admin (first time only)
spmb spark production spmb:branding clients/smp-nu-al-maruf-kudus/branding
```

Locally (Laragon): `php spark spmb:install` and `php spark spmb:branding clients/smp-nu-al-maruf-kudus/branding`.

The same command loads `tanya-jawab.json` (only into an installation without questions) and the
files in `berkas-contoh/` (see above). Running it again is safe.

Then the school completes Pengaturan → Identitas Sekolah (address, phone, WhatsApp, email) and enters
its own waves (Pengaturan → Gelombang). Everything stays editable in the admin.
