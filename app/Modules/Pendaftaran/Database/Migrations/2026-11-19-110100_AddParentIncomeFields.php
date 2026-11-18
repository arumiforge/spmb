<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Parent/guardian income and guardian occupation (D-048). Optional fields,
 * filled from the dropdown lists in setting('Pendaftaran.occupationOptions')
 * and setting('Pendaftaran.incomeOptions').
 */
class AddParentIncomeFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('applicants', [
            'father_income'       => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'father_occupation'],
            'mother_income'       => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'mother_occupation'],
            'guardian_occupation' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'guardian_relation'],
            'guardian_income'     => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'guardian_occupation'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('applicants', ['father_income', 'mother_income', 'guardian_occupation', 'guardian_income']);
    }
}
