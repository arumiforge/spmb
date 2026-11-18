<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Only three roles remain (D-063): Admin Sekolah, Panitia and the applicant
 * access. Accounts in the removed groups (Kepala Sekolah, Petugas Tes,
 * Petugas Daftar Ulang, Petugas Seragam) become Panitia, and the removed
 * groups are dropped from the two-step verification policy. Not reversible:
 * down() leaves the accounts as they are.
 */
class MergeStaffRolesIntoPanitia extends Migration
{
    private const REMOVED = ['kepsek', 'petugas-tes', 'petugas-daftar-ulang', 'petugas-seragam'];

    public function up(): void
    {
        $table = $this->db->table('auth_groups_users');
        $rows  = $table->select('user_id')->whereIn('group', self::REMOVED)->distinct()->get()->getResultArray();
        $now   = date('Y-m-d H:i:s');

        foreach (array_column($rows, 'user_id') as $userId) {
            $hasPanitia = $this->db->table('auth_groups_users')->where('user_id', $userId)->where('group', 'panitia')->countAllResults() > 0;

            if (! $hasPanitia) {
                $this->db->table('auth_groups_users')->insert(['user_id' => $userId, 'group' => 'panitia', 'created_at' => $now]);
            }
        }

        $this->db->table('auth_groups_users')->whereIn('group', self::REMOVED)->delete();

        // Two-step verification policy stored by the security settings page.
        $setting = $this->db->table('settings')->where('class', 'Config\TwoFactor')->where('key', 'requiredRoles')->get()->getRowArray();

        if ($setting !== null) {
            // codeigniter4/settings stores arrays serialized.
            $roles = @unserialize((string) $setting['value'], ['allowed_classes' => false]);

            if (is_array($roles) && array_intersect($roles, self::REMOVED) !== []) {
                $kept = array_values(array_diff($roles, self::REMOVED));

                if (array_intersect($roles, ['petugas-tes', 'petugas-daftar-ulang', 'petugas-seragam']) !== [] && ! in_array('panitia', $kept, true)) {
                    $kept[] = 'panitia';
                }

                $this->db->table('settings')->where('id', $setting['id'])->update(['value' => serialize($kept), 'type' => 'array', 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Merged accounts stay Panitia.
    }
}
