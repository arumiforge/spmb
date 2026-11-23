// Minify the public SVG assets (D-086).
//
// Originals live unminified in resources/img/<area>/*.svg, exactly as delivered (the source of
// truth). This writes minified copies to public/assets/img/<area>/*.svg, which are build output
// (ignored by git, produced by `npm run build` locally and in the Docker `assets` stage).
//
// Usage: node scripts/minify-assets.mjs        (npm run build:img)
import { mkdir, readFile, readdir, writeFile } from 'node:fs/promises';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { optimize } from 'svgo';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const sourceDir = join(root, 'resources', 'img');
const targetDir = join(root, 'public', 'assets', 'img');

// Keep what the CSS relies on: viewBox (svgo 4 keeps it unless removeViewBox is added) (masks and backgrounds scale with it), ids (`#diam` is a
// <view> target used as `memuat-kecil.svg#diam`; <use href="#…"> patterns), classes and the
// <style> blocks with their custom properties and animations.
//
// svgo 4 cannot parse @keyframes selectors (`0%,100%`), so each <style> body is swapped for a
// placeholder while svgo runs and put back afterwards with only its whitespace collapsed. svgo then
// treats the file as having no stylesheet, which only makes it skip style-aware tweaks.
const config = {
    multipass: true,
    plugins: [
        {
            name: 'preset-default',
            params: {
                overrides: {
                    cleanupIds: false,
                    inlineStyles: false,
                    minifyStyles: false,
                    mergePaths: false,
                    convertShapeToPath: false,
                    removeUnknownsAndDefaults: { keepRoleAttr: true },
                },
            },
        },
    ],
};

async function* walk(dir) {
    for (const entry of await readdir(dir, { withFileTypes: true })) {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) yield* walk(path);
        else if (entry.isFile() && entry.name.endsWith('.svg')) yield path;
    }
}

let before = 0;
let after = 0;
let count = 0;
for await (const source of walk(sourceDir)) {
    const rel = relative(sourceDir, source);
    const input = await readFile(source, 'utf8');
    const styles = [];
    const masked = input.replace(/(<style[^>]*>)([\s\S]*?)(<\/style>)/g, (_, open, css, close) => {
        styles.push(css.replace(/\s*\n\s*/g, '').trim());
        return `${open}/*spmb-style-${styles.length - 1}*/${close}`;
    });
    const { data: optimized } = optimize(masked, { ...config, path: source });
    const data = optimized.replace(/\/\*spmb-style-(\d+)\*\//g, (_, i) => styles[Number(i)]);
    if (styles.length && !styles.every((css) => data.includes(css))) {
        throw new Error(`${rel}: a <style> block was lost during minification`);
    }
    const target = join(targetDir, rel);
    await mkdir(dirname(target), { recursive: true });
    await writeFile(target, data);
    before += Buffer.byteLength(input);
    after += Buffer.byteLength(data);
    count++;
    if (process.argv.includes('--verbose')) {
        console.log(`${rel}: ${Buffer.byteLength(input)} → ${Buffer.byteLength(data)} B`);
    }
}

const saved = before ? (((before - after) / before) * 100).toFixed(1) : '0.0';
console.log(`build:img: ${count} SVG, ${before} → ${after} B (−${saved}%) → public/assets/img/`);
