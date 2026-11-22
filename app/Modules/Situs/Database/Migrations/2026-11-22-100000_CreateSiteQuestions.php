<?php

declare(strict_types=1);

namespace Modules\Situs\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Questions and answers on the public /tanya-jawab page (D-072), grouped by topic
 * and edited by Admin Sekolah. Answers are Markdown.
 */
class CreateSiteQuestions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'topic'        => ['type' => 'VARCHAR', 'constraint' => 80],
            'question'     => ['type' => 'VARCHAR', 'constraint' => 200],
            'answer'       => ['type' => 'TEXT'],
            'sort_order'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_published' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['is_published', 'sort_order']);
        $this->forge->createTable('site_questions');
    }

    public function down(): void
    {
        $this->forge->dropTable('site_questions', true);
    }
}
