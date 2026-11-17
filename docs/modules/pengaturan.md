# Module: Pengaturan

School settings. Core module.

| Route | Permission | Purpose |
| --- | --- | --- |
| `GET/POST /admin/pengaturan/sekolah` | `pengaturan.sekolah` | Name, short name, tagline, address, phone, WhatsApp, email, website, brand colour, time zone label, footer text, logo, favicon |
| `GET/POST /admin/pengaturan/keamanan` | `sistem.keamanan` | Roles that must use two-step verification |

Values are stored with `setting('School.*')` and `setting('TwoFactor.requiredRoles')`; defaults in
`Config\School` and `Config\TwoFactor`. Logo (PNG/JPG/WebP ≤ 1 MB, scaled to 512 px) and favicon
(PNG/ICO ≤ 200 KB) are saved under random names in `writable/uploads/branding/` and served by
`App\Controllers\Branding` at `/aset/logo` and `/aset/favicon` (image types only, `nosniff`).
Every change writes a `settings_updated` audit entry with old and new values.
