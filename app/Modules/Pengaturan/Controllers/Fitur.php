<?php

declare(strict_types=1);

namespace Modules\Pengaturan\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Switch feature modules on or off (Super Admin, sistem.modul).
 *
 * The list of disabled modules is stored in setting('Features.disabled').
 * Core modules (Config\Features::$core) are always on. A disabled module keeps
 * its data; only its pages, menu entries and guide sections disappear.
 */
class Fitur extends BaseController
{
    public function index(): string
    {
        $registry = service('modules');
        $modules  = array_map($registry->info(...), $registry->all());

        // Optional features first (they are what this page is for), then core.
        usort($modules, static fn (array $a, array $b): int => [$a['core'], $a['label']] <=> [$b['core'], $b['label']]);

        return view('Modules\Pengaturan\Views\fitur', [
            'title'       => lang('Pengaturan.featuresTitle'),
            'subtitle'    => lang('Pengaturan.featuresIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Fitur']],
            'modules'     => $modules,
        ]);
    }

    public function update(): RedirectResponse
    {
        $registry = service('modules');
        $on       = array_map('strval', (array) $this->request->getPost('aktif'));
        $disabled = [];

        foreach ($registry->all() as $name) {
            if (! $registry->isCore($name) && ! in_array($name, $on, true)) {
                $disabled[] = $name;
            }
        }

        $old = $registry->disabled();

        if ($old === $disabled) {
            return redirect()->route('pengaturan.fitur')->with('message', lang('Pengaturan.featuresUnchanged'));
        }

        setting('Features.disabled', $disabled);

        $label = static fn (array $names): array => array_map(static fn (string $n): string => $registry->info($n)['label'], $names);
        service('audit')->log(
            'features_changed',
            'settings',
            'Features',
            ['disabled' => $label($old)],
            ['disabled' => $label($disabled)],
            'Fitur yang dipakai diubah',
        );

        return redirect()->route('pengaturan.fitur')->with('message', lang('Pengaturan.featuresSaved'));
    }
}
