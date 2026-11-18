<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Referensi\Database\Seeds\DemoSeeder;
use Modules\Referensi\Models\SchoolModel;
use Tests\Support\Referensi\ReferensiFixtures;

/**
 * SchoolModel: label, search, picker resolution, normalisation, demo data.
 *
 * @internal
 */
final class ReferensiSchoolModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use ReferensiFixtures;

    protected $namespace;
    protected $refresh = true;
    private SchoolModel $schools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schools = model(SchoolModel::class, false);
    }

    public function testLabelShowsDistrictAndRegency(): void
    {
        $id = $this->makeSchool(['name' => 'SD NEGERI 1 JATI KULON', 'district' => 'Jati']);

        $this->assertSame('SD NEGERI 1 JATI KULON — Kec. Jati, Kab. Kudus', $this->schools->label($id));
        $this->assertNull($this->schools->label($id + 100));
        $this->assertSame('SD Contoh', SchoolModel::formatLabel(['name' => 'SD Contoh', 'district' => null, 'regency' => null]));
    }

    public function testNormaliseCleansNamesAndPlaces(): void
    {
        $row = SchoolModel::normalise([
            'name'      => '  SDN   2 Jati ',
            'level'     => 'sd',
            'ownership' => 'Negeri',
            'province'  => 'Prov. Jawa Tengah',
            'regency'   => 'Kabupaten Kudus',
            'district'  => 'Kec. Jati',
            'village'   => '',
        ]);

        $this->assertSame('SD Negeri 2 Jati', $row['name']);
        $this->assertSame('SD', $row['level']);
        $this->assertSame('negeri', $row['ownership']);
        $this->assertSame('Jawa Tengah', $row['province']);
        $this->assertSame('Kab. Kudus', $row['regency']);
        $this->assertSame('Jati', $row['district']);
        $this->assertNull($row['village']);
        $this->assertSame('SD NEGERI 4 DAWE', SchoolModel::normaliseName('SD N 4 DAWE'));
        $this->assertSame('MI NEGERI 1 KUDUS', SchoolModel::normaliseName('MIN 1 KUDUS'));
        $this->assertSame('MI NU Banat', SchoolModel::normaliseName('MIS NU Banat'));
        $this->assertSame('SD Nusantara', SchoolModel::normaliseName('SD Nusantara'));
    }

    public function testSearchMatchesWordStartsAndAbbreviations(): void
    {
        $jati   = $this->makeSchool(['npsn' => '20319001', 'name' => 'SD NEGERI 1 JATI KULON', 'district' => 'Jati']);
        $kramat = $this->makeSchool(['npsn' => '20319002', 'name' => 'SD NEGERI 2 KRAMAT', 'district' => 'Kota Kudus']);
        $mi     = $this->makeSchool(['name' => 'MI NU BANAT', 'level' => 'MI', 'district' => 'Kota Kudus']);
        $this->makeSchool(['name' => 'SD NEGERI 3 JATI TUTUP', 'district' => 'Jati', 'is_active' => 0]);

        $this->assertSame([$jati], array_column($this->schools->search('sdn 1 jati'), 'id'));
        $this->assertSame([$jati], array_column($this->schools->search('kulon'), 'id'));
        $this->assertSame([$jati, $kramat], array_column($this->schools->search('2031900'), 'id'), 'NPSN prefix');
        $this->assertSame([$kramat], array_column($this->schools->search('20319002'), 'id'));
        $this->assertEqualsCanonicalizing([$kramat, $mi], array_column($this->schools->search('kota kudus'), 'id'), 'district');
        $this->assertSame([], $this->schools->search('ulon'), 'only word starts');
        $this->assertSame([], $this->schools->search('s'), 'at least 2 characters');
        $this->assertSame([], array_column($this->schools->search('tutup'), 'id'), 'inactive schools are hidden');

        $first = $this->schools->search('banat')[0];
        $this->assertSame(['id', 'npsn', 'name', 'level', 'district', 'regency', 'label'], array_keys($first));
        $this->assertSame('MI NU BANAT — Kec. Kota Kudus, Kab. Kudus', $first['label']);
    }

    public function testSearchPutsConfiguredProvincesFirstAndCapsAtTwenty(): void
    {
        $outside = $this->makeSchool(['name' => 'SD NEGERI 1 ANDONG', 'province' => 'Jawa Timur', 'regency' => 'Kab. Tuban']);
        $inside  = $this->makeSchool(['name' => 'SD NEGERI 9 ZETA ANDONG', 'province' => 'Jawa Tengah']);

        $this->assertSame([$inside, $outside], array_column($this->schools->search('andong'), 'id'));

        setting('Referensi.provinces', ['Jawa Timur']);
        $this->assertSame([$outside, $inside], array_column($this->schools->search('andong'), 'id'));

        for ($i = 1; $i <= 25; $i++) {
            $this->makeSchool(['name' => 'SD NEGERI ' . $i . ' BANYAK']);
        }

        $this->assertCount(20, $this->schools->search('banyak'));
    }

    public function testFromPickerKeepsListEntryOrFreeText(): void
    {
        $id     = $this->makeSchool(['name' => 'SD NEGERI 1 JATI KULON', 'district' => 'Jati']);
        $closed = $this->makeSchool(['name' => 'SD NEGERI 9 TUTUP', 'is_active' => 0]);
        $label  = 'SD NEGERI 1 JATI KULON — Kec. Jati, Kab. Kudus';

        $this->assertSame(['previous_school_id' => $id, 'previous_school_name' => null], $this->schools->fromPicker((string) $id, $label));
        $this->assertSame(['previous_school_id' => $id, 'previous_school_name' => null], $this->schools->fromPicker($id, ''));
        $this->assertSame(['previous_school_id' => $id, 'previous_school_name' => null], $this->schools->fromPicker('', 'sd negeri 1 jati kulon'), 'exact name');
        $this->assertSame(['previous_school_id' => null, 'previous_school_name' => 'SD Lain di Pati'], $this->schools->fromPicker($id, ' SD Lain  di Pati '), 'text changed after choosing');
        $this->assertSame(['previous_school_id' => null, 'previous_school_name' => 'SD NEGERI 9 TUTUP'], $this->schools->fromPicker($closed, 'SD NEGERI 9 TUTUP'), 'inactive school');
        $this->assertSame(['previous_school_id' => null, 'previous_school_name' => null], $this->schools->fromPicker(null, '   '));
        $this->assertSame(150, mb_strlen((string) $this->schools->fromPicker(null, str_repeat('a', 200))['previous_school_name']));
    }

    public function testModelValidationUsesFriendlyMessages(): void
    {
        $this->makeSchool(['npsn' => '20319001', 'name' => 'SD A']);

        $this->assertFalse($this->schools->insert(['npsn' => '20319001', 'name' => 'SD B', 'level' => 'SD']));
        $this->assertSame(lang('Referensi.npsnTaken'), $this->schools->errors()['npsn']);

        $this->assertFalse($this->schools->insert(['npsn' => '123', 'name' => 'SD C', 'level' => 'SMP']));
        $this->assertSame(lang('Referensi.npsnFormat'), $this->schools->errors()['npsn']);
        $this->assertArrayHasKey('level', $this->schools->errors());
    }

    public function testDemoSeederIsIdempotent(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $count = $this->schools->countAllResults();
        $this->assertGreaterThanOrEqual(20, $count);
        $this->assertSame(count(DemoSeeder::SCHOOLS) + 1, $count);
    }
}
