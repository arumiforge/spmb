<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Optional English and Arabic names and descriptions of registration waves (D-109).
 * Empty means the Indonesian text shows.
 */
class AddTranslationsToWaves extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('waves', [
            'name_en'        => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'name'],
            'name_ar'        => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'name_en'],
            'description_en' => ['type' => 'TEXT', 'null' => true, 'after' => 'description'],
            'description_ar' => ['type' => 'TEXT', 'null' => true, 'after' => 'description_en'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('waves', ['name_en', 'name_ar', 'description_en', 'description_ar']);
    }
}
