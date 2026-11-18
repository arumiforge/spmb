# Module: Berkas

Applicant documents (FR-DOC-01…04, D-036): which documents are required, upload
by applicants or staff, safe storage, the committee's check, and the overall
document status of each applicant. Requires the Pendaftaran module.

## Routes

| Route | Permission | Purpose |
| --- | --- | --- |
| `GET/POST /admin/pengaturan/berkas` | `pengaturan.pendaftaran` | Document types list (order, edit, delete) and image settings `Berkas.imageMaxEdge`, `imageQuality`, `deadline` (audited `settings_updated`) |
| `GET/POST /admin/pengaturan/berkas/tambah`, `…/{id}/ubah` | `pengaturan.pendaftaran` | Add / edit a document type (name, code, description, required, may follow, max size, formats JPG/PNG/PDF, shown or hidden) |
| `POST /admin/pengaturan/berkas/{id}/pindah` (`arah=naik\|turun`) | `pengaturan.pendaftaran` | Move a type up or down |
| `POST /admin/pengaturan/berkas/{id}/hapus` | `pengaturan.pendaftaran` | Soft delete; refused when any applicant ever uploaded that type (hide it instead) |
| `GET /admin/berkas` | `pendaftar.verifikasi` | Queue: current documents, newest applicants first; filters `q` (name/number), `status` (`menunggu` default, `perlu-diganti`, `sesuai`, `menyusul`, `semua`), `jenis`, `gelombang`; 20 per page |
| `GET /admin/berkas/{docId}/periksa` | `pendaftar.verifikasi` | Review screen: preview next to the applicant's name, number, birth data and parents, with *Sesuai* / *Perlu Diganti* |
| `POST /admin/berkas/{docId}/periksa` | `pendaftar.verifikasi` | `status=valid\|invalid`, `note` (required for invalid), `kembali=antrean` (opens the next waiting document) or `pendaftar` (back to `/admin/pendaftar/{id}#berkas-{typeId}`) |
| `GET /admin/berkas/{docId}/lihat` | `pendaftar.view` | The stored file |
| `POST /admin/pendaftar/{id}/berkas/{typeId}` | `pendaftar.edit` | Upload on the applicant's behalf (field `berkas`), or `aksi=menyusul` |
| `POST /cek-status/berkas/{typeId}` | applicant grant | Applicant upload (field `berkas`) or `aksi=menyusul`; no grant → redirect to `/cek-status` with a friendly message; back to `/cek-status/data#berkas-{typeId}` |
| `GET /cek-status/berkas/{docId}` | applicant grant | The applicant's own current file; anything else → 404 |

Files are sent with the stored `Content-Type`, `Content-Disposition: inline;
filename="<code>-<registration number>.<ext>"`, `X-Content-Type-Options: nosniff`
and `Cache-Control: private, no-store`.

## Storage and checks (`Services\DocumentStorage`)

- Path `writable/uploads/berkas/<yyyy>/<32 hex>.<jpg|pdf>`; the client file name
  is only kept as a label (`original_name`, path parts and control characters removed).
- Extension (from the type's list) **and** real MIME type (`finfo`) must match the type.
  `.heic` gets a dedicated message (iPhone "Most Compatible" setting).
- Images (JPEG/PNG): accepted up to `Berkas.imageUploadLimitKb` (10 MB) so phone
  camera photos work, refused above 50 megapixels; turned upright from the EXIF
  orientation, scaled so the longest side is at most `imageMaxEdge`, drawn on white
  (transparent PNG) and saved as JPEG at `imageQuality` with plain GD — no EXIF or
  other metadata survives. The result must fit the type's `max_size_kb`.
- PDFs: copied unchanged; must fit `max_size_kb`.
- PHP upload errors become friendly messages (`UploadInput`); `UPLOAD_ERR_INI_SIZE`
  reports the server's own limit.

## Document actions (`Services\DocumentService`)

| Action | Rules | Audit event |
| --- | --- | --- |
| `upload()` | New row `pending`; the previous current row of that type is soft-deleted (its file is kept). Applicants cannot replace a `valid` document or change anything when their registration is `cancelled`; staff can. | `document_uploaded` (+ `document_replaced` for the old row) |
| `markToFollow()` | Only types with `may_follow`, and only when nothing usable is there (none or `invalid`) | `document_to_follow` |
| `verify()` | `valid` or `invalid` (+ note, required); stores `verified_by/at` | `document_verified` |

The model writes of these actions run with callbacks off and log one semantic
event each (old/new values), so Riwayat shows "Berkas diunggah" instead of a
generic "created" next to it.

## Document status (`Services\DocumentStatus`)

`DocumentStatus::refresh(int $applicantId): string` recomputes
`applicants.document_status` (through `ApplicantModel`, audited only when it
changes) after every action, from the active types:

1. `incomplete` — a required type has no document, or it is `invalid`, or it is
   `to_follow` while the type no longer allows that;
2. `pending` — otherwise, any current document (also optional ones) waits for a check;
3. `to_follow` — otherwise, a required type is "menyusul";
4. `complete` — every required document is valid.

`DocumentStatus::compute()` is the pure function behind it.

## Interfaces for other modules

- `DocumentTypeModel::active(): list<array>` — active types in display order (for `/persyaratan`).
  Helpers: `extensionsOf()`, `mimesOf()`, `acceptOf()`, `formatLabel()`.
- `ApplicantDocumentModel::currentFor(int $applicantId)` / `currentOf($applicantId, $typeId)`.
- `DocumentStatus::refresh()`.
- Panels `Views/panels/applicant_public.php` and `Views/panels/applicant_admin.php`
  receive `['applicant' => row]` and render nothing without an applicant or without
  active types. Section id `berkas`, rows `berkas-<typeId>`. Per-row upload errors
  come from the flash `berkas_error` (`[typeId => message]`).

## Seeds

- `InstallSeeder`: Kartu Keluarga (`kk`), Akta Kelahiran (`akta`), Pas Foto (`foto`,
  images only, 1 MB) required; Rapor / Surat Keterangan Lulus (`rapor-skl`, 5 MB)
  required but may follow. Idempotent by `code` (also deleted ones). Defaults until
  the school answers.
- `DemoSeeder`: for up to 12 applicants without documents, generated sample images
  with mixed statuses. Does nothing when there are no applicants yet.

## Deployment note

Phone photos are larger than PHP's default 2 MB upload limit. Set
`upload_max_filesize = 10M`, `post_max_size = 12M` (php-fpm) and
`client_max_body_size 12m;` (nginx), matching `Berkas.imageUploadLimitKb`.

## Guides

`Guide/calon-murid.md` (upload, menyusul, statuses, problems),
`Guide/panitia.md` (checking, uploading for applicants),
`Guide/admin-sekolah.md` (document list, image settings).
