<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Shield\Entities\User;

/**
 * Builds the admin sidebar from the menu entries of enabled modules,
 * keeping only the entries the user has permission for.
 *
 * Each module may return a list of entries from Config/Menu.php:
 *   ['label' => 'Pengguna', 'url' => 'admin/pengguna', 'icon' => 'users',
 *    'permission' => 'pengguna.view', 'group' => 'Pengaturan', 'order' => 80]
 * Entries with the same 'group' are shown under one heading.
 */
class Menu
{
    /**
     * Groups in display order; entries without a group go first.
     */
    private const GROUP_ORDER = ['', 'Pendaftaran', 'Tes', 'Laporan', 'Website', 'Pengaturan'];

    /**
     * @param list<array<string, mixed>> $coreItems Entries that do not belong to a module
     */
    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly array $coreItems = [],
    ) {
    }

    /**
     * @return list<array<string, mixed>> All entries from enabled modules (unfiltered).
     */
    public function items(): array
    {
        return [...$this->coreItems, ...$this->modules->collect('Config/Menu.php')];
    }

    /**
     * Sidebar groups for this user: [['label' => '', 'items' => [...]], ...].
     *
     * @return list<array{label: string, items: list<array<string, mixed>>}>
     */
    public function forUser(User $user, string $currentPath): array
    {
        $currentPath = trim($currentPath, '/');
        $groups      = [];

        foreach ($this->items() as $item) {
            $permission = $item['permission'] ?? null;

            if ($permission !== null && ! $user->can($permission)) {
                continue;
            }

            $url            = trim((string) $item['url'], '/');
            $item['active'] = $url === 'admin'
                ? $currentPath === 'admin'
                : ($currentPath === $url || str_starts_with($currentPath, $url . '/'));

            $groups[(string) ($item['group'] ?? '')][] = $item;
        }

        uksort($groups, static function (string $a, string $b): int {
            $ia = array_search($a, self::GROUP_ORDER, true);
            $ib = array_search($b, self::GROUP_ORDER, true);

            return [$ia === false ? 99 : $ia, $a] <=> [$ib === false ? 99 : $ib, $b];
        });

        $result = [];

        foreach ($groups as $label => $items) {
            usort($items, static fn (array $a, array $b): int => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));
            $result[] = ['label' => (string) $label, 'items' => $items];
        }

        return $result;
    }
}
