<?php

declare(strict_types=1);

namespace Modules\Situs\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
use Throwable;

/**
 * Image and attachment of the home page popup, stored in writable/uploads/situs/
 * (outside public/) under random names and served by Controllers\Popup.
 *
 * Images are scaled down to fit MAX_EDGE (aspect ratio kept). The attachment is
 * kept as uploaded; its original name is only used for the download.
 */
final class PopupFiles
{
    public const DIR            = WRITEPATH . 'uploads/situs/';
    public const MAX_EDGE       = 1200;
    public const IMAGE_EXT      = ['jpg', 'jpeg', 'png', 'webp'];
    public const ATTACHMENT_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    /**
     * Upload limits in KB (validation max_size).
     */
    public const IMAGE_MAX_KB = 2048;

    public const ATTACHMENT_MAX_KB = 5120;

    /**
     * Saves the uploaded image. Returns the stored name, or null on failure.
     */
    public static function storeImage(UploadedFile $file): ?string
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientExtension());
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;

        if (! in_array($ext, self::IMAGE_EXT, true)) {
            return null;
        }

        $name = self::copy($file->getTempName(), 'popup-gambar', $ext);

        if ($name !== null && extension_loaded('gd')) {
            try {
                $path             = self::DIR . $name;
                [$width, $height] = getimagesize($path) ?: [0, 0];

                if ($width === 0) {
                    self::remove($name);

                    return null;
                }

                if ($width > self::MAX_EDGE || $height > self::MAX_EDGE) {
                    Services::image(null, null, false)->withFile($path)->resize(self::MAX_EDGE, self::MAX_EDGE, true)->save($path);
                }
            } catch (Throwable $e) {
                log_message('error', 'Popup image resize failed: ' . $e->getMessage());
                self::remove($name);

                return null;
            }
        }

        return $name;
    }

    /**
     * Saves the uploaded attachment. Returns the stored name, or null on failure.
     */
    public static function storeAttachment(UploadedFile $file): ?string
    {
        $ext = strtolower($file->getClientExtension());

        if (! in_array($ext, self::ATTACHMENT_EXT, true)) {
            return null;
        }

        return self::copy($file->getTempName(), 'popup-lampiran', $ext);
    }

    /**
     * Absolute path of a stored file, or null when the name is unsafe or missing.
     */
    public static function path(string $name): ?string
    {
        if (preg_match('/\Apopup-(gambar|lampiran)-[a-f0-9]{16}\.[a-z]{3,4}\z/', $name) !== 1) {
            return null;
        }

        return is_file(self::DIR . $name) ? self::DIR . $name : null;
    }

    public static function remove(string $name): void
    {
        $path = self::path(basename($name));

        if ($path !== null) {
            @unlink($path);
        }
    }

    /**
     * File name for the download: the original name, cleaned, with the stored extension.
     */
    public static function downloadName(string $original, string $stored): string
    {
        $ext  = pathinfo($stored, PATHINFO_EXTENSION);
        $base = trim((string) preg_replace('/[^\pL\pN ._-]+/u', '', pathinfo($original, PATHINFO_FILENAME)));

        return ($base !== '' ? mb_substr($base, 0, 80) : 'lampiran') . '.' . $ext;
    }

    private static function copy(string $source, string $prefix, string $ext): ?string
    {
        try {
            if (! is_dir(self::DIR)) {
                mkdir(self::DIR, 0775, true);
            }

            $name = $prefix . '-' . bin2hex(random_bytes(8)) . '.' . $ext;

            return copy($source, self::DIR . $name) ? $name : null;
        } catch (Throwable $e) {
            log_message('error', 'Popup upload failed: ' . $e->getMessage());

            return null;
        }
    }
}
