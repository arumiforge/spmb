<?php

declare(strict_types=1);

namespace Modules\Panduan\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Panduan\Libraries\GuideBuilder;

class Panduan extends BaseController
{
    /**
     * Guides of roles merged into Panitia or Admin Sekolah (D-063) => new slug.
     */
    private const MOVED = [
        'panitia-pendaftaran'  => 'panitia',
        'petugas-tes'          => 'panitia',
        'petugas-daftar-ulang' => 'panitia',
        'petugas-seragam'      => 'panitia',
        'kepala-sekolah'       => 'admin-sekolah',
    ];

    private GuideBuilder $guides;

    public function __construct()
    {
        $this->guides = new GuideBuilder(service('modules'), require dirname(__DIR__) . '/Config/Roles.php');
    }

    public function index(): string
    {
        $mine = [];

        if (auth()->loggedIn()) {
            foreach ($this->guides->roles() as $slug => $role) {
                if ($role['group'] !== null && auth()->user()->inGroup($role['group'])) {
                    $mine[] = $slug;
                }
            }
        }

        return view('Modules\Panduan\Views\index', [
            'title'       => lang('Panduan.title'),
            'breadcrumbs' => [['label' => 'Beranda', 'url' => '/'], ['label' => 'Panduan']],
            'roles'       => $this->guides->roles(),
            'mine'        => $mine,
        ]);
    }

    public function show(string $slug): RedirectResponse|string
    {
        if (isset(self::MOVED[$slug])) {
            return redirect()->to(site_url('panduan/' . self::MOVED[$slug]), 301);
        }

        $roles = $this->guides->roles();

        if (! isset($roles[$slug])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $guide = $this->guides->render($slug);

        return view('Modules\Panduan\Views\show', [
            'title'       => 'Panduan ' . $roles[$slug]['title'],
            'breadcrumbs' => [['label' => 'Beranda', 'url' => '/'], ['label' => 'Panduan', 'url' => 'panduan'], ['label' => $roles[$slug]['title']]],
            'role'        => $roles[$slug],
            'slug'        => $slug,
            'roles'       => $roles,
            'html'        => $guide['html'],
            'toc'         => $guide['toc'],
        ]);
    }
}
