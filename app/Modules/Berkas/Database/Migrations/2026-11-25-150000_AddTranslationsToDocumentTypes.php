<?php

declare(strict_types=1);

namespace Modules\Berkas\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Optional English and Arabic names, descriptions and example-link texts of document types (D-109).
 * Empty means the Indonesian text shows.
 */
class AddTranslationsToDocumentTypes extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('document_types', [
            'name_en'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'description'],
            'name_ar'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'name_en'],
            'description_en'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'name_ar'],
            'description_ar'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'description_en'],
            'template_label_en' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'template_label'],
            'template_label_ar' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'template_label_en'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('document_types', ['name_en', 'name_ar', 'description_en', 'description_ar', 'template_label_en', 'template_label_ar']);
    }
}
