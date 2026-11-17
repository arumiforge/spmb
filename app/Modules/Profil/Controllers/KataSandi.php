<?php

declare(strict_types=1);

namespace Modules\Profil\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Passwords;

/**
 * Change own password. Also the page users are sent to when an admin reset
 * their password (forced change).
 */
class KataSandi extends BaseController
{
    public function index(): string
    {
        $user = auth()->user();

        return view('Modules\Profil\Views\password', [
            'title'       => lang('Akun.passwordTitle'),
            'subtitle'    => lang('Akun.passwordIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Profil Saya', 'url' => 'admin/profil'], ['label' => 'Ganti Kata Sandi']],
            'forced'      => $user->requiresPasswordReset(),
        ]);
    }

    public function update(): RedirectResponse
    {
        $user = auth()->user();

        $rules = [
            'current_password' => ['label' => lang('Akun.currentPassword'), 'rules' => 'required'],
            'password'         => [
                'label' => lang('Akun.newPassword'),
                'rules' => ['required', Passwords::getMaxLengthRule(), 'strong_password[]'],
            ],
            'password_confirm' => [
                'label'  => lang('Akun.confirmPassword'),
                'rules'  => 'required|matches[password]',
                'errors' => ['matches' => lang('Akun.passwordMismatch')],
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        if (! service('passwords')->verify((string) $this->request->getPost('current_password'), (string) $user->password_hash)) {
            return redirect()->back()->with('errors', ['current_password' => lang('Akun.passwordWrong')]);
        }

        $user->setPassword((string) $this->request->getPost('password'));
        model(UserModel::class)->save($user);
        $user->undoForcePasswordReset();

        service('audit')->log('password_changed', 'users', (int) $user->id, null, null, 'Kata sandi diganti oleh pemilik akun');

        return redirect()->route('profil')->with('message', lang('Akun.passwordSaved'));
    }
}
