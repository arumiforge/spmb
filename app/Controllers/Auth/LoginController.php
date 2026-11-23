<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Controllers\LoginController as ShieldLoginController;
use CodeIgniter\Shield\Validation\ValidationRules;

/**
 * Staff sign-in with one field for the email address or the username (D-080).
 *
 * The form posts `login`; a value containing "@" is tried as the email, anything
 * else as the username. Everything else (throttling, remember me, 2FA action)
 * stays Shield's.
 */
class LoginController extends ShieldLoginController
{
    public function loginAction(): RedirectResponse
    {
        $post = (array) $this->request->getPost();
        // `login` is the form field; `email`/`username` are still accepted from older forms and clients.
        $login         = trim((string) ($post['login'] ?? $post['email'] ?? $post['username'] ?? ''));
        $post['login'] = $login;

        unset($post['email'], $post['username']);

        if ($login !== '') {
            $post[str_contains($login, '@') ? 'email' : 'username'] = $login;
        }

        $this->request->setGlobal('post', $post);

        return parent::loginAction();
    }

    /**
     * @return array<string, array<string, list<string>|string>|string>
     */
    protected function getValidationRules(): array
    {
        return [
            'login' => [
                'label'  => 'Akun.loginField',
                'rules'  => ['required', 'max_length[254]'],
                'errors' => ['required' => 'Akun.loginFieldRequired'],
            ],
            'password' => (new ValidationRules())->getPasswordRules(),
        ];
    }
}
