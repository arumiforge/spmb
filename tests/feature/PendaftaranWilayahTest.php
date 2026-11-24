<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Services\ApplicantService;
use Tests\Support\Pendaftaran\PendaftaranFixtures;
use Tests\Support\Referensi\RegionFixtures;

/**
 * The address on the registration forms picked from the national region list
 * (D-111): the picker in the public form, the offline form and the correction
 * page, the codes check and the fallback without JavaScript.
 *
 * @internal
 */
final class PendaftaranWilayahTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PendaftaranFixtures;
    use RegionFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetVisitor();
        $this->forgetRegionSettings();
        service('throttler')->remove('daftar-' . md5('0.0.0.0'));
        $this->loadRegions();
    }

    protected function tearDown(): void
    {
        $this->forgetPendaftaranSettings();
        $this->forgetRegionSettings();
        $this->resetVisitor();
        parent::tearDown();
    }

    /**
     * Form input as the picker posts it with JavaScript: codes, no address text.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function pickedInput(array $overrides = []): array
    {
        $input = $this->applicantInput();
        unset($input['village'], $input['district'], $input['regency'], $input['province']);

        return $overrides + ['province_code' => '33', 'regency_code' => '33.19', 'district_code' => '33.19.03', 'village_code' => '33.19.03.2006'] + $input;
    }

    public function testThePublicFormHasThePickerAndTheTextFieldsForNoJavaScript(): void
    {
        $this->makeWave();
        $body = $this->get('daftar')->getBody();

        $this->assertStringContainsString('data-wilayah ', $body);
        $this->assertStringContainsString(lang('Referensi.regionSearchLabel'), $body);
        // The selects are off until the script switches them on; the text fields work without it.
        $this->assertMatchesRegularExpression('/<select id="f-province_code" name="province_code" disabled/', $body);
        $this->assertMatchesRegularExpression('/<select id="f-village_code" name="village_code" disabled/', $body);
        $this->assertMatchesRegularExpression('/<input id="f-village" type="text" name="village"/', $body);
        $this->assertStringContainsString('<option value="33">Jawa Tengah</option>', $body);
        $this->assertStringContainsString(lang('Referensi.regionChooseFirst.regency'), $body);
    }

    public function testTheSchoolRegionIsChosenOnAFreshForm(): void
    {
        $this->makeWave();
        setting('School.regionCode', '33.19');
        $body = $this->get('daftar')->getBody();

        $this->assertStringContainsString('<option value="33" selected>Jawa Tengah</option>', $body);
        $this->assertStringContainsString('<option value="33.19" selected>Kab. Kudus</option>', $body);
        $this->assertStringContainsString('<option value="33.19.03">Jati</option>', $body, 'the districts of the regency are listed');
        $this->assertStringContainsString('data-wilayah-mode="pilih"', $body);
    }

    public function testPickedCodesAreStoredWithTheOfficialNames(): void
    {
        $this->makeWave();

        $this->post('daftar', $this->pickedInput())->assertRedirectTo(site_url('daftar/selesai'));

        $row = model(ApplicantModel::class)->first();
        $this->assertSame(['33', '33.19', '33.19.03', '33.19.03.2006'], [$row['province_code'], $row['regency_code'], $row['district_code'], $row['village_code']]);
        $this->assertSame(['Jawa Tengah', 'Kab. Kudus', 'Jati', 'Ploso'], [$row['province'], $row['regency'], $row['district'], $row['village']]);
    }

    public function testCodesThatDoNotBelongTogetherAreRefused(): void
    {
        $this->makeWave();

        // Ploso of Karangtengah (Demak) under the district Jati (Kudus).
        $this->post('daftar', $this->pickedInput(['village_code' => '33.21.05.2001']))->assertSessionHas('errors');
        // The first level that does not fit shows the message.
        $this->assertSame(lang('Referensi.regionMismatch'), session('errors')['regency']);

        $this->post('daftar', $this->pickedInput(['village_code' => '33.19.03.9999']))->assertSessionHas('errors');
        $this->assertSame(lang('Referensi.regionUnknown', ['field' => lang('Referensi.regionLevels.village')]), session('errors')['village']);

        $this->post('daftar', $this->pickedInput(['village_code' => '33.19.03']))->assertSessionHas('errors');
        $this->assertArrayHasKey('village', session('errors'));

        $this->assertSame(0, model(ApplicantModel::class)->countAllResults());
    }

    public function testARequiredVillageMustBeChosen(): void
    {
        $this->makeWave();
        setting('Pendaftaran.requiredFields', ['nisn', 'village', 'district', 'regency', 'province']);

        $this->post('daftar', $this->pickedInput(['village_code' => '']))->assertSessionHas('errors');
        $this->assertArrayHasKey('village', session('errors'));
        $this->assertArrayNotHasKey('district', session('errors'));

        $body = $this->get('daftar')->getBody();
        $this->assertMatchesRegularExpression('/name="village_code" disabled[^>]*required/', $body);
    }

    public function testWithoutJavaScriptTheTextIsKeptAndMatchedWhenItIsExact(): void
    {
        $this->makeWave();

        // Exact names (after removing "Ds."/"Kec."): codes are added and the official names used.
        $this->post('daftar', $this->applicantInput(['full_name' => 'Anak Satu', 'village' => 'Ds. Ploso', 'district' => 'Kec. Jati', 'regency' => 'Kudus']))->assertRedirectTo(site_url('daftar/selesai'));
        $row = model(ApplicantModel::class)->where('full_name', 'Anak Satu')->first();
        $this->assertSame(['33.19.03.2006', 'Ploso', 'Kab. Kudus'], [$row['village_code'], $row['village'], $row['regency']]);

        // A typo is not guessed on the form: the text stays, the codes cover what is sure.
        $this->resetVisitor();
        service('throttler')->remove('daftar-' . md5('0.0.0.0'));
        $this->post('daftar', $this->applicantInput(['full_name' => 'Anak Dua', 'nisn' => '0123456780', 'village' => 'Plosso', 'district' => 'Jati', 'regency' => 'Kudus']))->assertRedirectTo(site_url('daftar/selesai'));
        $row = model(ApplicantModel::class)->where('full_name', 'Anak Dua')->first();
        $this->assertSame(['Plosso', null, '33.19.03'], [$row['village'], $row['village_code'], $row['district_code']]);

        // A place that is not in the list at all is accepted as typed.
        $this->resetVisitor();
        service('throttler')->remove('daftar-' . md5('0.0.0.0'));
        $this->post('daftar', $this->applicantInput(['full_name' => 'Anak Tiga', 'nisn' => '0123456781', 'village' => 'Kampung Antah', 'district' => 'Berantah', 'regency' => 'Negeri Dongeng', 'province' => 'Khayalan']))->assertRedirectTo(site_url('daftar/selesai'));
        $row = model(ApplicantModel::class)->where('full_name', 'Anak Tiga')->first();
        $this->assertSame(['Kampung Antah', 'Khayalan', null, null], [$row['village'], $row['province'], $row['village_code'], $row['province_code']]);
    }

    public function testWithoutTheRegionListTheFormStaysAsBefore(): void
    {
        $this->makeWave();
        setting()->forget('Referensi.regionsVersion');

        $this->assertStringNotContainsString('data-wilayah ', $this->get('daftar')->getBody());

        $this->post('daftar', $this->applicantInput(['village' => 'Ds. Ploso']))->assertRedirectTo(site_url('daftar/selesai'));
        $row = model(ApplicantModel::class)->first();
        $this->assertSame(['Ds. Ploso', null], [$row['village'], $row['village_code']]);
    }

    public function testApplicantCorrectsTheAddressOnTheirOwnPage(): void
    {
        $data   = ApplicantForm::clean($this->applicantInput(), [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE]);
        $result = (new ApplicantService())->register($data, 'offline');
        $id     = (int) $result['applicant']['id'];
        $this->get('tautan/' . $result['token']);

        // A saved address typed by hand that matches the list exactly opens with the list, already chosen.
        $page = $this->withSession($_SESSION)->get('cek-status/ubah');
        $page->assertSee(lang('Referensi.regionSearchLabel'));
        $this->assertStringContainsString('data-wilayah-mode="pilih"', $page->getBody());
        $this->assertStringContainsString('<option value="33.19.03.2008" selected>Getaspejaten</option>', $page->getBody());

        $this->withSession($_SESSION)->post('cek-status/ubah', [
            'address'       => 'Jl. Pindahan 5', 'parent_phone' => '081234567890',
            'province_code' => '33', 'regency_code' => '33.20', 'district_code' => '33.20.06', 'village_code' => '33.20.06.1004',
        ])->assertRedirectTo(site_url('cek-status/data'));

        $row = model(ApplicantModel::class)->find($id);
        $this->assertSame(['33.20.06.1004', 'Bapangan', 'Jepara', 'Kab. Jepara'], [$row['village_code'], $row['village'], $row['district'], $row['regency']]);
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'applicants', 'subject_id' => (string) $id]);

        // Choosing only down to the district clears the old village.
        $this->withSession($_SESSION)->post('cek-status/ubah', [
            'address'       => 'Jl. Pindahan 5', 'parent_phone' => '081234567890',
            'province_code' => '33', 'regency_code' => '33.19', 'district_code' => '33.19.07',
        ])->assertRedirectTo(site_url('cek-status/data'));
        $row = model(ApplicantModel::class)->find($id);
        $this->assertSame(['33.19.07', null, null, 'Bae'], [$row['district_code'], $row['village_code'], $row['village'], $row['district']]);

        $edit = $this->withSession($_SESSION)->get('cek-status/ubah')->getBody();
        $this->assertStringContainsString('data-wilayah-mode="pilih"', $edit);
        $this->assertStringContainsString('<option value="33.19.07" selected>Bae</option>', $edit);
    }

    public function testATypedAddressThatDoesNotMatchOpensWithTheTextFields(): void
    {
        $data = ApplicantForm::clean($this->applicantInput(['village' => 'Desa Contoh']), [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE]);
        $row  = (new ApplicantService())->register($data, 'offline')['applicant'];

        $edit = $this->actingAs($this->makeUser('panitia'))->get('admin/pendaftar/' . $row['id'] . '/ubah')->getBody();
        $this->assertStringContainsString('data-wilayah-mode="ketik"', $edit);
        $this->assertMatchesRegularExpression('/<input id="f-village" type="text" name="village" value="Desa Contoh"/', $edit);
        // "Pilih dari daftar" starts at what matched: the district Jati.
        $this->assertStringContainsString('<option value="33.19.03" selected>Jati</option>', $edit);
    }

    public function testStaffAddAndEditWithThePicker(): void
    {
        $this->makeWave();
        $panitia = $this->makeUser('panitia');

        $this->assertStringContainsString('data-wilayah ', $this->actingAs($panitia)->get('admin/pendaftar/tambah')->getBody());

        $this->actingAs($panitia)->post('admin/pendaftar/tambah', $this->pickedInput(['village_code' => '33.19.02.1001', 'district_code' => '33.19.02']))->assertSessionHas('private_link');
        $row = model(ApplicantModel::class)->first();
        $this->assertSame(['33.19.02.1001', 'Purwosari', 'Kota Kudus'], [$row['village_code'], $row['village'], $row['district']]);

        $edit = $this->actingAs($panitia)->get('admin/pendaftar/' . $row['id'] . '/ubah')->getBody();
        $this->assertStringContainsString('<option value="33.19.02.1001" selected>Purwosari</option>', $edit);
    }

    public function testEnglishAndArabicLabels(): void
    {
        $this->makeWave();

        $en = $this->get('en/daftar');
        $en->assertSee('Search for your village');
        $en->assertSee('Choose the province');
        $en->assertSee('My place is not in the list, I will type it');
        // Region names are proper nouns: never translated.
        $en->assertSee('Jawa Tengah');

        $ar = $this->get('ar/daftar');
        $ar->assertSee('ابحث عن القرية');
        $ar->assertSee('اختر المحافظة');
        $ar->assertSee('Jawa Tengah');
    }
}
