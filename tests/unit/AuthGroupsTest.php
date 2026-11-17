<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthGroups;

/**
 * @internal
 */
final class AuthGroupsTest extends CIUnitTestCase
{
    private AuthGroups $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = config('AuthGroups');
    }

    public function testEveryGroupHasAMatrixEntry(): void
    {
        $this->assertSame([], array_diff(array_keys($this->config->groups), array_keys($this->config->matrix)));
        $this->assertSame([], array_diff(array_keys($this->config->matrix), array_keys($this->config->groups)));
    }

    public function testMatrixOnlyUsesDeclaredPermissions(): void
    {
        $scopes = array_unique(array_map(
            static fn (string $key): string => explode('.', $key)[0],
            array_keys($this->config->permissions),
        ));

        foreach ($this->config->matrix as $group => $permissions) {
            foreach ($permissions as $permission) {
                if (str_ends_with($permission, '.*')) {
                    $this->assertContains(substr($permission, 0, -2), $scopes, "{$group}: unknown scope {$permission}");
                } else {
                    $this->assertArrayHasKey($permission, $this->config->permissions, "{$group}: unknown permission {$permission}");
                }
            }
        }
    }

    public function testSuperAdminCoversEveryScope(): void
    {
        $scopes = array_unique(array_map(
            static fn (string $key): string => explode('.', $key)[0] . '.*',
            array_keys($this->config->permissions),
        ));

        $this->assertSame([], array_values(array_diff($scopes, $this->config->matrix['superadmin'])));
    }

    public function testOnlySuperAdminManagesAdminsAndSystem(): void
    {
        foreach ($this->config->matrix as $group => $permissions) {
            if ($group === 'superadmin') {
                continue;
            }

            $this->assertNotContains('sistem.*', $permissions, $group);
            $this->assertNotContains('pengguna.*', $permissions, $group);
            $this->assertNotContains('pengguna.kelola-admin', $permissions, $group);
        }
    }

    /**
     * Phase 2 (registration) permissions: who may do what (docs/phase-2-plan.md).
     */
    public function testRegistrationPermissions(): void
    {
        $holders = function (string $permission): array {
            $scope = explode('.', $permission)[0] . '.*';

            return array_keys(array_filter(
                $this->config->matrix,
                static fn (array $list): bool => in_array($permission, $list, true) || in_array($scope, $list, true),
            ));
        };

        $this->assertSame(['superadmin', 'admin'], $holders('referensi.kelola'));
        $this->assertSame(['superadmin', 'admin'], $holders('pengaturan.pendaftaran'));
        $this->assertSame(['superadmin', 'admin', 'panitia'], $holders('pendaftar.create'));
        $this->assertSame(['superadmin', 'admin', 'panitia'], $holders('pendaftar.verifikasi'));
        $this->assertSame(['superadmin', 'admin', 'panitia', 'petugas-tes'], $holders('kartu.cetak'));
        $this->assertSame(['superadmin', 'admin'], $holders('pendaftar.delete'));
    }

    public function testDefaultGroupExists(): void
    {
        $this->assertArrayHasKey($this->config->defaultGroup, $this->config->groups);
    }
}
