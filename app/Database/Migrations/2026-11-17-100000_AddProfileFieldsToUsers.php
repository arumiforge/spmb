<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds display name and phone to Shield's users table.
 */
class AddProfileFieldsToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'username',
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'full_name',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', ['full_name', 'phone']);
    }
}
