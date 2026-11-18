<?php

declare(strict_types=1);

namespace Modules\Tes\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rooms used for the placement test (FR-TEST-01). Capacity is the default
 * quota of a session held in the room.
 *
 * Phase 3 shared schema (docs/phase-3-plan.md). Owned by the coordinator:
 * work packages add new migrations instead of editing this one.
 */
class CreateTestRooms extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'capacity'   => ['type' => 'SMALLINT', 'unsigned' => true],
            'location'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'building/floor, shown to applicants'],
            'sort_order' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('test_rooms');
    }

    public function down(): void
    {
        $this->forge->dropTable('test_rooms', true);
    }
}
