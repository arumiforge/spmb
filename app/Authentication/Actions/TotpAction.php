<?php

declare(strict_types=1);

namespace App\Authentication\Actions;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Actions\ActionInterface;
use CodeIgniter\Shield\Authentication\Actions\ConditionalActionInterface;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Entities\UserIdentity;
use CodeIgniter\Shield\Models\UserIdentityModel;

/**
 * Login step for users who turned on two-step verification (D-006, D-016).
 *
 * After the password is accepted Shield keeps the user "pending" and sends them
 * to /verifikasi. The marker identity created here stays in the database until a
 * valid code is entered, so a remember-me cookie cannot skip this step either.
 * Codes are checked by service('twoFactor')->verify() (TOTP or recovery code).
 */
class TotpAction implements ActionInterface, ConditionalActionInterface
{
    public const TYPE          = 'totp_2fa';
    private const ATTEMPTS_KEY = 'spmb_2fa_attempts';

    public function appliesTo(User $user): bool
    {
        return service('twoFactor')->isEnabled((int) $user->id);
    }

    public function show(): string
    {
        $user = auth('session')->getAuthenticator()->getPendingUser();

        if ($user === null) {
            return view('auth/two_factor_verify', ['expired' => true]);
        }

        return view('auth/two_factor_verify', ['expired' => false]);
    }

    /**
     * Not used: there is nothing to send. Kept for the interface.
     */
    public function handle(IncomingRequest $request): RedirectResponse
    {
        return redirect()->route('auth-action-show');
    }

    /**
     * @return RedirectResponse
     */
    public function verify(IncomingRequest $request)
    {
        $authenticator = auth('session')->getAuthenticator();
        $user          = $authenticator->getPendingUser();

        if ($user === null) {
            return redirect()->route('login')->with('error', lang('Akun.verifyExpired'));
        }

        $code     = trim((string) $request->getPost('code'));
        $identity = $this->getIdentity($user);

        if ($code !== '' && $identity instanceof UserIdentity && service('twoFactor')->verify((int) $user->id, $code)) {
            session()->remove(self::ATTEMPTS_KEY);
            $authenticator->checkAction($identity, $identity->secret);

            return redirect()->to(config('Auth')->loginRedirect());
        }

        $attempts = (int) session(self::ATTEMPTS_KEY) + 1;
        session()->set(self::ATTEMPTS_KEY, $attempts);

        if ($attempts >= (int) setting('TwoFactor.maxAttempts')) {
            session()->remove(self::ATTEMPTS_KEY);
            service('audit')->log('2fa_failed', 'users', (int) $user->id, null, null, 'Kode verifikasi salah berkali-kali; proses masuk dibatalkan', (int) $user->id);
            $authenticator->logout();

            return redirect()->route('login')->with('error', lang('Akun.verifyTooManyAttempts'));
        }

        return redirect()->route('auth-action-show')->with('error', lang('Akun.verifyWrongCode'));
    }

    public function createIdentity(User $user): string
    {
        /** @var UserIdentityModel $identities */
        $identities = model(UserIdentityModel::class);
        $identities->deleteIdentitiesByType($user, self::TYPE);

        return $identities->createCodeIdentity(
            $user,
            [
                'type'  => self::TYPE,
                'name'  => 'login',
                'extra' => lang('Akun.verifyPendingMessage'),
            ],
            static fn (): string => bin2hex(random_bytes(16)),
        );
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    private function getIdentity(User $user): ?UserIdentity
    {
        return model(UserIdentityModel::class)->getIdentityByType($user, self::TYPE);
    }
}
