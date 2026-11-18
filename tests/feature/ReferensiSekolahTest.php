<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Referensi\Models\SchoolModel;
use Tests\Support\Referensi\ReferensiFixtures;

/**
 * Previous-school list (/admin/referensi/sekolah): permissions, search, add, edit, deactivate.
 *
 * @internal
 */
final class ReferensiSekolahTest extends CIUnitTestCase
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

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function adminRoutes(): array
    {
        return [
            ['get', 'admin/referensi/sekolah'],
            ['get', 'admin/referensi/sekolah/tambah'],
            ['post', 'admin/referensi/sekolah/tambah'],
            ['get', 'admin/referensi/sekolah/1/ubah'],
            ['post', 'admin/referensi/sekolah/1/ubah'],
            ['post', 'admin/referensi/sekolah/1/nonaktifkan'],
            ['post', 'admin/referensi/sekolah/1/aktifkan'],
            ['get', 'admin/referensi/sekolah/impor'],
            ['post', 'admin/referensi/sekolah/impor'],
            ['get', 'admin/referensi/sekolah/impor/contoh'],
            ['get', 'admin/referensi/sekolah-lain'],
            ['get', 'admin/referensi/sekolah-lain/petakan?nama=SD+Lain'],
            ['post', 'admin/referensi/sekolah-lain/petakan'],
            ['post', 'admin/referensi/sekolah-lain/tambah'],
        ];
    }

    public function testEveryAdminRouteNeedsReferensiKelola(): void
    {
        $this->makeSchool(['name' => 'SD Contoh']);
        $panitia = $this->makeStaff('panitia1', 'panitia');

        foreach (self::adminRoutes() as [$method, $uri]) {
            $this->actingAs($panitia)->{$method}($uri)->assertRedirectTo(site_url('admin/akses-ditolak'));
        }

        $this->assertSame(1, model(SchoolModel::class, false)->where('is_active', 1)->countAllResults());
    }

    public function testGuestIsSentToSignIn(): void
    {
        foreach (self::adminRoutes() as [$method, $uri]) {
            $this->{$method}($uri)->assertRedirectTo(site_url('masuk'));
        }
    }

    public function testAdminAndSuperAdminSeeTheList(): void
    {
        $this->makeSchool(['npsn' => '20319001', 'name' => 'SD NEGERI 1 JATI KULON', 'district' => 'Jati']);

        foreach (['admin', 'superadmin'] as $group) {
            $result = $this->actingAs($this->makeStaff('u-' . $group, $group))->get('admin/referensi/sekolah');
            $result->assertOK();
            $result->assertSee('SD NEGERI 1 JATI KULON');
            $result->assertSee('Kec. Jati, Kab. Kudus');
        }
    }

    public function testSearchAndFilters(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');
        $this->makeSchool(['npsn' => '20319001', 'name' => 'SD NEGERI 1 JATI KULON', 'district' => 'Jati']);
        $this->makeSchool(['name' => 'MI NU BANAT', 'level' => 'MI', 'district' => 'Kota Kudus']);
        $this->makeSchool(['name' => 'SD NEGERI 1 TAHUNAN', 'regency' => 'Kab. Jepara', 'is_active' => 0]);

        $byText = $this->actingAs($admin)->get('admin/referensi/sekolah?q=sdn+jati');
        $byText->assertSee('SD NEGERI 1 JATI KULON');
        $byText->assertDontSee('MI NU BANAT');

        $byNpsn = $this->actingAs($admin)->get('admin/referensi/sekolah?q=2031900');
        $byNpsn->assertSee('SD NEGERI 1 JATI KULON');

        $byLevel = $this->actingAs($admin)->get('admin/referensi/sekolah?jenjang=MI');
        $byLevel->assertSee('MI NU BANAT');
        $byLevel->assertDontSee('SD NEGERI 1 JATI KULON');

        $byRegency = $this->actingAs($admin)->get('admin/referensi/sekolah?kabupaten=Kab.+Jepara');
        $byRegency->assertSee('SD NEGERI 1 TAHUNAN');
        $byRegency->assertDontSee('MI NU BANAT');

        $inactive = $this->actingAs($admin)->get('admin/referensi/sekolah?status=nonaktif');
        $inactive->assertSee('SD NEGERI 1 TAHUNAN');
        $inactive->assertDontSee('MI NU BANAT');

        $none = $this->actingAs($admin)->get('admin/referensi/sekolah?q=tidakadasamasekali');
        $none->assertSee(lang('Referensi.noResultTitle'));
    }

    public function testAdminAddsSchool(): void
    {
        $result = $this->actingAs($this->makeStaff('admin1', 'admin'))->post('admin/referensi/sekolah/tambah', [
            'npsn'      => '20319009',
            'name'      => 'SDN 4 Jati',
            'level'     => 'SD',
            'ownership' => 'negeri',
            'province'  => 'Jawa Tengah',
            'regency'   => 'Kabupaten Kudus',
            'district'  => 'Kec. Jati',
        ]);

        $result->assertRedirectTo(site_url('admin/referensi/sekolah'));
        $result->assertSessionHas('message');
        $this->seeInDatabase('ref_sekolah', ['npsn' => '20319009', 'name' => 'SD Negeri 4 Jati', 'regency' => 'Kab. Kudus', 'district' => 'Jati', 'source' => 'manual', 'is_active' => 1]);
        $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'ref_sekolah']);
    }

    public function testInvalidSchoolIsNotSaved(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');
        $this->makeSchool(['npsn' => '20319001', 'name' => 'SD A']);

        $empty = $this->actingAs($admin)->post('admin/referensi/sekolah/tambah', ['name' => '', 'level' => 'SD']);
        $empty->assertRedirect();
        $this->assertArrayHasKey('name', session('errors'));

        $this->actingAs($admin)->post('admin/referensi/sekolah/tambah', ['name' => 'SD B', 'level' => 'SD', 'npsn' => '20319001']);
        $this->assertSame(lang('Referensi.npsnTaken'), session('errors')['npsn']);

        $this->assertSame(1, model(SchoolModel::class, false)->countAllResults());
    }

    public function testAdminEditsSchool(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');
        $id    = $this->makeSchool(['npsn' => '20319001', 'name' => 'SD A', 'district' => 'Jati']);

        $this->actingAs($admin)->get('admin/referensi/sekolah/' . $id . '/ubah')->assertSee('SD A');

        $result = $this->actingAs($admin)->post('admin/referensi/sekolah/' . $id . '/ubah', [
            'npsn' => '20319001', 'name' => 'SD Negeri 1 Jati', 'level' => 'SD', 'district' => 'Jati', 'regency' => 'Kab. Kudus', 'province' => 'Jawa Tengah',
        ]);

        $result->assertRedirectTo(site_url('admin/referensi/sekolah/' . $id . '/ubah'));
        $this->seeInDatabase('ref_sekolah', ['id' => $id, 'name' => 'SD Negeri 1 Jati', 'npsn' => '20319001']);
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'ref_sekolah', 'subject_id' => $id]);
    }

    public function testDeactivateAndActivate(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');
        $id    = $this->makeSchool(['name' => 'SD A']);

        $this->actingAs($admin)->post('admin/referensi/sekolah/' . $id . '/nonaktifkan')->assertRedirectTo(site_url('admin/referensi/sekolah/' . $id . '/ubah'));
        $this->seeInDatabase('ref_sekolah', ['id' => $id, 'is_active' => 0]);
        $this->assertSame([], model(SchoolModel::class, false)->search('SD A'));

        $this->actingAs($admin)->get('admin/referensi/sekolah/' . $id . '/ubah')->assertSee(lang('Referensi.activate'));

        $this->actingAs($admin)->post('admin/referensi/sekolah/' . $id . '/aktifkan');
        $this->seeInDatabase('ref_sekolah', ['id' => $id, 'is_active' => 1]);
    }

    public function testUnknownSchoolIsNotFound(): void
    {
        $this->actingAs($this->makeStaff('admin1', 'admin'))->get('admin/referensi/sekolah/999/ubah')->assertStatus(404);
    }
}
