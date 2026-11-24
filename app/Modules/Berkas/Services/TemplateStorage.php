<?php

declare(strict_types=1);

namespace Modules\Berkas\Services;

use finfo;
use ZipArchive;

/**
 * Example files per document type ("Unduh contoh"), stored under
 * writable/uploads/berkas-contoh/<32 hex>.<ext> and served by the Contoh
 * controller. The school's file name is kept only as the download name.
 *
 * Extension and real MIME type (finfo) must match; files are kept as uploaded.
 */
class TemplateStorage
{
    public const MAX_KB = 5120;

    /**
     * Allowed extensions with the MIME types finfo may report for them.
     *
     * @var array<string, list<string>>
     */
    public const FORMATS = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];

    /**
     * Content-Type sent on download, by stored extension.
     *
     * @var array<string, string>
     */
    public const CONTENT_TYPES = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
    ];

    private readonly string $root;

    public function __construct(?string $root = null)
    {
        $this->root = rtrim($root ?? WRITEPATH . 'uploads/berkas-contoh', '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Value for the accept attribute of the upload field.
     */
    public static function accept(): string
    {
        return '.pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png';
    }

    /**
     * Absolute path of a stored file, or null when the name is unsafe or the file is gone.
     */
    public function path(?string $stored): ?string
    {
        if ($stored === null || preg_match('#\A[a-f0-9]{32}\.(pdf|docx?|jpg|png)\z#', $stored) !== 1) {
            return null;
        }

        $full = $this->root . $stored;

        return is_file($full) ? $full : null;
    }

    /**
     * Validates and stores one file.
     *
     * @return array{template_path: string, template_name: string}
     *
     * @throws UploadRejected
     */
    public function store(string $source, string $clientName): array
    {
        if (! is_file($source) || ! is_readable($source)) {
            throw new UploadRejected(lang('Berkas.errUploadFailed'));
        }

        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));

        if (! isset(self::FORMATS[$ext])) {
            throw new UploadRejected(lang('Berkas.templateErrFormat'));
        }

        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($source);

        if (! in_array($mime, self::FORMATS[$ext], true) || ($ext === 'docx' && ! $this->isWordDocument($source))) {
            throw new UploadRejected(lang('Berkas.templateErrFormat'));
        }

        $size = (int) filesize($source);

        if ($size === 0) {
            throw new UploadRejected(lang('Berkas.errUnreadable'));
        }

        if ($size > self::MAX_KB * 1024) {
            throw new UploadRejected(lang('Berkas.errTooLarge', [DocumentStorage::sizeLabel(self::MAX_KB)]));
        }

        if (! is_dir($this->root) && ! @mkdir($this->root, 0770, true) && ! is_dir($this->root)) {
            log_message('error', 'Berkas: cannot create template directory ' . $this->root);

            throw new UploadRejected(lang('Berkas.errUploadFailed'));
        }

        $stored = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);

        if (! @copy($source, $this->root . $stored)) {
            throw new UploadRejected(lang('Berkas.errUploadFailed'));
        }

        return ['template_path' => $stored, 'template_name' => DocumentStorage::cleanName($clientName)];
    }

    public function delete(?string $stored): void
    {
        $path = $this->path($stored);

        if ($path !== null) {
            @unlink($path);
        }
    }

    /**
     * Content-Type for a stored file name.
     */
    public static function contentType(string $stored): string
    {
        return self::CONTENT_TYPES[strtolower(pathinfo($stored, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    }

    /**
     * Download name: the school's file name with the stored extension, or
     * "contoh-<code>.<ext>" when nothing usable is left.
     *
     * @param array<string, mixed> $type document_types row
     */
    public static function downloadName(array $type): string
    {
        $stored = (string) ($type['template_path'] ?? '');
        $ext    = strtolower(pathinfo($stored, PATHINFO_EXTENSION));
        $name   = DocumentStorage::cleanName((string) ($type['template_name'] ?? ''));
        $base   = pathinfo($name, PATHINFO_FILENAME);

        if ($name === 'berkas' || trim($base) === '') {
            $base = 'contoh-' . ($type['code'] ?? 'berkas');
        }

        return $base . '.' . $ext;
    }

    /**
     * A .docx is a zip archive; accept it only with Word's main part inside.
     */
    private function isWordDocument(string $path): bool
    {
        if (! class_exists(ZipArchive::class)) {
            return true;
        }

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        $found = $zip->locateName('word/document.xml') !== false;
        $zip->close();

        return $found;
    }
}
