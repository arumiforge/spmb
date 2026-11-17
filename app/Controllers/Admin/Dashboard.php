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
            'breadcrumbs' => [['label' => 'Dasbor']],
            'user'        => $user,
            'roles'       => array_map(
                static fn (string $group): string => config('AuthGroups')->groups[$group]['title'] ?? $group,
                $user->getGroups(),
            ),
        ]);
    }
}
