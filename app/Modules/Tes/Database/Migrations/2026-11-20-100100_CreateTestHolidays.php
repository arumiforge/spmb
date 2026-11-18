<?php

declare(strict_types=1);

namespace Modules\Tes\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Days without tests (holidays, school events). The session generator and
 * the allocation skip them (FR-TEST-01).
 *
 * Phase 3 shared schema (docs/phase-3-plan.md). Owned by the coordinator.
 */
class CreateTestHolidays extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'date'       => ['type' => 'DATE'],
            'note'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'e.g. "Idul Adha"'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('date');
        $this->forge->createTable('test_holidays');
    }

    public function down(): void
    {
        $this->forge->dropTable('test_holidays', true);
    }
}
