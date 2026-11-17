<?php

declare(strict_types=1);

namespace Modules\Panduan\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Panduan\Libraries\GuideBuilder;

class Panduan extends BaseController
{
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

    public function show(string $slug): string
    {
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
