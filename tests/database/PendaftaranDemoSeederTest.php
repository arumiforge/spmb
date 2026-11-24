<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Berkas\Database\Seeds\InstallSeeder as BerkasInstallSeeder;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Pendaftaran\Database\Seeds\DemoSeeder;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Tests\Support\Pendaftaran\PendaftaranFixtures;

/**
 * Demo data for development (spmb:install --demo) is complete and idempotent.
 *
 * @internal
 */
final class PendaftaranDemoSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use PendaftaranFixtures;

    protected $namespace;
    protected $refresh = true;

    public function testSeedsWavesAndApplicantsOnce(): void
    {
        $seeder = new DemoSeeder(config('Database'));
        $seeder->run();

        $this->assertCount(3, model(WaveModel::class)->forYear('2027/2028'));
        $this->assertSame('Gelombang 1', model(WaveModel::class)->current()['name']);
        $this->assertSame(31, model(ApplicantModel::class)->countAllResults());
        $this->assertGreaterThanOrEqual(2, model(ApplicantModel::class)->where('possible_duplicate', 1)->countAllResults());
        $this->assertGreaterThan(0, model(ApplicantModel::class)->where('method', 'online')->countAllResults());
        $this->assertGreaterThan(0, model(ApplicantModel::class)->where('method', 'offline')->countAllResults());
        $this->assertSame(0, model(ApplicantModel::class)->where('registered_at >', date('Y-m-d H:i:s'))->countAllResults());

        $seeder->run();
        $this->assertCount(3, model(WaveModel::class)->forYear('2027/2028'));
        $this->assertSame(31, model(ApplicantModel::class)->countAllResults());
        $this->assertFalse($seeder->example['created']);
    }

    public function testCreatesOneCompleteExampleApplicant(): void
    {
        // A school from the SD/MI list and a staff member to check the documents.
        db_connect()->table('ref_sekolah')->insert(['npsn' => '99020001', 'name' => 'MI NU MIFTAHUL HUDA', 'level' => 'MI', 'regency' => 'Kab. Kudus', 'is_active' => 1]);
        $schoolId = (int) db_connect()->insertID();
        (new BerkasInstallSeeder(config('Database')))->run();
        $this->makeUser('panitia');

        $seeder = new DemoSeeder(config('Database'));
        $seeder->run();

        $row = model(ApplicantModel::class)->where('full_name', DemoSeeder::EXAMPLE_NAME)->first();
        $this->assertNotNull($row);
        $this->assertTrue($seeder->example['created']);
        $this->assertNotNull($seeder->example['token']);
        $this->assertSame($row['id'], $seeder->example['applicant']['id']);

        // Every form field is filled in (the school as a list entry), plus the guardian answer and a staff note.
        foreach ([...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE] as $field) {
            if ($field === 'previous_school_name') {
                continue;
            }

            $this->assertNotEmpty($row[$field], $field);
        }

        $this->assertSame($schoolId, (int) $row['previous_school_id']);
        $this->assertSame('1', (string) $row['has_guardian']);
        $this->assertNotEmpty($row['notes']);
        $this->assertSame('verified', $row['status']);

        // The record passes the form's own checks, so staff can edit it as it is.
        $form = new ApplicantForm();
        $data = ApplicantForm::clean($row, [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE]);
        $this->assertSame([], $form->extraErrors($data, [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE]));
        $validation = service('validation', null, false)->setRules($form->rules([...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE], $data));
        $this->assertTrue($validation->run($data), implode(' ', $validation->getErrors()));

        // Every document uploaded and valid.
        $documents = model(ApplicantDocumentModel::class, false)->where('applicant_id', $row['id'])->findAll();
        $this->assertCount(count(BerkasInstallSeeder::DEFAULTS), $documents);
        $this->assertSame(['valid'], array_values(array_unique(array_column($documents, 'status'))));
        $this->assertSame('complete', model(ApplicantModel::class)->find((int) $row['id'])['document_status']);

        foreach ($documents as $document) {
            @unlink(WRITEPATH . 'uploads/berkas/' . $document['file_path']);
        }

        // Running again adds nothing.
        $again = new DemoSeeder(config('Database'));
        $again->run();
        $this->assertSame(1, model(ApplicantModel::class)->where('full_name', DemoSeeder::EXAMPLE_NAME)->countAllResults());
        $this->assertCount(count(BerkasInstallSeeder::DEFAULTS), model(ApplicantDocumentModel::class, false)->where('applicant_id', $row['id'])->findAll());
    }
}
