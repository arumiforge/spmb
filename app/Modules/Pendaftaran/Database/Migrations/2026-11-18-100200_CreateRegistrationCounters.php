<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One row per numbering scope (default: academic year). Incremented with SELECT ... FOR UPDATE inside a transaction; online and offline share it (FR-REG-03).
 *
 * Phase 2 shared schema (docs/phase-2-plan.md). Owned by the coordinator:
 * work packages add new migrations instead of editing this one.
 */
class CreateRegistrationCounters extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'scope'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'last_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('scope');
        $this->forge->createTable('registration_counters');
    }

    public function down(): void
    {
        $this->forge->dropTable('registration_counters', true);
    }
}
