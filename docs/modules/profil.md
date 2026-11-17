# Module: Profil

Own account pages for every signed-in staff member (no permission needed). Core module.

| Route | Purpose |
| --- | --- |
| `GET /admin/profil` | Account data, roles, security status |
| `GET/POST /admin/profil/kata-sandi` | Change password (current password required); target of forced change |
| `GET /admin/profil/verifikasi-dua-langkah` | 2FA status, or setup (QR + manual key; unconfirmed secret reused on reload) |
| `POST …/verifikasi-dua-langkah/aktifkan` | Confirm with a code → recovery codes |
| `GET …/verifikasi-dua-langkah/kode-cadangan` | Recovery codes, shown once (`Cache-Control: no-store`) |
| `POST …/verifikasi-dua-langkah/kode-cadangan` | New recovery codes (password required) |
| `POST …/verifikasi-dua-langkah/matikan` | Turn 2FA off (password required; refused when the role requires 2FA) |

Audit events: `password_changed`, `2fa_enabled`, `2fa_disabled`, `2fa_recovery_regenerated`, `2fa_recovery_used`, `2fa_failed`.
Strings: `app/Language/id/Akun.php` (shared with the sign-in screens). Guide: `Guide/semua-petugas.md`.
