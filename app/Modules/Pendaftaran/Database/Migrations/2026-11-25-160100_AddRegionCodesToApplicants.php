<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Region codes of the applicant's address from the national list (D-111):
 * province, regency/city, district and village. The text columns province,
 * regency, district and village stay and hold the display names of the chosen
 * regions (or the free text typed when no region was chosen). Statistik groups
 * by both. Older rows are matched with `php spark referensi:cocokkan-alamat`.
 */
class AddRegionCodesToApplicants extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('applicants', [
            'province_code' => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => true, 'after' => 'province'],
            'regency_code'  => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'after' => 'province_code'],
            'district_code' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true, 'after' => 'regency_code'],
            'village_code'  => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true, 'after' => 'district_code'],
        ]);

        $table = $this->db->prefixTable('applicants');

        foreach (['regency_code', 'district_code', 'village_code'] as $column) {
            $this->db->query(
                'CREATE INDEX ' . $this->db->escapeIdentifiers('applicants_' . $column)
                . ' ON ' . $this->db->escapeIdentifiers($table) . ' (' . $this->db->escapeIdentifiers($column) . ')',
            );
        }
    }

    public function down(): void
    {
        $this->forge->dropColumn('applicants', ['province_code', 'regency_code', 'district_code', 'village_code']);
    }
}
