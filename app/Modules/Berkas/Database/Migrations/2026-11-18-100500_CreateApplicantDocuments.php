<?php

declare(strict_types=1);

namespace Modules\Berkas\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Uploaded files per applicant and document type, stored under writable/uploads/berkas/ (FR-DOC-02..04).
 *
 * Phase 2 shared schema (docs/phase-2-plan.md). Owned by the coordinator:
 * work packages add new migrations instead of editing this one.
 */
class CreateApplicantDocuments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'applicant_id'     => ['type' => 'INT', 'unsigned' => true],
            'document_type_id' => ['type' => 'INT', 'unsigned' => true],
            'file_path'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'relative to writable/uploads/berkas; null when to_follow'],
            'original_name'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'mime'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'size_bytes'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending', 'comment' => 'pending | valid | invalid | to_follow'],
            'note'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'uploaded_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'staff user; null = applicant'],
            'verified_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['applicant_id', 'document_type_id']);
        $this->forge->addKey('status');
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('document_type_id', 'document_types', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('verified_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('applicant_documents');
    }

    public function down(): void
    {
        $this->forge->dropTable('applicant_documents', true);
    }
}
