<?php

declare(strict_types=1);

namespace Modules\Referensi\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Normalised previous schools (SD, MI, ...) from public Dapodik/EMIS data or manual entry (D-012).
 *
 * Phase 2 shared schema (docs/phase-2-plan.md). Owned by the coordinator:
 * work packages add new migrations instead of editing this one.
 */
class CreateRefSekolah extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'npsn'       => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'level'      => ['type' => 'VARCHAR', 'constraint' => 10, 'comment' => 'SD, MI, SDLB, ...'],
            'ownership'  => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'comment' => 'negeri | swasta'],
            'province'   => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'regency'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'comment' => 'kabupaten/kota'],
            'district'   => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'comment' => 'kecamatan'],
            'village'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'comment' => 'desa/kelurahan'],
            'address'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'source'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'manual', 'comment' => 'dapodik | emis | import | manual'],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('npsn');
        $this->forge->addKey('name');
        $this->forge->addKey(['province', 'regency', 'district']);
        $this->forge->createTable('ref_sekolah');
    }

    public function down(): void
    {
        $this->forge->dropTable('ref_sekolah', true);
    }
}
