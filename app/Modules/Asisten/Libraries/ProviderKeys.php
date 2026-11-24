<?php

declare(strict_types=1);

namespace Modules\Asisten\Libraries;

use RuntimeException;
use SensitiveParameter;
use Throwable;

/**
 * Encrypts and decrypts AI API keys with CodeIgniter's Encryption service (the app's encryption.key).
 * The database holds base64 of the ciphertext; the screen only ever shows the last 4 characters.
 */
final class ProviderKeys
{
    /**
     * @throws RuntimeException when the app has no encryption key
     */
    public static function encrypt(#[SensitiveParameter] string $key): string
    {
        if ((string) config('Encryption')->key === '') {
            throw new RuntimeException('No encryption key configured.');
        }

        return base64_encode(service('encrypter', null, false)->encrypt($key));
    }

    /**
     * The plain key, or null when it is missing or cannot be decrypted (e.g. the encryption key changed).
     */
    public static function decrypt(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            $raw = base64_decode($stored, true);

            return $raw === false ? null : service('encrypter', null, false)->decrypt($raw);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Last 4 characters, for "••••abcd".
     */
    public static function hint(#[SensitiveParameter] string $key): string
    {
        return mb_substr($key, -4);
    }
}
