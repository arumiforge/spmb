---
name: spmb-copy
description: Write friendly, non-technical Indonesian UI text, validation/error messages, notifications and per-role guide pages (/panduan) for SPMB. Use whenever user-facing text is written or reviewed.
---

# Indonesian copy for non-technical users

**Writing standard: the `bahasa-indonesia-plain` skill** (plain language, EYD, inclusive and
trauma-informed writing, numbers and dates, RASA check before you finish). Load it whenever
you write or review Indonesian text. This skill adds SPMB's audience, form of address,
glossary and guide structure. New and rewritten text uses sentence case for headings,
buttons and labels (`Simpan data`, `Daftar sekarang`); existing Title Case labels are
changed in one copy sweep later, not piecemeal (D-071).

Audience: school committee staff, the principal, parents and students. Only the
super admin may see technical language.

## Voice

- Polite, warm, short. Address the user as **Anda**; for students/parents
  public pages may use "Bapak/Ibu" in guidance text.
- One idea per sentence. Lead with what the user should do.
- Use active verbs on buttons: `Daftar sekarang`, `Simpan data`, `Unduh kartu`,
  `Cetak ulang kartu`, `Cari pendaftar`.
- Numbers and dates in Indonesian format: `17 November 2026`, `Rp150.000`, `08.00 WIB`.

## Avoid → use

| Avoid | Use |
| --- | --- |
| Submit | Kirim / Simpan |
| Error / Failed | Belum berhasil |
| Invalid input | Isian belum sesuai |
| Upload | Unggah |
| Download | Unduh |
| Login / Logout | Masuk / Keluar |
| Dashboard (non-admin) | Beranda |
| User | Pengguna |
| Record / Data entry | Data |
| Database, server, token, session, cache | (do not mention; explain the effect instead) |
| 404 Not Found | Halaman tidak ditemukan |
| 403 Forbidden | Anda tidak memiliki akses ke halaman ini |
| 2FA / TOTP | Verifikasi dua langkah / kode dari aplikasi Google Authenticator |

## Messages

- Validation: say which field and how to fix: "NIK harus 16 angka." not "NIK invalid".
- Errors: what happened + what to do + who to contact: "Data belum tersimpan.
  Periksa koneksi internet lalu coba lagi. Jika masih gagal, hubungi panitia."
- Success: confirm the result: "Data pendaftar berhasil disimpan. Nomor pendaftaran: PMB-2027-0012."
- Destructive confirm: name the thing: "Hapus jadwal tes Hari ke-2? Peserta di jadwal ini akan menjadi belum terjadwal."

## Guide pages (`/panduan`)

One guide per role, written in Indonesian, served at `/panduan/<role-slug>`
(e.g. `/panduan/calon-murid`, `/panduan/panitia-pendaftaran`, `/panduan/admin-sekolah`).
Each guide:
1. "Apa yang bisa Anda lakukan" — bullet list of tasks.
2. One section per task: numbered steps (max 7), screenshot/illustration placeholder,
   tip box, common problems ("Jika ... , maka ...").
3. Plain words; link to the actual page for each task.
Sources live in each module's `Guide/` folder as Markdown; the Panduan module
assembles them by role and permission.

All strings go in `Language/id/*.php`; never hard-code text in views.
