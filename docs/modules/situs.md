# Situs (public information pages)

Public pages for parents and students (FR-WEB-01 Persyaratan and Jadwal Gelombang,
FR-WEB-02 Indonesian slugs) and the registration actions on the home page.
Requires the Pendaftaran module. No admin screen in phase 2: the pages read waves and
document types.

## Routes

| URL | Content |
| --- | --- |
| `/persyaratan` | Active document types in `sort_order` (required / optional, "boleh menyusul", formats, size limit) while the Berkas module is on, then the registration steps (upload and card steps only when Berkas / Kartu are on). Empty state when Berkas is off or no type is active. Uses `DocumentTypeModel::active()` when available, else `is_active = 1` ordered by `sort_order`, `name`. |
| `/jadwal` | Active waves of `setting('Pendaftaran.academicYear')` with state *Sedang dibuka* / *Akan dibuka* / *Sudah ditutup*, dates, quota and description, plus a banner. Empty state without waves. |
| `/` (`App\Controllers\Home`, `app/Views/home.php`) | "Daftar Sekarang" while online registration is open, "Cek Status" whenever Pendaftaran is on, "Lihat Persyaratan" when Situs is on, the current/next wave line and three short steps. With Pendaftaran off it only points to the guide. |

## WaveSchedule

`Modules\Situs\Services\WaveSchedule` (dates inclusive, today from `Time::today()` in the
app timezone):

- `forYear(?string $year = null, ?string $today = null)` — active waves with `state`.
- `current()`, `next()` — the open / next upcoming wave.
- `onlineOpen()` — `Pendaftaran.onlineOpen` and a wave covers today.
- `banner()` — `open`, `offline` (wave running, online form switched off: come to the
  school), `upcoming` or `closed`, with the Indonesian text.

## Tests

`tests/feature/SitusTest.php`, `tests/feature/HomeTest.php`.
