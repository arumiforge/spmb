<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Registration waves (gelombang) per academic year; the wave is picked from the registration date (FR-REG-04).
 *
 * Phase 2 shared schema (docs/phase-2-plan.md). Owned by the coordinator:
 * work packages add new migrations instead of editing this one.
 */
class CreateWaves extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'academic_year' => ['type' => 'VARCHAR', 'constraint' => 9, 'comment' => 'e.g. 2027/2028'],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 60],
            'starts_on'     => ['type' => 'DATE'],
            'ends_on'       => ['type' => 'DATE'],
            'quota'         => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'null = no limit'],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['academic_year', 'starts_on']);
        $this->forge->createTable('waves');
    }

    public function down(): void
    {
        $this->forge->dropTable('waves', true);
    }
}
