<?php

declare(strict_types=1);

namespace Modules\Kartu\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Random, revocable QR tokens for registration cards (FR-CARD-02). Never NIK/NISN or sequential ids.
 *
 * Phase 2 shared schema (docs/phase-2-plan.md). Owned by the coordinator:
 * work packages add new migrations instead of editing this one.
 */
class CreateCardTokens extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'applicant_id' => ['type' => 'INT', 'unsigned' => true],
            'token'        => ['type' => 'VARCHAR', 'constraint' => 64, 'comment' => '32 random bytes, base64url'],
            'created_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'revoked_at'   => ['type' => 'DATETIME', 'null' => true],
            'last_seen_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token');
        $this->forge->addKey('applicant_id');
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('card_tokens');
    }

    public function down(): void
    {
        $this->forge->dropTable('card_tokens', true);
    }
}
