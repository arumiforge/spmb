<?php

declare(strict_types=1);

namespace Modules\Berkas\Services;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Reads the uploaded file of a document form (field "berkas") and turns PHP
 * upload errors into friendly messages.
 */
final class UploadInput
{
    public const FIELD = 'berkas';

    /**
     * @return array{tmp: string, name: string}
     *
     * @throws UploadRejected
     */
    public static function read(IncomingRequest $request): array
    {
        $file = $request->getFile(self::FIELD);

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            throw new UploadRejected(lang('Berkas.errNoFile'));
        }

        if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new UploadRejected(lang('Berkas.errTooLarge', [DocumentStorage::sizeLabel(self::serverLimitKb())]));
        }

        $tmp = (string) $file->getTempName();

        // is_uploaded_file() is false for files injected by the test suite.
        if ($file->getError() !== UPLOAD_ERR_OK || $tmp === '' || (ENVIRONMENT !== 'testing' && ! is_uploaded_file($tmp))) {
            throw new UploadRejected(lang('Berkas.errUploadFailed'));
        }

        return ['tmp' => $tmp, 'name' => (string) $file->getClientName()];
    }

    /**
     * The server's own upload limit in KB (php.ini).
     */
    public static function serverLimitKb(): int
    {
        $limits = [];

        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $value = trim((string) ini_get($key));

            if ($value === '' || $value === '0') {
                continue;
            }

            $number = (int) $value;
            $limits[] = match (strtolower(substr($value, -1))) {
                'g'     => $number * 1024 * 1024,
                'm'     => $number * 1024,
                'k'     => $number,
                default => intdiv($number, 1024),
            };
        }

        return $limits === [] ? 2048 : max(1, min($limits));
    }
}
