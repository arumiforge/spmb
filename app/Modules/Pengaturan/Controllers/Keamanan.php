<?php

declare(strict_types=1);

namespace Modules\Pengaturan\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Security policy (Super Admin): which roles must use two-step verification.
 */
class Keamanan extends BaseController
{
    public function index(): string
    {
        $groups = config('AuthGroups')->groups;
        $counts = [];
        $db     = db_connect();
        $tables = config('Auth')->tables;

        foreach (array_keys($groups) as $group) {
            $total   = $db->table($tables['groups_users'])->where('group', $group)->countAllResults();
            $enabled = $db->table('user_two_factor')
                ->whereIn('user_id', static fn ($builder) => $builder->select('user_id')->from($tables['groups_users'])->where('group', $group))
                ->where('confirmed_at IS NOT NULL')
                ->countAllResults();

            $counts[$group] = [$enabled, $total];
        }

        return view('Modules\Pengaturan\Views\keamanan', [
            'title'       => lang('Pengaturan.securityTitle'),
            'subtitle'    => lang('Pengaturan.securityIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Keamanan']],
            'groups'      => $groups,
            'required'    => (array) setting('TwoFactor.requiredRoles'),
            'counts'      => $counts,
        ]);
    }

    public function update(): RedirectResponse
    {
        $groups   = array_keys(config('AuthGroups')->groups);
        $selected = array_values(array_intersect($groups, (array) $this->request->getPost('roles')));
        $old      = (array) setting('TwoFactor.requiredRoles');

        setting('TwoFactor.requiredRoles', $selected);
        service('audit')->log('settings_updated', 'settings', 'TwoFactor', ['requiredRoles' => $old], ['requiredRoles' => $selected], 'Kebijakan verifikasi dua langkah diubah');

        return redirect()->route('pengaturan.keamanan')->with('message', lang('Pengaturan.securitySaved'));
    }
}
