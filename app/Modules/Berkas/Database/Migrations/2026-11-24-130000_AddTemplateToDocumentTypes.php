<?php

declare(strict_types=1);

namespace Modules\Berkas\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Example file ("Unduh contoh") per document type, e.g. a letter template the
 * applicant prints and takes to the primary school. The school switches it
 * on and off per type. Files live in writable/uploads/berkas-contoh/.
 */
class AddTemplateToDocumentTypes extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('document_types', [
            'template_path'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'extensions', 'comment' => 'stored file name in writable/uploads/berkas-contoh'],
            'template_name'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'template_path', 'comment' => 'file name offered on download'],
            'template_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'template_name'],
            'template_label'   => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'template_enabled', 'comment' => 'link text; empty = "Unduh contoh"'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('document_types', ['template_path', 'template_name', 'template_enabled', 'template_label']);
    }
}
