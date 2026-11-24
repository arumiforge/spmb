<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Pendaftaran\Database\Migrations\AddHasGuardianToApplicants;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Services\ApplicantService;
use Tests\Support\Pendaftaran\PendaftaranFixtures;

/**
 * "Ada wali?" first, guardian fields after: cleaning, validation and the
 * has_guardian backfill of existing applicants.
 *
 * @internal
 */
final class PendaftaranGuardianTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use PendaftaranFixtures;

    private const ALL = [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE];

    protected $namespace;
    protected $refresh = true;

    protected function tearDown(): void
    {
        $this->forgetPendaftaranSettings();
        parent::tearDown();
    }

    public function testNoGuardianClearsAndSkipsTheGuardianFields(): void
    {
        $form = new ApplicantForm(['guardian_name', 'guardian_relation'], [], []);
        $data = ApplicantForm::clean(['has_guardian' => '0', 'guardian_name' => 'Pak Lik', 'guardian_relation' => 'Paman', 'father_name' => 'Budi'], self::ALL);

        $this->assertSame('0', $data['has_guardian']);
        $this->assertNull($data['guardian_name']);
        $this->assertNull($data['guardian_relation']);
        $this->assertSame('Budi', $data['father_name']);

        $fields = ApplicantForm::fieldsToCheck(self::ALL, $data);
        $this->assertSame([], array_intersect(ApplicantForm::GUARDIAN, $fields));
        $this->assertArrayNotHasKey('guardian_name', $form->rules($fields, $data));
        $this->assertArrayNotHasKey('guardian_relation', $form->extraErrors($data, $fields));
    }

    public function testWithGuardianTheRequiredGuardianFieldsAreEnforced(): void
    {
        $form   = new ApplicantForm(['guardian_relation'], [], []);
        $data   = ApplicantForm::clean(['has_guardian' => '1', 'guardian_name' => '', 'guardian_relation' => ''], self::ALL);
        $fields = ApplicantForm::fieldsToCheck(self::ALL, $data);
        $rules  = $form->rules($fields, $data);

        $this->assertSame('1', $data['has_guardian']);
        // Required by the school's setting, and the name once "Ada wali" is chosen.
        $this->assertStringStartsWith('required|', $rules['guardian_relation']['rules']);
        $this->assertStringStartsWith('required|', $rules['guardian_name']['rules']);
        // Optional guardian fields stay optional.
        $this->assertStringStartsWith('permit_empty|', $rules['guardian_income']['rules']);

        // Without "Ada wali" a required guardian field is not asked at all.
        $none = ApplicantForm::clean(['has_guardian' => '0'], self::ALL);
        $this->assertArrayNotHasKey('guardian_relation', $form->rules(ApplicantForm::fieldsToCheck(self::ALL, $none), $none));
    }

    public function testChoiceIsInferredOnlyWhenTheGuardianFieldsAreOnTheForm(): void
    {
        $this->assertSame('1', ApplicantForm::clean(['guardian_name' => 'Nenek Ijah'], self::ALL)['has_guardian']);
        $this->assertSame('0', ApplicantForm::clean(['father_name' => 'Budi'], self::ALL)['has_guardian']);
        $this->assertArrayNotHasKey('has_guardian', ApplicantForm::clean(['address' => 'Jl. A'], ApplicantForm::SELF_EDITABLE));

        $this->assertSame('sectionParents', ApplicantForm::sectionOf('has_guardian'));
        $this->assertSame('sectionSchool', ApplicantForm::sectionOf('previous_school_id'));
        $this->assertNull(ApplicantForm::sectionOf('agree'));
        $this->assertSame(['sectionStudent', 'sectionAddress', 'sectionParents', 'sectionSchool'], array_keys(ApplicantForm::sections(self::ALL)));
        $this->assertSame(['sectionStudent', 'sectionAddress', 'sectionParents'], array_keys(ApplicantForm::sections(ApplicantForm::SELF_EDITABLE)));
    }

    public function testServiceStoresTheAnswerAndUpdateClearsTheGuardian(): void
    {
        $this->makeWave();
        $data = ApplicantForm::clean($this->applicantInput(['has_guardian' => '1', 'guardian_name' => 'Khadijah', 'guardian_relation' => 'Nenek']), self::ALL);
        $row  = (new ApplicantService())->register($data, 'offline')['applicant'];

        $this->assertSame('1', (string) $row['has_guardian']);
        $this->assertSame('Khadijah', $row['guardian_name']);

        $changes = ApplicantForm::clean(['has_guardian' => '0', 'guardian_name' => 'Khadijah'], ApplicantForm::GUARDIAN);
        $updated = (new ApplicantService())->update((int) $row['id'], $changes);

        $this->assertSame('0', (string) $updated['has_guardian']);
        $this->assertNull($updated['guardian_name']);
        $this->assertNull($updated['guardian_relation']);
    }

    public function testMigrationBackfillsExistingApplicants(): void
    {
        $this->makeWave();
        $service = new ApplicantService();
        $with    = $service->register(ApplicantForm::clean($this->applicantInput(['guardian_relation' => 'Paman']), self::ALL), 'offline')['applicant'];
        $without = $service->register(ApplicantForm::clean($this->applicantInput(['nisn' => '0987654321', 'nik' => '3319011705150009', 'full_name' => 'Anak Lain']), self::ALL), 'offline')['applicant'];

        $migration = new AddHasGuardianToApplicants();
        $migration->down();
        $this->assertFalse(db_connect()->fieldExists('has_guardian', 'applicants'));
        $migration->up();

        $model = model(ApplicantModel::class);
        $this->assertSame('1', (string) $model->find((int) $with['id'])['has_guardian']);
        $this->assertSame('0', (string) $model->find((int) $without['id'])['has_guardian']);
    }
}
