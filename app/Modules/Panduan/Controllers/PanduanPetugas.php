<?php

declare(strict_types=1);

namespace Modules\Panduan\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Panduan\Libraries\GuideBuilder;

/**
 * Staff guides inside the dashboard (D-097). Everyone signed in reads the guide of their
 * own role; Super Admin (Arumi Studios, who supports every role) may read all of them.
 */
class PanduanPetugas extends BaseController
{
    private GuideBuilder $guides;

    public function __construct()
    {
        $this->guides = new GuideBuilder(service('modules'), require dirname(__DIR__) . '/Config/Roles.php');
    }

    public function index(): RedirectResponse|string
    {
        $mine = $this->allowed();

        if (count($mine) === 1) {
            return redirect()->to(site_url('admin/panduan/' . array_key_first($mine)));
        }

        return view('Modules\Panduan\Views\admin_index', [
            'title'       => lang('Panduan.title'),
            'subtitle'    => lang('Panduan.staffIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Panduan']],
            'roles'       => $mine,
            'public'      => array_filter($this->guides->roles(), static fn (array $role): bool => $role['group'] === null),
        ]);
    }

    public function show(string $slug): RedirectResponse|string
    {
        $asked = $slug;
        $slug  = Panduan::MOVED[$slug] ?? $slug;
        $roles = $this->guides->roles();

        if (isset($roles[$slug]) && $roles[$slug]['group'] === null) {
            return redirect()->to(site_url('panduan/' . $slug));
        }

        $mine = $this->allowed();

        if (! isset($mine[$slug])) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($slug !== $asked) {
            return redirect()->to(site_url('admin/panduan/' . $slug), 301);
        }

        $guide = $this->guides->render($slug);

        return view('Modules\Panduan\Views\admin_show', [
            'title'       => 'Panduan ' . $roles[$slug]['title'],
            'subtitle'    => $roles[$slug]['description'],
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Panduan', 'url' => 'admin/panduan'], ['label' => $roles[$slug]['title']]],
            'role'        => $roles[$slug],
            'slug'        => $slug,
            'others'      => array_map(static fn (array $role): string => $role['title'], array_diff_key($mine, [$slug => true])),
            'base'        => 'admin/panduan/',
            'html'        => $guide['html'],
            'toc'         => $guide['toc'],
        ]);
    }

    /**
     * Staff guides this user may read, in the order of Config/Roles.php.
     *
     * @return array<string, array{title: string, description: string, group: string|null, icon: string}>
     */
    private function allowed(): array
    {
        $user = auth()->user();
        $all  = $user->inGroup('superadmin');

        return array_filter(
            $this->guides->roles(),
            static fn (array $role): bool => $role['group'] !== null && ($all || $user->inGroup($role['group'])),
        );
    }
}
