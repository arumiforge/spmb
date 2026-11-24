<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\SetupChecklist;
use Modules\Pendaftaran\Services\ApplicantStats;

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
            // Registration counts while the Pendaftaran module is on (null otherwise).
            'stats'       => service('modules')->isEnabled('Pendaftaran') ? (new ApplicantStats())->summary() : null,
            'canViewList' => $user->can('pendaftar.view'),
            // "Persiapan sistem" (D-099) for the people who set the system up, with only the steps they can fix.
            'setup' => $user->can('pengaturan.sekolah') || $user->can('sistem.server') ? (new SetupChecklist())->steps($user) : [],
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
