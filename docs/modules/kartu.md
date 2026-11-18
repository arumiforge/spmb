# Kartu (registration card)

Printable registration card with a QR code, a public card check page and a staff
lookup. Requirements FR-CARD-01…04; decisions D-030 (FPDF), D-034 (applicant access),
D-035 (availability). Requires the Pendaftaran module.

## Routes

| Method | URL | Who | Purpose |
| --- | --- | --- | --- |
| GET | `/kartu/<token>` | public, throttled 30/min per IP | Card check: number, name, wave, "Kartu sah" / "Kartu tidak berlaku". Staff signed in with `pendaftar.view` also get a link to `/admin/pendaftar/<id>`. Unknown/revoked → 404 page, throttled → 429 page. |
| GET | `/cek-status/kartu` | applicant with a grant (`service('applicantAccess')`) | Downloads the own card PDF when `CardAvailability` allows it, otherwise a friendly page. No grant → redirect to `/cek-status`. |
| GET | `/admin/pendaftar/<id>/kartu` | `kartu.cetak` | Prints the card (inline PDF); event `card_printed`. Not for cancelled applicants. |
| POST | `/admin/pendaftar/<id>/kartu/baru` | `kartu.cetak` | Revokes the current token and issues a new one (lost or misused card). |
| GET | `/admin/kartu?q=` | `kartu.cetak` | Lookup by pasted card URL, bare card code or registration number. |
| GET/POST | `/admin/pengaturan/kartu` | `pengaturan.pendaftaran` | Settings below; audited as `settings_updated` (subject `Kartu`). |

## Tokens (`card_tokens`)

`Modules\Kartu\Services\CardTokenService`:

- `tokenFor(int $applicantId, ?int $staffId = null): string` — the active token, or a
  new one (32 random bytes, base64url, 43 characters). Concurrent first prints are
  serialised with `SELECT … FOR UPDATE` on the applicant row. Reprinting reuses the token.
- `renew(int $applicantId, int $staffId): string` — sets `revoked_at` on the old token, issues a new one.
- `resolve(string $token): ?array` — the applicant row, or null when unknown, revoked or
  the applicant is deleted; updates `last_seen_at`.
- `isRevoked(string $token): bool`, `generate(): string`.

Audit: token writes skip the generic `created`/`updated` callbacks and log one event on
the applicant (`subject_type = applicants`): `card_issued`, `card_revoked`, `card_printed`
(new values carry only the card row id, never the token). `last_seen_at` is written
without an audit entry (a technical scan timestamp; one entry per scan would drown Riwayat).

## Availability

`CardAvailability::allows(array $applicant): bool` reads `setting('Kartu.availableWhen')`:
`immediately` (default) or `verified` (applicant `status = verified`). Cancelled or
deleted applicants never get a card. Staff with `kartu.cetak` can always print (except
cancelled registrations).

## PDF

`CardPdf::render(array $applicant, string $token): string` draws the card with FPDF on the
top half of an A4 page (A5 landscape area) with a dashed cut line, black on white so it
prints cleanly on a black-and-white printer: school logo (re-encoded to PNG through GD;
WebP works) and name from `School.*`, `Kartu.headerText`, academic year, number, name,
wave, previous school (`ref_sekolah` name or free text), method, test schedule
("Belum dijadwalkan" until phase 3), a box to glue a 3 × 4 photo, the QR and
`Kartu.footerNote`. The QR (`chillerlan/php-qrcode`, ECC M) is drawn as vector squares
from the QR matrix — sharp at any printer resolution, no temporary files — and holds only
`site_url('kartu/<token>')`. Text is converted to windows-1252 (transliterated) for the
FPDF core fonts; long values shrink to 7 pt and are then shortened. Rendering never writes data.
`CardPdf::details()` returns the same facts for the check page and the lookup.

## Panels

- `Views/panels/applicant_admin.php` (`['applicant' => row]`): card state (active since,
  last scanned), whether the applicant may download it, **Cetak Kartu** and
  **Buat Kartu Baru** (with confirmation). Renders nothing for staff without `kartu.cetak`.
- `Views/panels/applicant_public.php`: **Unduh Kartu**, or "Kartu bisa diunduh setelah
  berkas diperiksa panitia" / the cancelled message.

## Settings (`setting('Kartu.*')`, defaults in `app/Config/Kartu.php`)

| Key | Default | Meaning |
| --- | --- | --- |
| `availableWhen` | `immediately` | `immediately` or `verified` |
| `headerText` | `Kartu Pendaftaran Murid Baru` | Title under the school name (max 80) |
| `footerNote` | `Bawa kartu ini saat tes dan daftar ulang.` | Note at the bottom (max 150, optional) |

## Guides

`Guide/panitia.md` (print, renew, check, scan the
QR), `Guide/calon-murid.md` (download and print), `Guide/admin-sekolah.md` (settings).

## Tests

`tests/database/KartuServicesTest.php`, `tests/feature/KartuTest.php`
(fixtures in `tests/_support/Kartu/KartuFixtures.php`).
