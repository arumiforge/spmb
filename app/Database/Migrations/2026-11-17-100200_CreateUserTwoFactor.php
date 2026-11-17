<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Optional TOTP two-factor authentication (Google Authenticator) per user.
 * The secret is stored encrypted; recovery codes are stored as hashes.
 */
class CreateUserTwoFactor extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'secret' => [
                'type' => 'TEXT',
            ],
            'recovery_codes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'confirmed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_used_timestep' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_two_factor');
    }

    public function down(): void
    {
        $this->forge->dropTable('user_two_factor', true);
    }
}
