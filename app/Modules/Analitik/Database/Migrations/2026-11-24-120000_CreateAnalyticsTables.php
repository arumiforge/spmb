<?php

declare(strict_types=1);

namespace Modules\Analitik\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Compact visitor statistics (D-009, D-053): daily totals only, nothing personal.
 *
 * - analytics_daily        one row per day: page views and unique visitors.
 * - analytics_daily_pages  views per normalised path per day (no query string);
 *                          pruned after setting('Analitik.retentionDays').
 * - analytics_visitors     one-way daily hash (HMAC with a key that changes every
 *                          day) so a visitor is counted once per day; rows older
 *                          than yesterday are deleted. No IP or user agent is stored.
 */
class CreateAnalyticsTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'date'       => ['type' => 'DATE'],
            'page_views' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'visitors'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('date');
        $this->forge->createTable('analytics_daily', true);

        $this->forge->addField([
            'id'    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'date'  => ['type' => 'DATE'],
            'path'  => ['type' => 'VARCHAR', 'constraint' => 191],
            'views' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['date', 'path']);
        $this->forge->createTable('analytics_daily_pages', true);

        $this->forge->addField([
            'date' => ['type' => 'DATE'],
            'hash' => ['type' => 'CHAR', 'constraint' => 32],
        ]);
        $this->forge->addPrimaryKey(['date', 'hash']);
        $this->forge->createTable('analytics_visitors', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('analytics_visitors', true);
        $this->forge->dropTable('analytics_daily_pages', true);
        $this->forge->dropTable('analytics_daily', true);
    }
}
