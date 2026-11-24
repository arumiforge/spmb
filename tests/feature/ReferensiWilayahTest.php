<?php

declare(strict_types=1);

use CodeIgniter\Config\Factories;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Referensi;
use Modules\Pendaftaran\Models\ApplicantModel;
use Tests\Support\Referensi\ReferensiFixtures;
use Tests\Support\Referensi\RegionFixtures;

/**
 * Public region lookups (/wilayah/daftar, /wilayah/cari), Sekolah Asal →
 * Alamat pendaftar and the school's own region in Identitas Sekolah (D-111).
 *
 * @internal
 */
final class ReferensiWilayahTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use ReferensiFixtures;
    use RegionFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetSignedInUser();
        $this->forgetRegionSettings();
        cache()->clean();
        $this->loadRegions();
    }

    protected function tearDown(): void
    {
        Factories::reset('config');
        $this->forgetRegionSettings();
        cache()->clean();
        parent::tearDown();
    }

    public function testChildrenAreJsonAndCachedByTheBrowser(): void
    {
        $provinces = $this->get('wilayah/daftar');
        $provinces->assertOK();
        $this->assertStringContainsString('application/json', $provinces->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('max-age=86400', $provinces->response()->getHeaderLine('Cache-Control'));
        $this->assertCount(38, json_decode($provinces->getJSON(), true));

        $kudus = json_decode($this->get('wilayah/daftar?induk=33.19')->getJSON(), true);
        $this->assertContains(['code' => '33.19.03', 'name' => 'Jati', 'label' => 'Jati'], $kudus);

        $regencies = json_decode($this->get('wilayah/daftar?induk=33')->getJSON(), true);
        $this->assertContains(['code' => '33.19', 'name' => 'Kabupaten Kudus', 'label' => 'Kab. Kudus'], $regencies);

        $this->assertSame([], json_decode($this->get('wilayah/daftar?induk=99.99')->getJSON(), true));
        $this->get('wilayah/daftar?induk=33.19.03.2006')->assertStatus(400);
        $this->get('wilayah/daftar?induk=' . urlencode("33' OR 1=1"))->assertStatus(400);

        // The same list again: the browser's copy is still good.
        $etag = $this->get('wilayah/daftar?induk=33.19')->response()->getHeaderLine('ETag');
        $this->assertNotSame('', $etag);
        $again = $this->withHeaders(['If-None-Match' => $etag])->get('wilayah/daftar?induk=33.19');
        $again->assertStatus(304);
    }

    public function testSearchGivesTheFullPath(): void
    {
        setting('School.regionCode', '33.19');

        $rows = json_decode($this->get('wilayah/cari?q=ploso')->getJSON(), true);
        $this->assertSame('Ploso, Jati, Kab. Kudus, Jawa Tengah', $rows[0]['path']);
        $this->assertSame('33.19.03.2006', $rows[0]['codes']['village']);

        $this->assertSame([], json_decode($this->get('wilayah/cari?q=pl')->getJSON(), true));
        $this->assertSame([], json_decode($this->get('wilayah/cari?q=' . urlencode('%_!'))->getJSON(), true));
    }

    public function testLookupsAreThrottledPerVisitor(): void
    {
        $config                        = config(Referensi::class);
        $config->regionSearchPerMinute = 1;
        $config->regionListPerMinute   = 1;
        Factories::injectMock('config', Referensi::class, $config);

        $this->get('wilayah/cari?q=ploso')->assertOK();
        $blocked = $this->get('wilayah/cari?q=ploso');
        $blocked->assertStatus(429);
        $this->assertSame(lang('Referensi.tooMany'), json_decode($blocked->getJSON(), true)['message']);

        $this->get('wilayah/daftar')->assertOK();
        $this->get('wilayah/daftar')->assertStatus(429);
    }

    public function testAddressPageNeedsThePermission(): void
    {
        $this->get('admin/referensi/alamat')->assertRedirectTo(site_url('masuk'));
        $this->actingAs($this->makeStaff('panitia1', 'panitia'))->get('admin/referensi/alamat')->assertRedirectTo(site_url('admin/akses-ditolak'));
    }

    public function testAdminPreviewsAndSavesTheMatching(): void
    {
        $id    = $this->makeApplicant(['village' => 'Ds. Getas Pejaten', 'district' => 'Kec. Jati', 'regency' => 'Kudus', 'province' => 'Jawa Tengah']);
        $admin = $this->makeStaff('admin1', 'admin');

        $page = $this->actingAs($admin)->get('admin/referensi/alamat');
        $page->assertOK();
        $page->assertSee(lang('Referensi.addressPreview'));
        $page->assertSee(lang('Referensi.addressRegionHint'));

        $preview = $this->actingAs($admin)->get('admin/referensi/alamat?pratinjau=1');
        $preview->assertSee('Getaspejaten, Jati, Kab. Kudus, Jawa Tengah');
        $preview->assertSee(lang('Referensi.addressSave'));
        $this->assertNull(model(ApplicantModel::class, false)->find($id)['village_code']);

        $this->actingAs($admin)->post('admin/referensi/alamat')->assertRedirectTo(site_url('admin/referensi/alamat'));
        $this->assertSame('33.19.03.2008', model(ApplicantModel::class, false)->find($id)['village_code']);

        $this->actingAs($admin)->get('admin/referensi/sekolah')->assertSee(lang('Referensi.addressButton'));
    }

    public function testSchoolRegionIsChosenInIdentitasSekolah(): void
    {
        $admin = $this->makeStaff('admin1', 'admin');
        $post  = ['name' => 'SMP Contoh', 'shortName' => 'SMP Contoh', 'primaryColor' => '#0E7490', 'timezoneLabel' => 'WIB'];

        $page = $this->actingAs($admin)->get('admin/pengaturan/sekolah');
        $page->assertSee(lang('Pengaturan.regionCode'));
        $page->assertSee('<option value="33.19">Kab. Kudus</option>', null, false);

        $this->actingAs($admin)->post('admin/pengaturan/sekolah', $post + ['regionCode' => '33.19.03'])->assertSessionHas('errors');
        $this->assertSame('', (string) setting('School.regionCode'));

        $this->actingAs($admin)->post('admin/pengaturan/sekolah', $post + ['regionCode' => '33.19'])->assertRedirect();
        $this->assertSame('33.19', setting('School.regionCode'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_id' => 'School']);

        $this->actingAs($admin)->post('admin/pengaturan/sekolah', $post + ['regionCode' => ''])->assertRedirect();
        $this->assertSame('', (string) setting('School.regionCode'));

        foreach (['name', 'shortName', 'primaryColor', 'timezoneLabel'] as $key) {
            setting()->forget('School.' . $key);
        }
    }
}
