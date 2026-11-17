<?php

declare(strict_types=1);

namespace Modules\Berkas\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Configurable list of documents applicants hand in (FR-DOC-01).
 *
 * Phase 2 shared schema (docs/phase-2-plan.md). Owned by the coordinator:
 * work packages add new migrations instead of editing this one.
 */
class CreateDocumentTypes extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'        => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'may_follow'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => 'may be handed in later (menyusul)'],
            'max_size_kb' => ['type' => 'INT', 'unsigned' => true, 'default' => 2048],
            'extensions'  => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'jpg,jpeg,png,pdf'],
            'sort_order'  => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('document_types');
    }

    public function down(): void
    {
        $this->forge->dropTable('document_types', true);
    }
}
