<?php

declare(strict_types=1);

namespace Modules\Tes\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Test sessions: one room at one date and time with a quota (FR-TEST-01).
 * wave_id null = open to applicants of every wave; a wave id = tests per wave.
 *
 * Phase 3 shared schema (docs/phase-3-plan.md). Owned by the coordinator.
 */
class CreateTestSessions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'academic_year' => ['type' => 'VARCHAR', 'constraint' => 9],
            'wave_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'null = every wave'],
            'test_date'     => ['type' => 'DATE'],
            'starts_at'     => ['type' => 'TIME'],
            'ends_at'       => ['type' => 'TIME'],
            'room_id'       => ['type' => 'INT', 'unsigned' => true],
            'quota'         => ['type' => 'SMALLINT', 'unsigned' => true, 'comment' => 'seats; defaults to the room capacity'],
            'label'         => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'comment' => 'e.g. "Sesi 1"'],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '0 = no new allocations'],
            'notes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['academic_year', 'test_date', 'starts_at']);
        $this->forge->addKey('room_id');
        $this->forge->addForeignKey('room_id', 'test_rooms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('wave_id', 'waves', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('test_sessions');
    }

    public function down(): void
    {
        $this->forge->dropTable('test_sessions', true);
    }
}
