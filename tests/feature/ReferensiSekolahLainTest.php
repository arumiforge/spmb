<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Pendaftaran\Models\ApplicantModel;
use Tests\Support\Referensi\ReferensiFixtures;

/**
 * Mapping school names typed by applicants (/admin/referensi/sekolah-lain).
 *
 * @internal
 */
final class ReferensiSekolahLainTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use ReferensiFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetSignedInUser();
    }

    public function testListGroupsTypedNames(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');
        $id    = $this->makeSchool(['name' => 'SD NEGERI 1 JATI KULON']);
        $this->makeApplicant(['previous_school_name' => 'SD 1 Jati Kulon']);
        $this->makeApplicant(['previous_school_name' => 'SD 1 Jati Kulon ']);
        $this->makeApplicant(['previous_school_name' => 'SD Swasta Pati']);
        $this->makeApplicant(['previous_school_id' => $id]);
        $deleted = $this->makeApplicant(['previous_school_name' => 'SD Terhapus']);
        model(ApplicantModel::class, false)->delete($deleted);

        $result = $this->actingAs($admin)->get('admin/referensi/sekolah-lain');

        $result->assertOK();
        $result->assertSee('SD 1 Jati Kulon');
        $result->assertSee(lang('Referensi.othersCount', [2]));
        $result->assertSee('SD Swasta Pati');
        $result->assertDontSee('SD Terhapus');

        $this->actingAs($admin)->get('admin/referensi/sekolah')->assertSee(lang('Referensi.othersWaiting', [3]));
    }

    public function testEmptyState(): void
    {
        $this->actingAs($this->makeStaff('admin1', 'admin'))->get('admin/referensi/sekolah-lain')->assertSee(lang('Referensi.othersEmptyTitle'));
    }

    public function testMapPageSuggestsSchools(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');
        $this->makeSchool(['name' => 'SD NEGERI 1 JATI KULON', 'district' => 'Jati']);
        $this->makeApplicant(['previous_school_name' => 'SDN 1 Jati Kulon']);

        $result = $this->actingAs($admin)->get('admin/referensi/sekolah-lain/petakan?nama=' . rawurlencode('SDN 1 Jati Kulon'));
        $result->assertOK();
        $result->assertSee('SD NEGERI 1 JATI KULON — Kec. Jati, Kab. Kudus');
        $result->assertSee(lang('Referensi.mapSubmit'));

        $none = $this->actingAs($admin)->get('admin/referensi/sekolah-lain/petakan?nama=Tidak+Ada');
        $none->assertRedirectTo(site_url('admin/referensi/sekolah-lain'));
    }

    public function testAssignMapsEveryApplicantWithThatName(): void
    {
        $admin  = $this->makeStaff('admin1', 'admin');
        $school = $this->makeSchool(['name' => 'SD NEGERI 1 JATI KULON']);
        $a      = $this->makeApplicant(['previous_school_name' => 'SD 1 Jati Kulon']);
        $b      = $this->makeApplicant(['previous_school_name' => 'SD 1 Jati Kulon']);
        $other  = $this->makeApplicant(['previous_school_name' => 'SD Lain']);

        $result = $this->actingAs($admin)->post('admin/referensi/sekolah-lain/petakan', ['nama' => 'SD 1 Jati Kulon', 'school_id' => $school]);

        $result->assertRedirectTo(site_url('admin/referensi/sekolah-lain'));
        $result->assertSessionHas('message', lang('Referensi.mapped', [2, 'SD NEGERI 1 JATI KULON']));
        $this->seeInDatabase('applicants', ['id' => $a, 'previous_school_id' => $school]);
        $this->seeInDatabase('applicants', ['id' => $b, 'previous_school_id' => $school]);
        $this->seeInDatabase('applicants', ['id' => $other, 'previous_school_id' => null]);
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'applicants', 'subject_id' => $a]);
        $this->seeInDatabase('audit_logs', ['event' => 'school_mapped', 'subject_type' => 'ref_sekolah', 'subject_id' => $school]);
    }

    public function testAssignNeedsAChoice(): void
    {
        $this->makeApplicant(['previous_school_name' => 'SD Lain']);

        $result = $this->actingAs($this->makeStaff('admin1', 'admin'))->post('admin/referensi/sekolah-lain/petakan', ['nama' => 'SD Lain']);

        $result->assertSessionHas('error', lang('Referensi.mapChooseFirst'));
        $this->dontSeeInDatabase('audit_logs', ['event' => 'school_mapped']);
    }

    public function testAddAndAssign(): void
    {
        $id = $this->makeApplicant(['previous_school_name' => 'MI Al Hikmah Pati']);

        $result = $this->actingAs($this->makeStaff('admin1', 'admin'))->post('admin/referensi/sekolah-lain/tambah', [
            'nama' => 'MI Al Hikmah Pati', 'name' => 'MI Al Hikmah', 'level' => 'MI', 'ownership' => 'swasta', 'district' => 'Juwana', 'regency' => 'Kab. Pati', 'province' => 'Jawa Tengah',
        ]);

        $result->assertRedirectTo(site_url('admin/referensi/sekolah-lain'));
        $school = db_connect()->table('ref_sekolah')->where('name', 'MI Al Hikmah')->get()->getRowArray();
        $this->assertSame('manual', $school['source']);
        $this->seeInDatabase('applicants', ['id' => $id, 'previous_school_id' => $school['id']]);
    }

    public function testAddAndAssignValidates(): void
    {
        $this->makeApplicant(['previous_school_name' => 'MI Al Hikmah Pati']);

        $this->actingAs($this->makeStaff('admin1', 'admin'))->post('admin/referensi/sekolah-lain/tambah', ['nama' => 'MI Al Hikmah Pati', 'name' => '', 'level' => 'MI']);

        $this->assertArrayHasKey('name', session('errors'));
        $this->seeInDatabase('applicants', ['previous_school_id' => null]);
    }
}
