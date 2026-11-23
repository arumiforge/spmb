<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
use RuntimeException;
use Throwable;

/**
 * The school's brand images (white label, D-054): which exist, where they are
 * stored, how big they may be and under which public URL they are served.
 *
 * Files live in writable/uploads/branding/ under random names; the name is
 * stored in setting('School.<setting>'). They are public by nature (website,
 * sign-in page, link previews) and served by App\Controllers\Branding.
 */
class BrandingAssets
{
    public const DIR = WRITEPATH . 'uploads/branding/';

    /**
     * key => setting (School.*), public slug (/aset/<slug>), largest width and
     * height (kept inside, aspect ratio preserved), accepted extensions.
     *
     * @var array<string, array{setting: string, slug: string, max: array{0: int, 1: int}, ext: list<string>}>
     */
    public const ASSETS = [
        'logo'         => ['setting' => 'logoPath', 'slug' => 'logo', 'max' => [512, 512], 'ext' => ['png', 'jpg', 'jpeg', 'webp']],
        'logoWide'     => ['setting' => 'logoWidePath', 'slug' => 'logo-lebar', 'max' => [1600, 480], 'ext' => ['png', 'webp']],
        'logoWideDark' => ['setting' => 'logoWideDarkPath', 'slug' => 'logo-lebar-gelap', 'max' => [1600, 480], 'ext' => ['png', 'webp']],
        'partnerLogo'  => ['setting' => 'partnerLogoPath', 'slug' => 'logo-naungan', 'max' => [600, 400], 'ext' => ['png', 'jpg', 'jpeg', 'webp']],
        'shareImage'   => ['setting' => 'shareImagePath', 'slug' => 'gambar-bagikan', 'max' => [1200, 630], 'ext' => ['png', 'jpg', 'jpeg']],
        'icon'         => ['setting' => 'iconPath', 'slug' => 'ikon', 'max' => [512, 512], 'ext' => ['png']],
        'favicon'      => ['setting' => 'faviconPath', 'slug' => 'favicon', 'max' => [64, 64], 'ext' => ['png', 'ico']],
        'heroSlide1'   => ['setting' => 'heroSlide1Path', 'slug' => 'slide-1', 'max' => [1600, 1600], 'ext' => ['png', 'jpg', 'jpeg', 'webp']],
        'heroSlide2'   => ['setting' => 'heroSlide2Path', 'slug' => 'slide-2', 'max' => [1600, 1600], 'ext' => ['png', 'jpg', 'jpeg', 'webp']],
        'heroSlide3'   => ['setting' => 'heroSlide3Path', 'slug' => 'slide-3', 'max' => [1600, 1600], 'ext' => ['png', 'jpg', 'jpeg', 'webp']],
        'heroSlide4'   => ['setting' => 'heroSlide4Path', 'slug' => 'slide-4', 'max' => [1600, 1600], 'ext' => ['png', 'jpg', 'jpeg', 'webp']],
        'heroSlide5'   => ['setting' => 'heroSlide5Path', 'slug' => 'slide-5', 'max' => [1600, 1600], 'ext' => ['png', 'jpg', 'jpeg', 'webp']],
        'heroSlide6'   => ['setting' => 'heroSlide6Path', 'slug' => 'slide-6', 'max' => [1600, 1600], 'ext' => ['png', 'jpg', 'jpeg', 'webp']],
    ];

    /**
     * Keys of the home hero slides (D-084), in order.
     */
    public const HERO_SLIDES = ['heroSlide1', 'heroSlide2', 'heroSlide3', 'heroSlide4', 'heroSlide5', 'heroSlide6'];

    /**
     * URLs of the slides the school uploaded, in order (empty slots skipped).
     *
     * @return list<string>
     */
    public static function heroSlides(): array
    {
        return array_values(array_filter(array_map(self::url(...), self::HERO_SLIDES)));
    }

    /**
     * MIME types ever served from the branding folder.
     */
    public const MIMES = ['image/png', 'image/jpeg', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];

    public static function keyForSlug(string $slug): ?string
    {
        foreach (self::ASSETS as $key => $asset) {
            if ($asset['slug'] === $slug) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Stored file name of an asset, or '' when none.
     */
    public static function file(string $key): string
    {
        return basename((string) setting('School.' . self::ASSETS[$key]['setting']));
    }

    /**
     * Public URL of an asset (with a cache-busting version), or null when none is set.
     */
    public static function url(string $key): ?string
    {
        $file = self::file($key);

        if ($file === '' || ! is_file(self::DIR . $file)) {
            return null;
        }

        return site_url('aset/' . self::ASSETS[$key]['slug']) . '?v=' . substr(md5($file), 0, 8);
    }

    /**
     * Width and height of a stored asset as HTML attributes (' width="…" height="…"'), or '' when unknown.
     * Lets the browser reserve the space before the image arrives, so nothing jumps on reload.
     */
    public static function sizeAttributes(string $key): string
    {
        $file = self::file($key);

        if ($file === '' || ! is_file(self::DIR . $file)) {
            return '';
        }

        [$width, $height] = @getimagesize(self::DIR . $file) ?: [0, 0];

        return $width > 0 && $height > 0 ? ' width="' . $width . '" height="' . $height . '"' : '';
    }

    /**
     * Saves an uploaded file for $key. Returns the stored name, or null on failure.
     */
    public static function storeUpload(UploadedFile $upload, string $key): ?string
    {
        $ext = strtolower($upload->guessExtension() ?: $upload->getClientExtension());

        try {
            return self::storeFile($upload->getTempName(), $key, $ext);
        } catch (Throwable $e) {
            log_message('error', 'Branding upload failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Copies a file from disk for $key (used by spark spmb:branding), scaling
     * it down to the asset's largest size. Returns the stored name.
     */
    public static function storeFile(string $source, string $key, ?string $ext = null): string
    {
        $asset = self::ASSETS[$key] ?? throw new RuntimeException('Unknown branding asset: ' . $key);
        $ext ??= strtolower(pathinfo($source, PATHINFO_EXTENSION));

        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        if (! in_array($ext, $asset['ext'], true) && ! ($ext === 'jpg' && in_array('jpeg', $asset['ext'], true))) {
            throw new RuntimeException("{$key}: file type .{$ext} is not accepted");
        }

        $mime = (string) mime_content_type($source);

        if (! in_array($mime, self::MIMES, true)) {
            throw new RuntimeException("{$key}: {$mime} is not an image type we serve");
        }

        if (! is_dir(self::DIR)) {
            mkdir(self::DIR, 0775, true);
        }

        $name = $asset['slug'] . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = self::DIR . $name;

        if (! copy($source, $dest)) {
            throw new RuntimeException("{$key}: could not copy the file");
        }

        if ($ext !== 'ico' && extension_loaded('gd')) {
            [$width, $height] = getimagesize($dest) ?: [0, 0];
            [$maxW, $maxH]    = $asset['max'];

            if ($width > $maxW || $height > $maxH) {
                Services::image(null, null, false)->withFile($dest)->resize($maxW, $maxH, true)->save($dest);
            }
        }

        return $name;
    }

    public static function remove(string $name): void
    {
        $name = basename($name);

        if ($name !== '' && is_file(self::DIR . $name)) {
            unlink(self::DIR . $name);
        }
    }
}
