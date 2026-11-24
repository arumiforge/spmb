<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The answer to "Ada wali?" on the registration form: 1 = the applicant has a
 * guardian, 0 = no guardian, null = not asked (guardian fields not on the form).
 * Existing rows: 1 when any guardian field is filled in, otherwise 0.
 */
class AddHasGuardianToApplicants extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('applicants', [
            'has_guardian' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'after' => 'mother_income'],
        ]);

        $filled = [];

        foreach (['guardian_name', 'guardian_relation', 'guardian_occupation', 'guardian_income'] as $field) {
            $column   = $this->db->escapeIdentifiers($field);
            $filled[] = "({$column} IS NOT NULL AND TRIM({$column}) <> '')";
        }

        $this->db->query(
            'UPDATE ' . $this->db->prefixTable('applicants')
            . ' SET ' . $this->db->escapeIdentifiers('has_guardian') . ' = CASE WHEN ' . implode(' OR ', $filled) . ' THEN 1 ELSE 0 END',
        );
    }

    public function down(): void
    {
        $this->forge->dropColumn('applicants', 'has_guardian');
    }
}
