<?php

declare(strict_types=1);

namespace Modules\Statistik\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Index for the Statistik page (D-111): every aggregate filters applicants by
 * academic year and status (deleted rows excluded), so (academic_year, status,
 * deleted_at) lets MySQL read only the rows of one year.
 */
class AddYearStatusIndexToApplicants extends Migration
{
    private const NAME = 'applicants_year_status';

    public function up(): void
    {
        if (! array_key_exists(self::NAME, $this->db->getIndexData('applicants'))) {
            $this->forge->addKey(['academic_year', 'status', 'deleted_at'], false, false, self::NAME);
            $this->forge->processIndexes('applicants');
        }
    }

    public function down(): void
    {
        if (array_key_exists(self::NAME, $this->db->getIndexData('applicants'))) {
            $this->forge->dropKey('applicants', self::NAME, false);
        }
    }
}
