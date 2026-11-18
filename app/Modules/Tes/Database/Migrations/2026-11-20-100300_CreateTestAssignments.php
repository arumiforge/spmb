<?php

declare(strict_types=1);

namespace Modules\Tes\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * An applicant's current test session and attendance (FR-TEST-02, FR-TEST-03).
 * One row per applicant; moves change session_id and are logged in
 * test_schedule_changes. status: pending (Belum tes) | present (Hadir) |
 * absent (Tidak hadir) | rescheduled (Dijadwalkan ulang, not tested yet).
 *
 * Phase 3 shared schema (docs/phase-3-plan.md). Owned by the coordinator.
 */
class CreateTestAssignments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'applicant_id' => ['type' => 'INT', 'unsigned' => true],
            'session_id'   => ['type' => 'INT', 'unsigned' => true],
            'seat_number'  => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'comment' => 'order within the session'],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending', 'comment' => 'pending | present | absent | rescheduled'],
            'attended_at'  => ['type' => 'DATETIME', 'null' => true],
            'recorded_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'staff who recorded attendance'],
            'assigned_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'null = automatic allocation'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('applicant_id');
        $this->forge->addKey(['session_id', 'status']);
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('session_id', 'test_sessions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('recorded_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('assigned_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('test_assignments');
    }

    public function down(): void
    {
        $this->forge->dropTable('test_assignments', true);
    }
}
