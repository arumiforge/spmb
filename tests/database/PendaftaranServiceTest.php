<?php

declare(strict_types=1);

use CodeIgniter\Events\Events;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Pendaftaran\Exceptions\RegistrationClosed;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Pendaftaran\Services\ApplicantService;
use Modules\Pendaftaran\Services\DuplicateChecker;
use Tests\Support\Pendaftaran\PendaftaranFixtures;

/**
 * ApplicantService and DuplicateChecker (FR-REG-01…05, D-031, D-032, D-034).
 *
 * @internal
 */
final class PendaftaranServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use PendaftaranFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function tearDown(): void
    {
        $this->forgetPendaftaranSettings();
        parent::tearDown();
    }

    private function data(array $overrides = []): array
    {
        $fields = [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE];

        return ApplicantForm::clean($this->applicantInput($overrides), $fields);
    }

    public function testOnlineRegistrationGetsNumberWaveAndToken(): void
    {
        $waveId = $this->makeWave();
        $seen   = null;
        Events::on('applicant_registered', static function (array $applicant) use (&$seen): void {
            $seen = $applicant['registration_number'];
        });

        $result    = (new ApplicantService())->register($this->data(), 'online');
        $applicant = $result['applicant'];

        $this->assertSame('PMB-2027-0001', $applicant['registration_number']);
        $this->assertSame($waveId, (int) $applicant['wave_id']);
        $this->assertSame('online', $applicant['method']);
        $this->assertSame('registered', $applicant['status']);
        $this->assertSame('ahmad fauzi', $applicant['name_key']);
        $this->assertSame('081234567890', $applicant['parent_phone']);
        $this->assertNull($applicant['created_by']);
        $this->assertSame('0', (string) $applicant['possible_duplicate']);
        $this->assertSame(hash('sha256', $result['token']), $applicant['access_token_hash']);
        $this->assertSame(43, strlen($result['token']));
        $this->assertSame('PMB-2027-0001', $seen);
        $this->seeInDatabase('audit_logs', ['event' => 'applicant_registered', 'subject_id' => (string) $applicant['id']]);

        $this->assertSame((int) $applicant['id'], (int) model(ApplicantModel::class)->findByAccessToken($result['token'])['id']);
        $this->assertNull(model(ApplicantModel::class)->findByAccessToken('salah-' . $result['token']));
        $this->assertSame((int) $applicant['id'], (int) model(ApplicantModel::class)->findByNumberAndBirthDate(' pmb-2027-0001 ', $applicant['birth_date'])['id']);
        $this->assertNull(model(ApplicantModel::class)->findByNumberAndBirthDate('PMB-2027-0001', '2000-01-01'));
    }

    public function testOnlineIsClosedWithoutWaveSwitchOrRoom(): void
    {
        $service = new ApplicantService();

        foreach (['no_wave' => static fn () => null, 'switched_off' => function (): void {
            $this->makeWave(['quota' => 1, 'quota_mode' => 'close']);
            setting('Pendaftaran.onlineOpen', false);
        }, 'full' => function () use ($service): void {
            setting('Pendaftaran.onlineOpen', true);
            $service->register($this->data(['full_name' => 'Anak Pertama', 'nik' => '', 'nisn' => '1111111111']), 'online');
        }] as $reason => $prepare) {
            $prepare();

            try {
                $service->register($this->data(), 'online');
                $this->fail('Expected RegistrationClosed: ' . $reason);
            } catch (RegistrationClosed $e) {
                $this->assertSame($reason, $e->reason);
                $this->assertSame($reason, $service->onlineClosedReason());
            }
        }
    }

    public function testOfflineStaffMayChooseWaveOrNone(): void
    {
        $staff = $this->makeUser('panitia');
        $later = $this->makeWave(['name' => 'Gelombang 2', 'starts_on' => date('Y-m-d', strtotime('+30 days')), 'ends_on' => date('Y-m-d', strtotime('+40 days'))]);

        $none = (new ApplicantService())->register($this->data(), 'offline', (int) $staff->id);
        $this->assertNull($none['applicant']['wave_id']);
        $this->assertSame((int) $staff->id, (int) $none['applicant']['created_by']);

        $chosen = (new ApplicantService())->register($this->data(['nik' => '', 'nisn' => '2222222222', 'full_name' => 'Budi']) + ['wave_id' => $later, 'notes' => 'Datang bersama ibu'], 'offline', (int) $staff->id);
        $this->assertSame($later, (int) $chosen['applicant']['wave_id']);
        $this->assertSame('Datang bersama ibu', $chosen['applicant']['notes']);
        $this->assertSame('PMB-2027-0002', $chosen['applicant']['registration_number']);
    }

    public function testDuplicatesAreFlaggedNotBlocked(): void
    {
        $this->makeWave();
        $service = new ApplicantService();
        $first   = $service->register($this->data(), 'online');

        $byNik = $service->register($this->data(['nisn' => '9999999999', 'full_name' => 'Nama Lain']), 'online');
        $this->assertSame('1', (string) $byNik['applicant']['possible_duplicate']);
        $this->assertSame(['nik'], $byNik['duplicates'][0]['reasons']);
        $this->assertSame((int) $first['applicant']['id'], (int) $byNik['duplicates'][0]['id']);

        $byName = $service->register($this->data(['nik' => '', 'nisn' => '', 'full_name' => '  AHMAD   fauzi ']), 'online');
        $this->assertSame('1', (string) $byName['applicant']['possible_duplicate']);
        $this->assertContains('name', $byName['duplicates'][0]['reasons']);

        $other = $service->register($this->data(['nik' => '3319000000000009', 'nisn' => '8888888888', 'full_name' => 'Citra Lestari']), 'online');
        $this->assertSame('0', (string) $other['applicant']['possible_duplicate']);
    }

    public function testDuplicateChecker(): void
    {
        $this->makeWave();
        $first   = (new ApplicantService())->register($this->data(), 'online')['applicant'];
        $checker = new DuplicateChecker();

        $this->assertSame([], $checker->find(['nik' => '3319011705150001'], (int) $first['id']));
        $this->assertCount(1, $checker->find(['nisn' => '0123456789']));
        $this->assertCount(1, $checker->find(['full_name' => 'Ahmad Fauzi', 'birth_date' => $first['birth_date']]));
        $this->assertSame([], $checker->find(['full_name' => 'Ahmad Fauzi', 'birth_date' => '2010-01-01']));
        $this->assertSame([], $checker->find(['nik' => '3319011705150001', 'academic_year' => '2028/2029']));
        $this->assertSame([], $checker->find([]));

        model(ApplicantModel::class)->delete($first['id']);
        $this->assertSame([], $checker->find(['nik' => '3319011705150001']));
    }

    public function testNameKey(): void
    {
        $this->assertSame('siti aisyah', DuplicateChecker::nameKey("  Siti  'Aisyah "));
        $this->assertSame('muhammad rafi', DuplicateChecker::nameKey('Muhammad Rafi\''));
        $this->assertSame('andre', DuplicateChecker::nameKey('André'));
    }

    public function testUpdateStatusReviewAndPrivateLink(): void
    {
        $this->makeWave();
        $service = new ApplicantService();
        $a       = $service->register($this->data(), 'online');
        $b       = $service->register($this->data(['nik' => '3319000000000009', 'nisn' => '8888888888', 'full_name' => 'Citra']), 'online')['applicant'];
        $id      = (int) $b['id'];

        $updated = $service->update($id, ['full_name' => 'Citra Lestari', 'address' => 'Jl. Baru 2']);
        $this->assertSame('citra lestari', $updated['name_key']);
        $this->assertSame('0', (string) $updated['possible_duplicate']);

        $updated = $service->update($id, ['nik' => '3319011705150001']);
        $this->assertSame('1', (string) $updated['possible_duplicate']);

        $service->reviewDuplicate($id, 'Beda anak, NIK salah ketik');
        $this->seeInDatabase('audit_logs', ['event' => 'duplicate_reviewed', 'subject_id' => (string) $id]);
        $this->assertSame('0', (string) model(ApplicantModel::class)->find($id)['possible_duplicate']);

        $changed = $service->changeStatus($id, 'verified', 'Berkas sudah dicek');
        $this->assertSame('verified', $changed['status']);
        $this->seeInDatabase('audit_logs', ['event' => 'applicant_status_changed', 'subject_id' => (string) $id]);

        $token = $service->renewPrivateLink((int) $a['applicant']['id']);
        $this->assertNull(model(ApplicantModel::class)->findByAccessToken($a['token']));
        $this->assertNotNull(model(ApplicantModel::class)->findByAccessToken($token));
    }

    public function testFormModesFollowSettings(): void
    {
        setting('Pendaftaran.requiredFields', ['nik']);
        setting('Pendaftaran.hiddenFields', ['nisn', 'religion', 'full_name']);
        $form = new ApplicantForm();

        $this->assertSame('required', $form->mode('nik'));
        $this->assertSame('hidden', $form->mode('nisn'));
        $this->assertSame('required', $form->mode('full_name'));
        $this->assertSame('optional', $form->mode('kk_number'));
        $this->assertNotContains('religion', $form->visible());

        $data = ApplicantForm::clean(['nik' => '3319 0117 0515 0001', 'parent_phone' => '+62 812-3456-7890', 'gender' => 'p', 'x' => 'y'], ['nik', 'parent_phone', 'gender']);
        $this->assertSame(['nik' => '3319011705150001', 'parent_phone' => '+6281234567890', 'gender' => 'P'], $data);

        $errors = $form->extraErrors(['birth_date' => date('Y-m-d'), 'previous_school_name' => null], ['birth_date', 'previous_school_name', 'father_name', 'mother_name']);
        $this->assertArrayHasKey('birth_date', $errors);
        $this->assertArrayHasKey('previous_school_name', $errors);
        $this->assertArrayHasKey('father_name', $errors);
    }

    public function testQuotaModesPerWave(): void
    {
        $service = new ApplicantService();
        $waves   = model(WaveModel::class);

        // Information only: never closes.
        $id = $this->makeWave(['quota' => 1, 'quota_mode' => 'none']);
        $service->register($this->data(['full_name' => 'Anak Satu', 'nik' => '', 'nisn' => '1111111111']), 'online');
        $this->assertNull($service->onlineClosedReason());

        // A separate online limit: offline registrations do not count.
        $waves->update($id, ['quota_mode' => 'online_limit', 'online_quota' => 2]);
        $service->register($this->data(['full_name' => 'Anak Dua', 'nik' => '', 'nisn' => '2222222222']), 'offline');
        $this->assertNull($service->onlineClosedReason());
        $service->register($this->data(['full_name' => 'Anak Tiga', 'nik' => '', 'nisn' => '3333333333']), 'online');
        $this->assertSame('full', $service->onlineClosedReason());

        // Close at the quota.
        $waves->update($id, ['quota_mode' => 'close', 'quota' => 10]);
        $this->assertNull($service->onlineClosedReason());
    }

    public function testStaffOnlyFieldsAndDropdownLists(): void
    {
        $form = new ApplicantForm([], [], ['kk_number', 'father_income']);

        $this->assertSame('staff', $form->mode('father_income'));
        $this->assertFalse($form->shows('father_income'));
        $this->assertTrue($form->shows('father_income', true));
        $this->assertNotContains('kk_number', $form->visible());
        $this->assertContains('kk_number', $form->visible(true));
        $this->assertContains('Petani', ApplicantForm::options('mother_occupation'));
        $this->assertContains('Tidak berpenghasilan', ApplicantForm::options('guardian_income'));
        $this->assertNull(ApplicantForm::options('religion'));

        $fields = ['father_occupation', 'mother_income'];
        $this->assertSame([], $form->extraErrors(['father_occupation' => 'Petani', 'mother_income' => null], $fields));
        $this->assertSame(['father_occupation'], array_keys($form->extraErrors(['father_occupation' => 'Astronot'], $fields)));
    }
}
