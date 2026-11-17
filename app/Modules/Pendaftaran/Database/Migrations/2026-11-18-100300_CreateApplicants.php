<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Applicants (calon murid). Minimum personal data (UU PDP); every change goes through ApplicantModel (Auditable).
 *
 * Phase 2 shared schema (docs/phase-2-plan.md). Owned by the coordinator:
 * work packages add new migrations instead of editing this one.
 */
class CreateApplicants extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'registration_number'  => ['type' => 'VARCHAR', 'constraint' => 30],
            'academic_year'        => ['type' => 'VARCHAR', 'constraint' => 9],
            'wave_id'              => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'method'               => ['type' => 'VARCHAR', 'constraint' => 10, 'comment' => 'online | offline'],
            'status'               => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'registered', 'comment' => 'registered | verified | cancelled'],
            'document_status'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'incomplete', 'comment' => 'incomplete | pending | to_follow | complete'],
            'full_name'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'name_key'             => ['type' => 'VARCHAR', 'constraint' => 100, 'comment' => 'normalised name for duplicate checks'],
            'gender'               => ['type' => 'CHAR', 'constraint' => 1, 'comment' => 'L | P'],
            'birth_place'          => ['type' => 'VARCHAR', 'constraint' => 60],
            'birth_date'           => ['type' => 'DATE'],
            'nik'                  => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],
            'nisn'                 => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'kk_number'            => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],
            'religion'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'address'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'village'              => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'district'             => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'regency'              => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'province'             => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'phone'                => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'father_name'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'father_occupation'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'mother_name'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'mother_occupation'    => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'guardian_name'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'guardian_relation'    => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'parent_phone'         => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => 'WhatsApp number of a parent/guardian'],
            'parent_email'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'previous_school_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'previous_school_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'comment' => 'free text when the school is not in ref_sekolah'],
            'graduation_year'      => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'possible_duplicate'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'access_token_hash'    => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'comment' => 'sha256 of the private link token (D-008)'],
            'notes'                => ['type' => 'TEXT', 'null' => true, 'comment' => 'staff-only notes'],
            'created_by'           => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'staff user for offline registration'],
            'registered_at'        => ['type' => 'DATETIME'],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('registration_number');
        $this->forge->addUniqueKey('access_token_hash');
        $this->forge->addKey('nik');
        $this->forge->addKey('nisn');
        $this->forge->addKey(['name_key', 'birth_date']);
        $this->forge->addKey(['academic_year', 'wave_id']);
        $this->forge->addKey('status');
        $this->forge->addKey('registered_at');
        $this->forge->addForeignKey('wave_id', 'waves', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('previous_school_id', 'ref_sekolah', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('applicants');
    }

    public function down(): void
    {
        $this->forge->dropTable('applicants', true);
    }
}
