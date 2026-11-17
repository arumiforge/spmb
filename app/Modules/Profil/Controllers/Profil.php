<?php

declare(strict_types=1);

namespace Modules\Profil\Controllers;

use App\Controllers\BaseController;
use App\Filters\StaffArea;

class Profil extends BaseController
{
    public function index(): string
    {
        $user = auth()->user();

        return view('Modules\Profil\Views\index', [
            'title'       => lang('Akun.profileTitle'),
            'subtitle'    => lang('Akun.profileIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Profil Saya']],
            'user'        => $user,
            'roles'       => array_map(
                static fn (string $group): string => config('AuthGroups')->groups[$group]['title'] ?? $group,
                $user->getGroups() ?? [],
            ),
            'twoFactorOn'       => service('twoFactor')->isEnabled((int) $user->id),
            'twoFactorRequired' => StaffArea::roleRequiresTwoFactor($user),
        ]);
    }
}
