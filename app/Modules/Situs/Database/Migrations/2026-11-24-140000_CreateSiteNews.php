<?php

declare(strict_types=1);

namespace Modules\Situs\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Short news items for the ticker under the public header ("Berita singkat").
 * Each item can link somewhere and can be limited to a date range.
 */
class CreateSiteNews extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'text'       => ['type' => 'VARCHAR', 'constraint' => 160],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
            'starts_on'  => ['type' => 'DATE', 'null' => true],
            'ends_on'    => ['type' => 'DATE', 'null' => true],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['is_active', 'sort_order']);
        $this->forge->createTable('site_news');
    }

    public function down(): void
    {
        $this->forge->dropTable('site_news', true);
    }
}
