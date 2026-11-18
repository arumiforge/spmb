<?php

declare(strict_types=1);

use App\Database\Migrations\MergeStaffRolesIntoPanitia;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * D-063: accounts in removed roles become Panitia; the 2FA policy drops them.
 *
 * @internal
 */
final class RoleMergeMigrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;

    public function testRemovedRolesBecomePanitia(): void
    {
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        foreach ([1 => 'kepsek', 2 => 'petugas-tes', 3 => 'panitia', 4 => 'admin'] as $id => $group) {
            $db->table('users')->insert(['id' => $id, 'username' => 'u' . $id, 'active' => 1, 'created_at' => $now]);
            $db->table('auth_groups_users')->insert(['user_id' => $id, 'group' => $group, 'created_at' => $now]);
        }

        $db->table('auth_groups_users')->insert(['user_id' => 3, 'group' => 'petugas-seragam', 'created_at' => $now]);
        $db->table('settings')->insert(['class' => 'Config\TwoFactor', 'key' => 'requiredRoles', 'value' => serialize(['admin', 'petugas-tes']), 'type' => 'array', 'context' => null, 'created_at' => $now, 'updated_at' => $now]);

        require_once APPPATH . 'Database/Migrations/2026-11-21-090000_MergeStaffRolesIntoPanitia.php';
        (new MergeStaffRolesIntoPanitia())->up();

        $groups = [];

        foreach ($db->table('auth_groups_users')->orderBy('user_id')->orderBy('group')->get()->getResultArray() as $row) {
            $groups[(int) $row['user_id']][] = $row['group'];
        }

        $this->assertSame([1 => ['panitia'], 2 => ['panitia'], 3 => ['panitia'], 4 => ['admin']], $groups);

        $value = $db->table('settings')->where('key', 'requiredRoles')->get()->getRowArray()['value'];
        $this->assertSame(['admin', 'panitia'], unserialize($value));
    }
}
