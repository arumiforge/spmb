<?php

declare(strict_types=1);

namespace Modules\Berkas\Services;

use Config\Services;
use finfo;
use Modules\Berkas\Models\DocumentTypeModel;
use Throwable;

/**
 * Stores applicant documents under writable/uploads/berkas/<yyyy>/<random>.<ext>
 * (FR-DOC-02, FR-DOC-03, D-036).
 *
 * - The client file name is never used for the path; it is kept only as a label.
 * - Extension, real MIME type (finfo) and size are checked against the document type.
 * - Images are turned upright (EXIF orientation), resized to Berkas.imageMaxEdge
 *   and re-encoded as JPEG at Berkas.imageQuality; re-encoding drops EXIF data
 *   (location, camera). PDFs are kept as uploaded within the type's size limit.
 */
class DocumentStorage
{
    /**
     * Images above this many pixels are refused before decoding (memory guard).
     */
    public const MAX_PIXELS = 50_000_000;

    private readonly string $root;

    public function __construct(?string $root = null)
    {
        $this->root = rtrim($root ?? WRITEPATH . 'uploads/berkas', '/\\') . DIRECTORY_SEPARATOR;
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * Absolute path of a stored file, or null when the relative path is unsafe or missing.
     */
    public function path(?string $relative): ?string
    {
        if ($relative === null || preg_match('#\A[0-9]{4}/[a-f0-9]{32}\.(jpg|pdf)\z#', $relative) !== 1) {
            return null;
        }

        $full = $this->root . $relative;

        return is_file($full) ? $full : null;
    }

    /**
     * Validates and stores one uploaded file.
     *
     * @param string               $tmpPath    the uploaded temporary file (left untouched)
     * @param string               $clientName file name sent by the browser (label only)
     * @param array<string, mixed> $type       document_types row
     *
     * @return array{file_path: string, mime: string, size_bytes: int, original_name: string}
     *
     * @throws UploadRejected
     */
    public function store(string $tmpPath, string $clientName, array $type): array
    {
        if (! is_file($tmpPath) || ! is_readable($tmpPath)) {
            throw new UploadRejected(lang('Berkas.errUploadFailed'));
        }

        $allowedExt = DocumentTypeModel::extensionsOf($type);
        $formats    = DocumentTypeModel::formatLabel($type);
        $ext        = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));

        if (in_array($ext, ['heic', 'heif'], true)) {
            throw new UploadRejected(lang('Berkas.errHeic', [$formats]));
        }

        if ($ext !== '' && ! in_array($ext, $allowedExt, true)) {
            throw new UploadRejected(lang('Berkas.errWrongFormat', [$formats]));
        }

        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmpPath);

        if (! in_array($mime, DocumentTypeModel::mimesOf($type), true)) {
            throw new UploadRejected(lang('Berkas.errWrongFormat', [$formats]));
        }

        $size    = (int) filesize($tmpPath);
        $limitKb = max(1, (int) ($type['max_size_kb'] ?? 2048));

        if ($size === 0) {
            throw new UploadRejected(lang('Berkas.errUnreadable'));
        }

        $dir = date('Y');

        if (! is_dir($this->root . $dir) && ! @mkdir($this->root . $dir, 0770, true) && ! is_dir($this->root . $dir)) {
            log_message('error', 'Berkas: cannot create upload directory ' . $this->root . $dir);

            throw new UploadRejected(lang('Berkas.errUploadFailed'));
        }

        if ($mime === 'application/pdf') {
            if ($size > $limitKb * 1024) {
                throw new UploadRejected(lang('Berkas.errTooLarge', [self::sizeLabel($limitKb)]));
            }

            $relative = $dir . '/' . bin2hex(random_bytes(16)) . '.pdf';

            if (! @copy($tmpPath, $this->root . $relative)) {
                throw new UploadRejected(lang('Berkas.errUploadFailed'));
            }
        } else {
            $uploadLimitKb = max($limitKb, (int) setting('Berkas.imageUploadLimitKb'));

            if ($size > $uploadLimitKb * 1024) {
                throw new UploadRejected(lang('Berkas.errTooLarge', [self::sizeLabel($uploadLimitKb)]));
            }

            $relative = $dir . '/' . bin2hex(random_bytes(16)) . '.jpg';
            $this->processImage($tmpPath, $this->root . $relative);

            clearstatcache(true, $this->root . $relative);

            if ((int) filesize($this->root . $relative) > $limitKb * 1024) {
                @unlink($this->root . $relative);

                throw new UploadRejected(lang('Berkas.errTooLarge', [self::sizeLabel($limitKb)]));
            }

            $mime = 'image/jpeg';
        }

        clearstatcache(true, $this->root . $relative);

        return [
            'file_path'     => $relative,
            'mime'          => $mime,
            'size_bytes'    => (int) filesize($this->root . $relative),
            'original_name' => self::cleanName($clientName),
        ];
    }

    /**
     * Removes a stored file (used when saving the database row fails).
     */
    public function delete(string $relative): void
    {
        $path = $this->path($relative);

        if ($path !== null) {
            @unlink($path);
        }
    }

    /**
     * Upright, resized, EXIF-free JPEG via CodeIgniter's image service (GD).
     *
     * @throws UploadRejected
     */
    private function processImage(string $source, string $target): void
    {
        $info = @getimagesize($source);

        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            throw new UploadRejected(lang('Berkas.errUnreadable'));
        }

        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new UploadRejected(lang('Berkas.errImageTooBig'));
        }

        $maxEdge = max(200, (int) setting('Berkas.imageMaxEdge'));
        $quality = min(100, max(10, (int) setting('Berkas.imageQuality')));

        try {
            $image = Services::image(null, null, false)->withFile($source);
            $image->reorient(true);

            $width  = $image->getWidth();
            $height = $image->getHeight();

            if ($width > $maxEdge || $height > $maxEdge) {
                $scale = $maxEdge / max($width, $height);
                $image->resize(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
            }

            // White background for transparent PNGs, then JPEG without metadata.
            $image->flatten(255, 255, 255)->convert(IMAGETYPE_JPEG)->save($target, $quality);
        } catch (Throwable $e) {
            @unlink($target);
            log_message('error', 'Berkas: image processing failed: ' . $e->getMessage());

            throw new UploadRejected(lang('Berkas.errUnreadable'));
        }

        if (! is_file($target)) {
            throw new UploadRejected(lang('Berkas.errUploadFailed'));
        }
    }

    /**
     * Friendly size, e.g. "2 MB", "1,5 MB", "500 KB".
     */
    public static function sizeLabel(int $kb): string
    {
        if ($kb < 1024) {
            return $kb . ' KB';
        }

        $mb = $kb / 1024;

        return (floor($mb) === $mb ? (string) (int) $mb : number_format($mb, 1, ',', '')) . ' MB';
    }

    /**
     * File size in bytes for display.
     */
    public static function bytesLabel(int $bytes): string
    {
        return $bytes < 1024 * 1024
            ? max(1, (int) round($bytes / 1024)) . ' KB'
            : number_format($bytes / 1024 / 1024, 1, ',', '') . ' MB';
    }

    /**
     * Client file name reduced to a safe label (no path, no control characters).
     */
    public static function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = (string) preg_replace('/[\x00-\x1F\x7F]+/u', '', $name);

        return mb_substr(trim($name), 0, 200) ?: 'berkas';
    }
}
