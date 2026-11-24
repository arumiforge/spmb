<?php

declare(strict_types=1);

namespace Modules\Asisten\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
use RuntimeException;

/**
 * The assistant's picture (white label, D-110): uploaded by Admin Sekolah or loaded from a branding
 * pack (asisten.webp, `spark spmb:branding`). Stored in writable/uploads/asisten/ under a random name
 * (setting Asisten.avatar), scaled down to 512 px and served by Widget::picture. Without one, the
 * painted pelita (ikon/asisten-pelita, Codex brief 073) or a Lucide icon shows.
 */
final class AvatarStorage
{
    public const DIR      = WRITEPATH . 'uploads/asisten/';
    public const MAX      = 512;
    public const MAX_KB   = 2048;
    public const MIMES    = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    public const FALLBACK = 'ikon/asisten-pelita';

    /**
     * Stored file name, or '' when none.
     */
    public static function file(): string
    {
        $name = basename((string) setting('Asisten.avatar'));

        return $name !== '' && is_file(self::DIR . $name) ? $name : '';
    }

    /**
     * Public URL of the uploaded picture (versioned), or null.
     */
    public static function url(): ?string
    {
        $file = self::file();

        return $file === '' ? null : site_url('asisten/gambar') . '?v=' . substr(md5($file), 0, 8);
    }

    /**
     * Picture markup for the widget: the uploaded picture, the painted pelita, or a Lucide icon.
     */
    public static function html(string $class): string
    {
        $url = self::url();

        if ($url !== null) {
            return '<img src="' . esc($url, 'attr') . '" alt="" class="' . esc($class) . ' object-contain" width="64" height="64" decoding="async">';
        }

        $painted = illustration(self::FALLBACK, '', $class . ' object-contain');

        return $painted !== '' ? $painted : icon('message-circle-question-mark', $class);
    }

    /**
     * Saves an uploaded picture and replaces the current one. Returns the stored name.
     *
     * @throws RuntimeException with a message for the admin
     */
    public static function storeUpload(UploadedFile $upload): string
    {
        if (! $upload->isValid()) {
            throw new RuntimeException(lang('Asisten.pictureInvalid'));
        }

        if ($upload->getSizeByUnit('kb') > self::MAX_KB) {
            throw new RuntimeException(lang('Asisten.pictureTooBig', [self::MAX_KB / 1024]));
        }

        return self::storeFile($upload->getTempName());
    }

    /**
     * Copies a picture from disk (upload or branding pack), scaled down to 512 px.
     *
     * @throws RuntimeException
     */
    public static function storeFile(string $source): string
    {
        $mime = is_file($source) ? (string) mime_content_type($source) : '';

        if (! isset(self::MIMES[$mime]) || @getimagesize($source) === false) {
            throw new RuntimeException(lang('Asisten.pictureInvalid'));
        }

        if (! is_dir(self::DIR)) {
            mkdir(self::DIR, 0775, true);
        }

        $name = 'asisten-' . bin2hex(random_bytes(8)) . '.' . self::MIMES[$mime];
        $dest = self::DIR . $name;

        if (! copy($source, $dest)) {
            throw new RuntimeException(lang('Asisten.pictureInvalid'));
        }

        [$width, $height] = getimagesize($dest) ?: [0, 0];

        if (extension_loaded('gd') && ($width > self::MAX || $height > self::MAX)) {
            Services::image(null, null, false)->withFile($dest)->resize(self::MAX, self::MAX, true)->save($dest);
        }

        return $name;
    }

    /**
     * Sets a newly stored picture as the current one and deletes the old file. Returns the old name.
     */
    public static function replace(string $name): string
    {
        $old = (string) setting('Asisten.avatar');
        setting('Asisten.avatar', $name);
        self::delete($old);

        return $old;
    }

    public static function delete(string $name): void
    {
        $name = basename($name);

        if ($name !== '' && is_file(self::DIR . $name)) {
            unlink(self::DIR . $name);
        }
    }
}
