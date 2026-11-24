<?php

declare(strict_types=1);

namespace Modules\Panduan\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Panduan\Libraries\GuideBuilder;

/**
 * Public guide pages: only the guides without a Shield group (applicants and parents).
 * Staff guides moved into the dashboard (D-097); their old public addresses redirect there,
 * where signing in is required.
 */
class Panduan extends BaseController
{
    /**
     * Guides of roles merged into Panitia or Admin Sekolah (D-063) => new slug.
     */
    public const MOVED = [
        'panitia-pendaftaran'  => 'panitia',
        'petugas-tes'          => 'panitia',
        'petugas-daftar-ulang' => 'panitia',
        'petugas-seragam'      => 'panitia',
        'kepala-sekolah'       => 'admin-sekolah',
    ];

    private GuideBuilder $guides;

    public function __construct()
    {
        $this->guides = new GuideBuilder(service('modules'), self::translatedRoles(require dirname(__DIR__) . '/Config/Roles.php'));
    }

    /**
     * Titles and descriptions of the public guides in the page's language (Panduan.roles.<slug>, D-109).
     *
     * @param array<string, array{title: string, description: string, group: string|null, icon: string}> $roles
     *
     * @return array<string, array{title: string, description: string, group: string|null, icon: string}>
     */
    private static function translatedRoles(array $roles): array
    {
        foreach ($roles as $slug => $role) {
            $text = lang('Panduan.roles.' . $slug);

            if ($role['group'] === null && is_array($text)) {
                $roles[$slug] = ['title' => (string) ($text['title'] ?? $role['title']), 'description' => (string) ($text['description'] ?? $role['description'])] + $role;
            }
        }

        return $roles;
    }

    public function index(): RedirectResponse|string
    {
        $public = array_filter($this->guides->roles(), static fn (array $role): bool => $role['group'] === null);

        // One public guide (the usual case): open it straight away.
        if (count($public) === 1) {
            return redirect()->to(site_url('panduan/' . array_key_first($public)));
        }

        return view('Modules\Panduan\Views\index', [
            'title'       => lang('Panduan.title'),
            'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('App.guide')]],
            'roles'       => $public,
        ]);
    }

    public function show(string $slug): RedirectResponse|string
    {
        $asked = $slug;
        $slug  = self::MOVED[$slug] ?? $slug;
        $roles = $this->guides->roles();

        if (! isset($roles[$slug])) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($roles[$slug]['group'] !== null) {
            return redirect()->to(site_url('admin/panduan/' . $slug), 301);
        }

        if ($slug !== $asked) {
            return redirect()->to(site_url('panduan/' . $slug), 301);
        }

        $guide  = $this->guides->render($slug);
        $public = array_filter($roles, static fn (array $role): bool => $role['group'] === null);

        return view('Modules\Panduan\Views\show', [
            'title'       => lang('Panduan.pageTitle', [$roles[$slug]['title']]),
            'breadcrumbs' => [['label' => lang('App.home'), 'url' => '/'], ['label' => lang('Panduan.pageTitle', [$roles[$slug]['title']])]],
            'role'        => $roles[$slug],
            'slug'        => $slug,
            'others'      => array_map(static fn (array $role): string => $role['title'], array_diff_key($public, [$slug => true])),
            'base'        => 'panduan/',
            'html'        => $guide['html'],
            'toc'         => $guide['toc'],
        ]);
    }
}
