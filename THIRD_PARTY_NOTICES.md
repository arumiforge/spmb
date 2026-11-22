# Third-Party Notices

SPMB is proprietary software of Arumi Studios (see [LICENSE](LICENSE)). It uses
the third-party components below, each under its own licence. Their licence
texts ship with the packages (`vendor/<package>/LICENSE`, `node_modules/<package>/LICENSE`).

Refresh this list when dependencies change:
`composer licenses --no-dev` and `npx license-checker --production --summary`.

## PHP (Composer, production)

| Package | Licence |
| --- | --- |
| codeigniter4/framework | MIT |
| codeigniter4/shield | MIT |
| codeigniter4/settings | MIT |
| codeigniter4/translations | MIT |
| chillerlan/php-qrcode | MIT / Apache-2.0 (dual) |
| chillerlan/php-settings-container | MIT |
| dflydev/dot-access-data | MIT |
| league/commonmark | BSD-3-Clause |
| league/config | BSD-3-Clause |
| laminas/laminas-escaper | BSD-3-Clause |
| nette/schema, nette/utils | BSD-3-Clause (dual-licensed with GPL; used under BSD-3-Clause) |
| paragonie/constant_time_encoding | MIT |
| pragmarx/google2fa | MIT |
| setasign/fpdf | MIT |
| phpoffice/phpspreadsheet | MIT |
| maennchen/zipstream-php, markbaker/complex, markbaker/matrix, composer/pcre, psr/simple-cache | MIT |
| psr/event-dispatcher, psr/log | MIT |
| symfony/deprecation-contracts, symfony/polyfill-ctype, symfony/polyfill-mbstring, symfony/polyfill-php80 | MIT |

Development-only Composer packages (PHPUnit, PHP CS Fixer, Faker, ...) are MIT or
BSD-3-Clause and are not deployed (`composer install --no-dev`).

## Frontend (npm, added with the phase 1 UI)

| Package | Licence | Note |
| --- | --- | --- |
| flowbite | MIT | Open-source components |
| tailwindcss, @tailwindcss/cli | MIT | Build tool |
| esbuild | MIT | Build tool |
| lucide-static | ISC | Lucide icons; only the SVG paths of the icons we use are copied into `app/Helpers` at development time (D-070) |
| apexcharts | ApexCharts licence (v4+) | **Verify** the current terms on apexcharts.com: v4 is free only below a revenue threshold for the licensee; buy a licence if Arumi Studios or a client exceeds it, or switch charts library |
| qr-scanner (nimiq) | MIT | Camera QR scanning for test attendance (phase 3, `resources/js/scanner.js`) |

## Commercial

| Component | Licence | Note |
| --- | --- | --- |
| Flowbite Pro (admin dashboard v2.2.0, landing pages v1.1.0) | Flowbite commercial licence held by Arumi Studios | Licence proof is kept by Arumi Studios outside this repository. Source in `flowbite/` is a markup reference only: the repository must stay private and Pro source must never be published, extracted or given to clients. |

## Data

| Data | Source | Note |
| --- | --- | --- |
| National SD/MI list (`app/Modules/Referensi/Database/Data/sekolah-sd-mi/`) | Public school reference data (Kemendikdasmen / Kemenag), compiled by Arumi Studios | Public data, no personal data (D-058) |

## CodeIgniter application starter

Files under `app/Config/`, `app/Controllers/BaseController.php`, `public/index.php`,
`spark`, `preload.php` and `phpunit.dist.xml` originate from the CodeIgniter 4
application starter and remain available under its MIT licence:

```
The MIT License (MIT)

Copyright (c) 2014-2019 British Columbia Institute of Technology
Copyright (c) 2019-present CodeIgniter Foundation

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
```
