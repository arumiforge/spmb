<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * Applicants (table applicants). Owner: work package B (Pendaftaran).
 * Every write to applicant data goes through this model so it is audited (D-015).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class ApplicantModel extends Model
{
    use Auditable;

    protected $table          = 'applicants';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'registration_number', 'academic_year', 'wave_id', 'method', 'status', 'document_status',
        'full_name', 'name_key', 'gender', 'birth_place', 'birth_date', 'nik', 'nisn', 'kk_number', 'religion',
        'address', 'village', 'district', 'regency', 'province', 'phone',
        'father_name', 'father_occupation', 'mother_name', 'mother_occupation', 'guardian_name', 'guardian_relation',
        'parent_phone', 'parent_email', 'previous_school_id', 'previous_school_name', 'graduation_year',
        'possible_duplicate', 'access_token_hash', 'notes', 'created_by', 'registered_at',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }

    /**
     * The applicant with this registration number and date of birth (public
     * status check, D-008), or null. Case and spaces in the number are ignored.
     *
     * @return array<string, mixed>|null
     */
    public function findByNumberAndBirthDate(string $number, string $date): ?array
    {
        $number = strtoupper(preg_replace('/\s+/', '', $number) ?? '');

        if ($number === '' || preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $date) !== 1) {
            return null;
        }

        return $this->where('registration_number', $number)->where('birth_date', $date)->first();
    }

    /**
     * The applicant whose private link carries $token (stored as SHA-256, D-034), or null.
     *
     * @return array<string, mixed>|null
     */
    public function findByAccessToken(string $token): ?array
    {
        if (preg_match('/\A[A-Za-z0-9_-]{20,100}\z/', $token) !== 1) {
            return null;
        }

        return $this->where('access_token_hash', hash('sha256', $token))->first();
    }

    /**
     * NIK for lists: first 4 and last 4 digits only ("3319••••••••0001").
     */
    public static function maskNik(?string $nik): string
    {
        $nik = (string) $nik;

        if ($nik === '') {
            return '-';
        }

        return strlen($nik) <= 8 ? str_repeat('•', strlen($nik)) : substr($nik, 0, 4) . str_repeat('•', strlen($nik) - 8) . substr($nik, -4);
    }
}
