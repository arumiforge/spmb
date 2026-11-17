<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $user = auth()->user();

        return view('admin/dashboard', [
            'title'       => 'Dasbor',
            'subtitle'    => 'Selamat datang, ' . ($user->full_name ?: $user->username) . '.',
            'breadcrumbs' => [['label' => 'Dasbor']],
            'user'        => $user,
            'roles'       => array_map(
                static fn (string $group): string => config('AuthGroups')->groups[$group]['title'] ?? $group,
                $user->getGroups(),
            ),
            'twoFactorOn' => service('twoFactor')->isEnabled((int) $user->id),
        ]);
    }

    /**
     * Shown when a page needs a permission the user does not have.
     */
    public function denied(): string
    {
        $this->response->setStatusCode(403);

        return view('admin/denied', [
            'title'       => lang('App.deniedTitle'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Akses Ditolak']],
        ]);
    }
}
