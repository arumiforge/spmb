<?php

declare(strict_types=1);

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Referensi;
use Tests\Support\Referensi\ReferensiFixtures;

/**
 * Public school search (/sekolah-asal/cari) and the school picker partial.
 *
 * @internal
 */
final class ReferensiCariTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use ReferensiFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetSignedInUser();
        cache()->clean();
    }

    protected function tearDown(): void
    {
        Factories::reset('config');
        cache()->clean();
        parent::tearDown();
    }

    public function testSearchReturnsJsonForGuests(): void
    {
        $id = $this->makeSchool(['npsn' => '20319001', 'name' => 'SD NEGERI 1 JATI KULON', 'district' => 'Jati']);
        $this->makeSchool(['name' => 'SD NEGERI 2 JATI TUTUP', 'district' => 'Jati', 'is_active' => 0]);

        $result = $this->get('sekolah-asal/cari?q=sdn+jati');

        $result->assertOK();
        $this->assertStringContainsString('application/json', $result->response()->getHeaderLine('Content-Type'));
        $this->assertSame([[
            'id'       => $id,
            'npsn'     => '20319001',
            'name'     => 'SD NEGERI 1 JATI KULON',
            'level'    => 'SD',
            'district' => 'Jati',
            'regency'  => 'Kab. Kudus',
            'label'    => 'SD NEGERI 1 JATI KULON — Kec. Jati, Kab. Kudus',
        ]], json_decode($result->getJSON(), true));

        $this->assertSame([], json_decode($this->get('sekolah-asal/cari?q=s')->getJSON(), true));
    }

    public function testSearchIsThrottledPerVisitor(): void
    {
        $config                  = config(Referensi::class);
        $config->searchPerMinute = 2;
        Factories::injectMock('config', Referensi::class, $config);

        $this->get('sekolah-asal/cari?q=sd')->assertOK();
        $this->get('sekolah-asal/cari?q=sd')->assertOK();

        $blocked = $this->get('sekolah-asal/cari?q=sd');
        $blocked->assertStatus(429);
        $this->assertSame(lang('Referensi.tooMany'), json_decode($blocked->getJSON(), true)['message']);
    }

    public function testPickerWorksWithoutJavaScript(): void
    {
        $html = view('Modules\Referensi\Views\partials\school_picker', ['picker' => ['id' => null, 'name' => 'SD Swasta Pati', 'error' => null, 'required' => true]]);
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);

        $this->assertStringContainsString('name="previous_school_name" value="SD Swasta Pati"', $html);
        $this->assertStringContainsString('name="previous_school_id" value=""', $html);
        $this->assertStringContainsString('role="combobox"', $html);
        $this->assertStringContainsString(' required', $html);
        $this->assertStringContainsString(lang('Referensi.pickerNotListed'), $html);
        $this->assertStringContainsString(site_url('sekolah-asal/cari'), $html);
    }

    public function testPickerShowsChosenSchoolAndError(): void
    {
        $id   = $this->makeSchool(['name' => 'SD NEGERI 1 JATI KULON', 'district' => 'Jati']);
        $html = view('Modules\Referensi\Views\partials\school_picker', ['picker' => ['id' => $id, 'name' => null, 'error' => 'Sekolah asal wajib diisi.', 'required' => false]]);
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);

        $this->assertStringContainsString('value="SD NEGERI 1 JATI KULON — Kec. Jati, Kab. Kudus"', $html);
        $this->assertStringContainsString('name="previous_school_id" value="' . $id . '"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('Sekolah asal wajib diisi.', $html);
        $this->assertStringContainsString(lang('App.optional'), $html);

        $unknown = view('Modules\Referensi\Views\partials\school_picker', ['picker' => ['id' => 999, 'name' => 'Ketikan', 'required' => false]]);
        $this->assertStringContainsString('name="previous_school_id" value=""', $unknown);
    }
}
