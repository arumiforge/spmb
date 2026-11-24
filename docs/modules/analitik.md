# Analitik (visitor statistics)

Compact, built-in visitor statistics for the public website (D-009, D-053, FR-PLT-07): page
views and unique visitors per day, the most opened pages, and a small visitor line in the public
footer. No third-party script, no cookie, nothing personal stored. Shown on the Fitur page as
"Statistik Pengunjung"; can be switched off there (counting, pages, menu and panels stop).

## How counting works

The global after-filter `analitik` (`Modules\Analitik\Filters\CountVisit`, registered in
`app/Config/Filters.php`) hands every response to `Libraries/VisitRecorder` while the module is on.

A request counts only when **all** of these hold:

- `GET`, response status `200`, `Content-Type` HTML (redirects, errors, PDFs, JSON and other
  downloads are skipped; `HEAD` and form posts too);
- not AJAX (`X-Requested-With`, `HX-Request`), `Sec-Fetch-Dest` absent or `document`, no
  prefetch/prerender (`Sec-Purpose`, `Purpose`, `X-Moz`);
- the path is not in `VisitRecorder::EXCLUDED_PATHS` (`admin`, `admin/*`, `masuk`, `keluar`,
  `verifikasi*`, `aset/*`, `assets/*`, `favicon.ico`, `robots.txt`, …);
- the user agent looks like a browser (contains `Mozilla`/`Opera`) and matches none of
  `VisitRecorder::BOT_PATTERNS` (bots, crawlers, link previews, monitors, HTTP libraries,
  headless browsers);
- the visitor is not signed in. The session is only looked at when the browser already sends a
  session or remember-me cookie, so anonymous visitors never get a session from counting.

**Unique visitors** use a one-way daily hash: `HMAC-SHA256(ip + "\n" + user agent, dayKey)`,
where `dayKey = HMAC-SHA256("analitik-visitor|YYYY-MM-DD", encryption key)` (fallback without
an encryption key: a per-install value from the root path and host name). The first 128 bits
(32 hex characters) are stored with the date in `analytics_visitors` only to see whether this
visitor was already counted today. The key changes every day and is never stored, so hashes
cannot be linked across days, and the rows are deleted after a day. Raw IPs and user agents are
never written anywhere.

**Paths** are normalised before storing (`VisitRecorder::normalisePath()`): lower case, no query
string, no slashes at the ends, at most four segments and 191 characters; numeric segments and
segments of 20+ characters (card and link tokens) become `*` (e.g. `kartu/*`).

Recording is three small queries (`INSERT IGNORE` into `analytics_visitors`, then
`INSERT … ON DUPLICATE KEY UPDATE` on `analytics_daily` and `analytics_daily_pages`). Any
failure is caught and logged (`Analitik: visit not recorded: …`); the page is served as usual.
The first view of each day also prunes (see below).

"Pengunjung" over a period (7 days, 30 days, month, total) is the **sum of daily unique
visitors**: someone who comes back on another day is counted again. The admin page explains this
in plain Indonesian ("Cara menghitung").

## Routes

| URL | Permission | Content |
| --- | --- | --- |
| `GET /admin/pengunjung` | `analitik.view` | Tiles (Hari ini, Kemarin, 7 hari terakhir, 30 hari terakhir, Sejak awal: visitors + page views), area/line chart per day (`?rentang=7\|30\|90`, default 30; ApexCharts via `charts.js`, datetime axis with an Indonesian locale, table view in a `<details>`), the 10 most opened pages of the last 30 days with friendly names (`Analitik.pages` in the language file; unknown paths show "Halaman lain" + the path), "Cara menghitung", and the settings card for `pengaturan.sekolah` |
| `POST /admin/pengunjung/pengaturan` | `pengaturan.sekolah` | Saves the settings below; audited as `settings_updated` / `settings` / `Analitik` |

Sidebar: group **Website**, "Pengunjung", icon `chart-line`.

## Panels

- `Views/panels/dashboard.php` (slot `dashboard`, users with `analitik.view`): visitors today and
  in the last 7 days, a 14-day sparkline drawn as inline SVG (no chart script needed on the
  dashboard), link "Lihat statistik".
- `Views/panels/public_footer.php` (slot `public_footer` in `layouts/public.php`): "Pengunjung hari
  ini · bulan ini · total" with an icon, only while `Analitik.showInFooter` is on. Numbers come from
  `VisitStats::footer()`, cached for five minutes (cache key `analitik_footer`), so most page
  loads run no query for it. Errors are logged and the line is left out.

## Settings (`app/Config/Analitik.php`, `setting('Analitik.*')`)

| Key | Default | Meaning |
| --- | --- | --- |
| `showInFooter` | `true` | Show the visitor line in the public footer |
| `retentionDays` | `400` | Days per-page rows are kept (30–3650 on the form); daily totals are kept for good |

## Tables

| Table | Columns | Notes |
| --- | --- | --- |
| `analytics_daily` | `date` (PK), `page_views`, `visitors` | One row per day, never pruned |
| `analytics_daily_pages` | `id`, `date`, `path` (191), `views`; unique (`date`, `path`) | Pruned after `retentionDays` |
| `analytics_visitors` | `date`, `hash` (CHAR 32); PK (`date`, `hash`) | Rows older than yesterday are deleted |

## Pruning

`VisitRecorder::prune()` deletes visitor hashes older than yesterday and per-page rows older than
the retention. It runs automatically on the first recorded view of each day, and from the command
line (`app/Commands/AnalitikPrune.php`; kept outside the module because CodeIgniter's command
discovery plus Composer's `App\` mapping can include a module command twice, see the class comment):

```bash
php spark analitik:prune             # retention from setting Analitik.retentionDays
php spark analitik:prune --hari 90   # keep 90 days of per-page rows
```

A cron entry is optional (useful on a site with no visitors for days).

## Demo data

`Database/Seeds/DemoSeeder.php` (run by `php spark spmb:install --demo`) adds 90 days of plausible
totals and per-page views while the tables are empty.

## Not included (compact on purpose)

Referrers and device-type totals mentioned in D-053 are not built yet; there is no per-visitor
detail by design.

## Guide

`Guide/admin-sekolah.md`: reading the page and the dashboard card, the footer setting and retention.
