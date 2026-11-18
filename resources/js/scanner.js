/*
 * Camera QR scanner for staff pages (test attendance, card lookup), built to
 * public/build/scanner.js and loaded only on pages that need it.
 *
 *   <script src="/build/scanner.js" defer></script>
 *   const stop = await window.SpmbScanner.start(videoElement, (text) => { ... });
 *   stop();
 *
 * Uses the browser's BarcodeDetector when available and qr-scanner (MIT,
 * nimiq/qr-scanner) otherwise. The callback gets the decoded text (normally
 * the card URL .../kartu/<token>); the same code is not reported twice
 * within 3 seconds. start() rejects when there is no camera or no permission.
 */
import QrScanner from 'qr-scanner';

const REPEAT_MS = 3000;

async function start(video, onResult) {
    let last = '';
    let lastAt = 0;

    const scanner = new QrScanner(
        video,
        (result) => {
            const text = typeof result === 'string' ? result : result.data;
            const now = Date.now();

            if (!text || (text === last && now - lastAt < REPEAT_MS)) {
                return;
            }

            last = text;
            lastAt = now;
            onResult(text);
        },
        { preferredCamera: 'environment', highlightScanRegion: true, highlightCodeOutline: true, returnDetailedScanResult: true },
    );

    await scanner.start();

    return () => {
        scanner.stop();
        scanner.destroy();
    };
}

/** Card token from a scanned text: ".../kartu/<token>" → "<token>"; anything else unchanged. */
function cardCode(text) {
    const match = /\/kartu\/([A-Za-z0-9_-]+)\/?(?:[?#].*)?$/.exec(String(text).trim());

    return match ? match[1] : String(text).trim();
}

window.SpmbScanner = { start, cardCode, hasCamera: () => QrScanner.hasCamera() };
