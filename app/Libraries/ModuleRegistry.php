<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Finds feature modules in app/Modules and tells which are switched on.
 *
 * A module is a folder app/Modules/<Name> (namespace Modules\<Name>). Its
 * routes (Config/Routes.php) and sidebar entries (Config/Menu.php) are only
 * loaded while it is enabled, so a disabled module's pages return 404.
 */
class ModuleRegistry
{
    /**
     * @param list<string> $core     Modules that cannot be disabled
     * @param list<string> $disabled Disabled module names
     */
    public function __construct(
        private readonly string $path,
        private readonly array $core,
        private readonly array $disabled,
    ) {
    }

    /**
     * @return list<string> All module names, sorted.
     */
    public function all(): array
    {
        $names = [];

        foreach (glob(rtrim($this->path, '/') . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);

            if (preg_match('/\A[A-Z][A-Za-z0-9]*\z/', $name) === 1) {
                $names[] = $name;
            }
        }

        sort($names);

        return $names;
    }

    /**
     * @return list<string>
     */
    public function enabled(): array
    {
        return array_values(array_filter($this->all(), $this->isEnabled(...)));
    }

    /**
     * A module is on unless it is switched off, or a module it requires
     * (Config/Module.php 'requires') is off or missing. Core modules are always on.
     *
     * @param list<string> $seen Guards against circular requirements
     */
    public function isEnabled(string $name, array $seen = []): bool
    {
        if (in_array($name, $this->core, true)) {
            return true;
        }

        if (in_array($name, $this->disabled, true) || in_array($name, $seen, true)) {
            return false;
        }

        foreach ($this->requires($name) as $required) {
            if (! is_dir(rtrim($this->path, '/') . '/' . $required) || ! $this->isEnabled($required, [...$seen, $name])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Modules this one needs (Config/Module.php 'requires').
     *
     * @return list<string>
     */
    public function requires(string $name): array
    {
        $requires = $this->meta($name)['requires'] ?? [];

        return is_array($requires) ? array_values(array_filter($requires, is_string(...))) : [];
    }

    /**
     * Raw Config/Module.php array of a module (empty when missing).
     *
     * @return array<string, mixed>
     */
    private function meta(string $name): array
    {
        $file = rtrim($this->path, '/') . '/' . $name . '/Config/Module.php';
        $meta = is_file($file) ? require $file : [];

        return is_array($meta) ? $meta : [];
    }

    public function isCore(string $name): bool
    {
        return in_array($name, $this->core, true);
    }

    /**
     * Label, description and icon of a module from its Config/Module.php
     * (read even while the module is switched off). Falls back to the folder name.
     *
     * @return array{name: string, label: string, description: string, icon: string, core: bool, enabled: bool, requires: list<string>}
     */
    public function info(string $name): array
    {
        $meta = $this->meta($name);

        return [
            'name'        => $name,
            'label'       => (string) ($meta['label'] ?? $name),
            'description' => (string) ($meta['description'] ?? ''),
            'icon'        => (string) ($meta['icon'] ?? 'grid'),
            'core'        => $this->isCore($name),
            'enabled'     => $this->isEnabled($name),
            'requires'    => $this->requires($name),
        ];
    }

    /**
     * Disabled module names that still exist and are not core.
     *
     * @return list<string>
     */
    public function disabled(): array
    {
        return array_values(array_filter($this->all(), fn (string $name): bool => ! $this->isEnabled($name)));
    }

    /**
     * Entries of a list-returning config file (e.g. Config/PublicMenu.php) from
     * every enabled module, each tagged with 'module'.
     *
     * @return list<array<string, mixed>>
     */
    public function collect(string $relative): array
    {
        $items = [];

        foreach ($this->enabled() as $module) {
            $file = $this->file($module, $relative);

            if ($file === null) {
                continue;
            }

            $entries = require $file;

            foreach (is_array($entries) ? $entries : [] as $entry) {
                if (is_array($entry)) {
                    $items[] = $entry + ['module' => $module];
                }
            }
        }

        return $items;
    }

    /**
     * View names of a panel that enabled modules provide in Views/panels/<name>.php,
     * e.g. panels('applicant_admin') → ['Modules\Berkas\Views\panels\applicant_admin', ...].
     * Lets one module's page show sections owned by other modules without
     * depending on them (docs/phase-2-plan.md, "Panels").
     *
     * @return list<string>
     */
    public function panels(string $name): array
    {
        $views = [];

        foreach ($this->enabled() as $module) {
            if ($this->file($module, 'Views/panels/' . $name . '.php') !== null) {
                $views[] = 'Modules\\' . $module . '\\Views\\panels\\' . $name;
            }
        }

        return $views;
    }

    /**
     * Absolute path of a file inside an enabled module, or null.
     */
    public function file(string $module, string $relative): ?string
    {
        $file = rtrim($this->path, '/') . '/' . $module . '/' . ltrim($relative, '/');

        return $this->isEnabled($module) && is_file($file) ? $file : null;
    }
}
