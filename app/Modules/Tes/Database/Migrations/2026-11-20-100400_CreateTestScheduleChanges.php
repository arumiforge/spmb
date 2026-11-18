<?php

declare(strict_types=1);

namespace Modules\Tes\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Log of every schedule change (FR-TEST-04): who, when, from → to, reason.
 * kind: allocated (automatic) | assigned (by staff) | moved | removed.
 * Rows are never updated or deleted.
 *
 * Phase 3 shared schema (docs/phase-3-plan.md). Owned by the coordinator.
 */
class CreateTestScheduleChanges extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'applicant_id'    => ['type' => 'INT', 'unsigned' => true],
            'from_session_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'to_session_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'kind'            => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => 'allocated | assigned | moved | removed'],
            'reason'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'changed_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'null = system'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['applicant_id', 'id']);
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('changed_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('test_schedule_changes');
    }

    public function down(): void
    {
        $this->forge->dropTable('test_schedule_changes', true);
    }
}
