<?php

declare(strict_types=1);

namespace Modules\Referensi\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * National list of administrative regions (D-111): provinces (level 1),
 * regencies and cities (2), districts (3) and villages (4), keyed by the
 * Kemendagri code ("33", "33.19", "33.19.03", "33.19.03.2006"). Filled from the
 * bundled data by `php spark referensi:import --bawaan` (BundledRegions).
 * Names keep the official spelling ("Kabupaten Kudus").
 */
class CreateRefWilayah extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'code'        => ['type' => 'VARCHAR', 'constraint' => 13],
            'parent_code' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
            'level'       => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'comment' => '1 provinsi, 2 kabupaten/kota, 3 kecamatan, 4 desa/kelurahan'],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addPrimaryKey('code');
        // Children of a region in name order (the address selects).
        $this->forge->addKey(['parent_code', 'name']);
        // Name search per level ("Ploso", "Jati Kulon"): prefix lookups use this index.
        $this->forge->addKey(['level', 'name']);
        $this->forge->createTable('ref_wilayah', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('ref_wilayah', true);
    }
}
