// Copy the self-hosted Arabic font into the build (D-109).
//
// Noto Sans Arabic (SIL Open Font License 1.1) comes from the npm package @fontsource/noto-sans-arabic.
// Only the Arabic-script subset in the weights the public pages use is copied to public/build/fonts/,
// which is build output (ignored by git). The @font-face rules are at the end of resources/css/komponen.css;
// browsers download the files only on Arabic pages (/ar/…), where the layout also preloads the regular weight.
//
// Usage: node scripts/fonts.mjs        (npm run build:fonts, part of npm run build)
import { copyFile, mkdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const source = join(root, 'node_modules', '@fontsource', 'noto-sans-arabic', 'files');
const target = join(root, 'public', 'build', 'fonts');

await mkdir(target, { recursive: true });

for (const weight of [400, 500, 600, 700, 800]) {
    await copyFile(
        join(source, `noto-sans-arabic-arabic-${weight}-normal.woff2`),
        join(target, `noto-sans-arabic-${weight}.woff2`),
    );
}

console.log('Copied Noto Sans Arabic (400–800) to public/build/fonts/');
