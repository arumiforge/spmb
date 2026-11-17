<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;

/**
 * Runs after the session filter on every /admin page:
 * 1. an account flagged for a password change goes to the change-password page;
 * 2. a user whose role requires two-step verification, but who has not turned
 *    it on yet, goes to the two-step setup page.
 * The pages needed to fix the situation (and sign out) stay reachable.
 */
class StaffArea implements FilterInterface
{
    private const PASSWORD_PAGE   = 'admin/profil/kata-sandi';
    private const TWO_FACTOR_PAGE = 'admin/profil/verifikasi-dua-langkah';

    public function before(RequestInterface $request, $arguments = null)
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $path = trim($request->getUri()->getPath(), '/');

        if ($user->requiresPasswordReset() && ! $this->startsWith($path, self::PASSWORD_PAGE)) {
            return redirect()->to(self::PASSWORD_PAGE)
                ->with('warning', 'Silakan buat kata sandi baru sebelum melanjutkan.');
        }

        if (self::mustSetUpTwoFactor($user) && ! $this->startsWith($path, self::TWO_FACTOR_PAGE) && ! $this->startsWith($path, self::PASSWORD_PAGE)) {
            return redirect()->to(self::TWO_FACTOR_PAGE)
                ->with('warning', 'Peran Anda wajib memakai verifikasi dua langkah. Aktifkan dulu untuk melanjutkan.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    /**
     * True when one of the user's roles requires 2FA and it is not on yet.
     */
    public static function mustSetUpTwoFactor(User $user): bool
    {
        return self::roleRequiresTwoFactor($user) && ! service('twoFactor')->isEnabled((int) $user->id);
    }

    public static function roleRequiresTwoFactor(User $user): bool
    {
        $required = setting('TwoFactor.requiredRoles');

        return is_array($required) && array_intersect($user->getGroups() ?? [], $required) !== [];
    }

    private function startsWith(string $path, string $prefix): bool
    {
        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }
}
