<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Kartu\Models\CardTokenModel;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Referensi\Models\SchoolModel;

/**
 * The shared phase 2 tables and models fit together and are audited.
 *
 * @internal
 */
final class Phase2SchemaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;

    public function testSharedModelsLinkUpAndAreAudited(): void
    {
        $schoolId = model(SchoolModel::class)->insert(['npsn' => '20300001', 'name' => 'SD Negeri 1 Contoh', 'level' => 'SD', 'province' => 'Jawa Tengah', 'regency' => 'Kab. Kudus', 'source' => 'manual']);
        $waveId   = model(WaveModel::class)->insert(['academic_year' => '2027/2028', 'name' => 'Gelombang 1', 'starts_on' => '2027-01-04', 'ends_on' => '2027-02-27']);

        $applicantId = model(ApplicantModel::class)->insert([
            'registration_number' => 'PMB-2027-0001',
            'academic_year'       => '2027/2028',
            'wave_id'             => $waveId,
            'method'              => 'online',
            'full_name'           => 'Ahmad Fauzi',
            'name_key'            => 'ahmad fauzi',
            'gender'              => 'L',
            'birth_place'         => 'Kudus',
            'birth_date'          => '2015-05-17',
            'parent_phone'        => '081234567890',
            'previous_school_id'  => $schoolId,
            'access_token_hash'   => hash('sha256', 'rahasia'),
            'registered_at'       => '2027-01-05 08:00:00',
        ]);

        $typeId = model(DocumentTypeModel::class)->insert(['code' => 'kk', 'name' => 'Kartu Keluarga']);
        model(ApplicantDocumentModel::class)->insert(['applicant_id' => $applicantId, 'document_type_id' => $typeId, 'status' => 'to_follow']);
        model(CardTokenModel::class)->insert(['applicant_id' => $applicantId, 'token' => bin2hex(random_bytes(16))]);

        $applicant = model(ApplicantModel::class)->find($applicantId);
        $this->assertSame('registered', $applicant['status']);
        $this->assertSame('incomplete', $applicant['document_status']);
        $this->assertSame('0', (string) $applicant['possible_duplicate']);

        foreach (['ref_sekolah', 'waves', 'applicants', 'document_types', 'applicant_documents', 'card_tokens'] as $table) {
            $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => $table]);
        }

        $log = db_connect()->table('audit_logs')->where('subject_type', 'applicants')->get()->getRowArray();
        $this->assertStringNotContainsString(hash('sha256', 'rahasia'), (string) $log['new_values']);
    }

    public function testRegistrationNumberIsUnique(): void
    {
        $row = [
            'registration_number' => 'PMB-2027-0001',
            'academic_year'       => '2027/2028',
            'method'              => 'offline',
            'full_name'           => 'A',
            'name_key'            => 'a',
            'gender'              => 'P',
            'birth_place'         => 'Kudus',
            'birth_date'          => '2015-01-01',
            'parent_phone'        => '0812',
            'registered_at'       => '2027-01-05 08:00:00',
        ];
        model(ApplicantModel::class)->insert($row);

        $this->expectException(Throwable::class);
        db_connect()->table('applicants')->insert($row);
    }
}
