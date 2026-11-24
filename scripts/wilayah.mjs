// Convert the national region list (cahyadsn/wilayah, MIT) into the bundled data of the Referensi
// module (D-111): app/Modules/Referensi/Database/Data/wilayah/wilayah.json.gz plus manifest.json.
//
// Source: https://github.com/cahyadsn/wilayah, file db/wilayah.sql (codes per Kepmendagri
// No 300.2.2-2138 Tahun 2025). The SQL file is treated as untrusted input: it is never executed,
// only its ('code','name') tuples are read with a strict pattern and every row is checked
// (code shape, parent present, name characters and length, no duplicates).
//
// Output: a gzipped JSON array of [code, name] pairs, sorted by code. Level and parent follow from
// the code (11 / 11.01 / 11.01.01 / 11.01.01.2001). Names keep the official spelling
// ("Kabupaten Kudus"); the short display form ("Kab. Kudus") is made by the application.
//
// Usage: node scripts/wilayah.mjs <path/to/wilayah.sql> [--version=YYYY-MM-DD]
//   The version defaults to the "last edit" date in the SQL header. Bump it whenever the data
//   changes: `php spark referensi:import --bawaan` reloads the list only when the version differs
//   from the setting Referensi.regionsVersion.
import { createHash } from 'node:crypto';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const targetDir = join(root, 'app', 'Modules', 'Referensi', 'Database', 'Data', 'wilayah');

const args = process.argv.slice(2);
const source = args.find((a) => !a.startsWith('--'));
const versionArg = args.find((a) => a.startsWith('--version='));

if (!source) {
    console.error('Usage: node scripts/wilayah.mjs <path/to/wilayah.sql> [--version=YYYY-MM-DD]');
    process.exit(1);
}

const sql = await readFile(source, 'utf8');

if (sql.length > 20 * 1024 * 1024) {
    throw new Error('The source file is unexpectedly large.');
}

const CODE = /^\d{2}(\.\d{2}(\.\d{2}(\.\d{4})?)?)?$/;
// Letters (any script, for names such as "Regapu’u"), digits, spaces and a few punctuation marks.
const NAME = /^[\p{L}\p{N}][\p{L}\p{N} .,'’()\/-]*$/u;
const TUPLE = /\('([0-9.]{2,13})','((?:[^'\\]|'')*)'\)/g;

const rows = new Map();

for (const match of sql.matchAll(TUPLE)) {
    const code = match[1];
    const name = match[2].replace(/''/g, "'").replace(/\s+/g, ' ').trim();

    if (!CODE.test(code)) {
        throw new Error(`Unexpected code: ${code}`);
    }

    if (name.length === 0 || name.length > 100 || !NAME.test(name)) {
        throw new Error(`Unexpected name for ${code}: ${JSON.stringify(name)}`);
    }

    if (rows.has(code)) {
        throw new Error(`Duplicate code: ${code}`);
    }

    rows.set(code, name);
}

// Every data line must have been read: a line the pattern skipped would silently drop a region.
const dataLines = sql.split('\n').filter((line) => line.trimStart().startsWith("('")).length;

if (dataLines !== rows.size) {
    throw new Error(`Read ${rows.size} regions from ${dataLines} data lines; check the source format.`);
}

const codes = [...rows.keys()].sort();
const counts = { provinces: 0, regencies: 0, districts: 0, villages: 0 };
const keys = ['provinces', 'regencies', 'districts', 'villages'];

for (const code of codes) {
    const level = code.split('.').length;
    counts[keys[level - 1]]++;

    if (level > 1) {
        const parent = code.slice(0, code.lastIndexOf('.'));

        if (!rows.has(parent)) {
            throw new Error(`Parent ${parent} of ${code} is missing.`);
        }
    }
}

if (counts.provinces < 30 || counts.regencies < 500 || counts.districts < 7000 || counts.villages < 80000) {
    throw new Error(`The list looks incomplete: ${JSON.stringify(counts)}`);
}

const header = /last edit\s*:\s*(\d{4}-\d{2}-\d{2})/.exec(sql);
const version = versionArg ? versionArg.slice('--version='.length) : header?.[1];

if (!version || !/^\d{4}-\d{2}-\d{2}(\.\d+)?$/.test(version)) {
    throw new Error('No version: pass --version=YYYY-MM-DD.');
}

const json = JSON.stringify(codes.map((code) => [code, rows.get(code)]));
const gz = gzipSync(Buffer.from(json, 'utf8'), { level: 9 });

await mkdir(targetDir, { recursive: true });
await writeFile(join(targetDir, 'wilayah.json.gz'), gz);

const manifest = {
    version,
    description: 'Administrative regions of Indonesia: provinces, regencies and cities, districts, villages (public data, no personal data).',
    source: 'https://github.com/cahyadsn/wilayah (db/wilayah.sql), Kepmendagri No 300.2.2-2138 Tahun 2025',
    licence: 'MIT, (c) 2025 cahya dsn',
    file: 'wilayah.json.gz',
    sha256: createHash('sha256').update(gz).digest('hex'),
    regions: codes.length,
    ...counts,
};

await writeFile(join(targetDir, 'manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);

console.log(`Wrote ${codes.length} regions (${JSON.stringify(counts)}), ${(gz.length / 1024).toFixed(0)} KB, version ${version}.`);
