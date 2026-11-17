# Module: Pengguna

Staff accounts and roles. Core module.

| Route | Permission | Purpose |
| --- | --- | --- |
| `GET /admin/pengguna` | `pengguna.view` | List; filters `q` (name, username, email), `peran`, `status` (`aktif`/`nonaktif`); 20 per page |
| `GET/POST /admin/pengguna/tambah` | `pengguna.create` | New account; temporary password shown once, change forced at first sign-in |
| `GET/POST /admin/pengguna/{id}/ubah` | `pengguna.edit` | Name, username, email, roles |
| `POST /admin/pengguna/{id}/atur-ulang-kata-sandi` | `pengguna.edit` | New temporary password; remember-me tokens removed |
| `POST /admin/pengguna/{id}/nonaktifkan`, `…/aktifkan` | `pengguna.edit` | Deactivate (Shield ban) / reactivate |
| `POST /admin/pengguna/{id}/matikan-dua-langkah` | `pengguna.edit` | Turn off a user's 2FA (lost phone) |

Rules (D-020): accounts holding, and the assignment of, `superadmin` or `admin` need
`pengguna.kelola-admin`; locked roles a user already has are kept on save. Users cannot deactivate,
reset or change roles of their own account. Audit events: `user_roles_changed`, `user_deactivated`,
`user_activated`, `password_reset`, plus model-level changes through `Auditable`.
