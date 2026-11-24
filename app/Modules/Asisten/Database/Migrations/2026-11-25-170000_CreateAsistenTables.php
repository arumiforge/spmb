<?php

declare(strict_types=1);

namespace Modules\Asisten\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * "Tanya Asisten" (D-110).
 *
 * - asisten_providers  OpenAI-compatible AI connections (Super Admin). The API key is stored
 *                      encrypted with the app encryption key; key_hint keeps only its last 4 characters.
 *                      used_seq gives the round robin order, rested_until skips a failing connection.
 * - asisten_questions  question log for the school: the question (long numbers and e-mail addresses
 *                      masked), language and how it was answered. No IP, no visitor hash.
 * - asisten_usage      AI answers per day: one row per anonymous daily visitor key (HMAC with a key
 *                      that changes every day) and one row "_site" for the whole website. Rows older than
 *                      yesterday are deleted.
 */
class CreateAsistenTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 80],
            'base_url'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'model'           => ['type' => 'VARCHAR', 'constraint' => 120],
            'api_key'         => ['type' => 'TEXT', 'null' => true],
            'key_hint'        => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
            'timeout_seconds' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 20],
            'max_tokens'      => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 400],
            'temperature'     => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => '0.30'],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'      => ['type' => 'INT', 'default' => 0],
            'used_seq'        => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'last_used_at'    => ['type' => 'DATETIME', 'null' => true],
            'rested_until'    => ['type' => 'DATETIME', 'null' => true],
            'last_error'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'last_error_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('asisten_providers', true);

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'question'         => ['type' => 'VARCHAR', 'constraint' => 500],
            'question_key'     => ['type' => 'VARCHAR', 'constraint' => 191],
            'locale'           => ['type' => 'VARCHAR', 'constraint' => 5, 'default' => 'id'],
            'answered_by'      => ['type' => 'VARCHAR', 'constraint' => 16],
            'site_question_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'choice_label'     => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'provider_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'provider_name'    => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'tokens_in'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tokens_out'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('created_at');
        $this->forge->addKey(['answered_by', 'question_key']);
        $this->forge->createTable('asisten_questions', true);

        $this->forge->addField([
            'date'     => ['type' => 'DATE'],
            'visitor'  => ['type' => 'VARCHAR', 'constraint' => 32],
            'ai_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey(['date', 'visitor']);
        $this->forge->createTable('asisten_usage', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('asisten_usage', true);
        $this->forge->dropTable('asisten_questions', true);
        $this->forge->dropTable('asisten_providers', true);
    }
}
