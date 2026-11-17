<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\UserTwoFactorModel;
use CodeIgniter\Encryption\EncrypterInterface;
use PragmaRX\Google2FA\Google2FA;
use Throwable;

/**
 * Optional TOTP two-factor authentication (Google Authenticator and compatible apps).
 *
 * Flow: beginSetup() → user scans the QR / enters the key → confirmSetup() with a
 * code from the app → recovery codes are shown once. At login, verify() accepts a
 * TOTP code (each time step only once) or a single-use recovery code.
 */
class TwoFactor
{
    public const RECOVERY_CODE_COUNT = 8;

    /**
     * Accept codes from one time step (30 s) before or after now, for clock drift.
     */
    private const WINDOW = 1;

    public function __construct(
        private readonly UserTwoFactorModel $model,
        private readonly Google2FA $google2fa,
        private readonly EncrypterInterface $encrypter,
        private readonly string $issuer,
    ) {
    }

    public function isEnabled(int $userId): bool
    {
        $row = $this->model->find($userId);

        return $row !== null && $row['confirmed_at'] !== null;
    }

    /**
     * Starts (or restarts) setup with a new secret. 2FA stays off until confirmed.
     *
     * @return array{secret: string, uri: string}
     */
    public function beginSetup(int $userId, string $accountLabel): array
    {
        $secret = $this->google2fa->generateSecretKey(32);

        $this->model->save([
            'user_id'            => $userId,
            'secret'             => $this->encrypt($secret),
            'recovery_codes'     => null,
            'confirmed_at'       => null,
            'last_used_timestep' => null,
        ]);

        return [
            'secret' => $secret,
            'uri'    => $this->google2fa->getQRCodeUrl($this->issuer, $accountLabel, $secret),
        ];
    }

    /**
     * Confirms setup with a code from the app.
     *
     * @return list<string>|null Plain recovery codes (show once), or null if the code is wrong.
     */
    public function confirmSetup(int $userId, string $code): ?array
    {
        $row = $this->model->find($userId);

        if ($row === null || $row['confirmed_at'] !== null) {
            return null;
        }

        $timestep = $this->checkTotp($this->decrypt($row['secret']), $code, null);

        if ($timestep === null) {
            return null;
        }

        [$plain, $hashed] = $this->makeRecoveryCodes();

        $this->model->update($userId, [
            'recovery_codes'     => json_encode($hashed),
            'confirmed_at'       => date('Y-m-d H:i:s'),
            'last_used_timestep' => $timestep,
        ]);

        service('audit')->log('2fa_enabled', 'users', $userId, null, null, 'Verifikasi dua langkah diaktifkan', $userId);

        return $plain;
    }

    /**
     * Verifies a login code: a TOTP code or an unused recovery code.
     */
    public function verify(int $userId, string $code): bool
    {
        $row = $this->model->find($userId);

        if ($row === null || $row['confirmed_at'] === null) {
            return false;
        }

        $last     = $row['last_used_timestep'] === null ? null : (int) $row['last_used_timestep'];
        $timestep = $this->checkTotp($this->decrypt($row['secret']), $code, $last);

        if ($timestep !== null) {
            $this->model->update($userId, ['last_used_timestep' => $timestep]);

            return true;
        }

        return $this->useRecoveryCode($userId, $row, $code);
    }

    /**
     * @return list<string> New plain recovery codes; old ones stop working.
     */
    public function regenerateRecoveryCodes(int $userId): array
    {
        [$plain, $hashed] = $this->makeRecoveryCodes();

        $this->model->update($userId, ['recovery_codes' => json_encode($hashed)]);

        return $plain;
    }

    public function remainingRecoveryCodes(int $userId): int
    {
        $row = $this->model->find($userId);

        return $row === null ? 0 : count(json_decode((string) $row['recovery_codes'], true) ?: []);
    }

    public function disable(int $userId): void
    {
        $this->model->delete($userId);

        service('audit')->log('2fa_disabled', 'users', $userId, null, null, 'Verifikasi dua langkah dimatikan');
    }

    /**
     * @return int|null Matched time step, or null when the code is invalid or already used.
     */
    private function checkTotp(string $secret, string $code, ?int $lastTimestep): ?int
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== 6) {
            return null;
        }

        try {
            // With a previous step given (0 = none yet) the library returns the
            // matched step, and rejects codes from that step or older (no replay).
            $result = $this->google2fa->verifyKeyNewer($secret, $code, $lastTimestep ?? 0, self::WINDOW);
        } catch (Throwable) {
            return null;
        }

        return is_int($result) ? $result : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function useRecoveryCode(int $userId, array $row, string $code): bool
    {
        $normalised = $this->normaliseRecoveryCode($code);

        if (strlen($normalised) !== 10) {
            return false;
        }

        $hashes = json_decode((string) $row['recovery_codes'], true) ?: [];

        foreach ($hashes as $index => $hash) {
            if (password_verify($normalised, $hash)) {
                unset($hashes[$index]);
                $this->model->update($userId, ['recovery_codes' => json_encode(array_values($hashes))]);

                service('audit')->log('2fa_recovery_used', 'users', $userId, null, null, 'Kode cadangan dipakai', $userId);

                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: list<string>, 1: list<string>} [plain, hashed]
     */
    private function makeRecoveryCodes(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O, 1/I
        $plain    = [];
        $hashed   = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $raw = '';

            for ($j = 0; $j < 10; $j++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $plain[]  = substr($raw, 0, 5) . '-' . substr($raw, 5);
            $hashed[] = password_hash($raw, PASSWORD_DEFAULT);
        }

        return [$plain, $hashed];
    }

    private function normaliseRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    private function encrypt(string $value): string
    {
        return base64_encode($this->encrypter->encrypt($value));
    }

    private function decrypt(string $value): string
    {
        return $this->encrypter->decrypt(base64_decode($value, true) ?: '');
    }
}
