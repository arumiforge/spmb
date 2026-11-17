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

    public function isEnabled(string $name): bool
    {
        return in_array($name, $this->core, true) || ! in_array($name, $this->disabled, true);
    }

    public function isCore(string $name): bool
    {
        return in_array($name, $this->core, true);
    }

    /**
     * Label, description and icon of a module from its Config/Module.php
     * (read even while the module is switched off). Falls back to the folder name.
     *
     * @return array{name: string, label: string, description: string, icon: string, core: bool, enabled: bool}
     */
    public function info(string $name): array
    {
        $file = rtrim($this->path, '/') . '/' . $name . '/Config/Module.php';
        $meta = is_file($file) ? require $file : [];
        $meta = is_array($meta) ? $meta : [];

        return [
            'name'        => $name,
            'label'       => (string) ($meta['label'] ?? $name),
            'description' => (string) ($meta['description'] ?? ''),
            'icon'        => (string) ($meta['icon'] ?? 'grid'),
            'core'        => $this->isCore($name),
            'enabled'     => $this->isEnabled($name),
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
     * Absolute path of a file inside an enabled module, or null.
     */
    public function file(string $module, string $relative): ?string
    {
        $file = rtrim($this->path, '/') . '/' . $module . '/' . ltrim($relative, '/');

        return $this->isEnabled($module) && is_file($file) ? $file : null;
    }
}
