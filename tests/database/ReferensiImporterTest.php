<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Referensi\Libraries\SchoolImporter;
use Modules\Referensi\Models\SchoolModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\Referensi\ReferensiFixtures;

/**
 * CSV/XLSX import: header aliases, row report, upsert by NPSN, never deletes.
 *
 * @internal
 */
final class ReferensiImporterTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use ReferensiFixtures;

    protected $namespace;
    protected $refresh = true;

    public function testCsvImportReportsEveryRow(): void
    {
        $importer = new SchoolImporter();
        $rows     = $importer->readFile(self::fixture('sekolah.csv'), 'csv');

        $this->assertSame([2, 3, 4, 5, 6, 8], array_keys($rows), 'spreadsheet row numbers, empty line skipped');

        $report = $importer->import($rows, 'import');

        $this->assertSame(2, $report['created']);
        $this->assertSame(1, $report['updated'], 'the NPSN repeated in row 8 updates row 2');
        $this->assertSame(3, $report['failed']);
        $this->assertSame([4, 5, 6], array_column($report['errors'], 'row'));
        $this->assertSame(lang('Referensi.rowBadNpsn'), $report['errors'][0]['message']);
        $this->assertSame(lang('Referensi.rowNoName'), $report['errors'][1]['message']);

        $sd = model(SchoolModel::class, false)->where('npsn', '20319101')->first();
        $this->assertSame('SD NEGERI 3 JATI WETAN', $sd['name']);
        $this->assertSame('Kab. Kudus', $sd['regency']);
        $this->assertSame('Jati', $sd['district']);
        $this->assertSame('negeri', $sd['ownership']);
        $this->assertSame('import', $sd['source']);

        $mi = model(SchoolModel::class, false)->where('name', 'MI NU Hidayatul Mubtadiin')->first();
        $this->assertNull($mi['npsn']);
        $this->assertSame('MI', $mi['level']);
    }

    public function testImportIsSafeToRunAgainAndKeepsManualDecisions(): void
    {
        $importer = new SchoolImporter();
        $rows     = $importer->readFile(self::fixture('sekolah.csv'), 'csv');
        $importer->import($rows, 'import');

        $model = model(SchoolModel::class, false);
        $id    = (int) $model->where('npsn', '20319101')->first()['id'];
        $model->update($id, ['is_active' => 0, 'address' => 'Jl. Lama']);
        $extra = $this->makeSchool(['name' => 'SD Tidak Ada di Berkas']);

        $again = (new SchoolImporter())->import($rows, 'import');

        $this->assertSame(0, $again['created']);
        $this->assertSame(3, $again['failed']);
        $this->assertSame(3, $model->countAllResults());
        $row = $model->find($id);
        $this->assertSame('0', (string) $row['is_active'], 'deactivating stays manual');
        $this->assertSame('Jl. Lama', $row['address'], 'empty cells keep existing data');
        $this->assertNotNull($model->find($extra), 'nothing is deleted');
    }

    public function testRowWithNpsnCompletesSchoolTypedWithoutOne(): void
    {
        $id = $this->makeSchool(['name' => 'SD NEGERI 1 BAE', 'district' => 'Bae']);

        $report = (new SchoolImporter())->import([2 => ['npsn' => '20319555', 'name' => 'SD Negeri 1 Bae', 'level' => 'SD', 'district' => 'Bae', 'regency' => 'Kab. Kudus']], 'dapodik');

        $this->assertSame(['created' => 0, 'updated' => 1], array_intersect_key($report, ['created' => 0, 'updated' => 0]));
        $this->assertSame('20319555', model(SchoolModel::class, false)->find($id)['npsn']);
    }

    public function testDryRunWritesNothing(): void
    {
        $importer = new SchoolImporter();
        $report   = $importer->import($importer->readFile(self::fixture('sekolah.csv'), 'csv'), 'import', true);

        $this->assertSame(2, $report['created']);
        $this->assertSame(0, model(SchoolModel::class, false)->countAllResults());
    }

    public function testXlsxWithOtherHeaderNames(): void
    {
        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->fromArray([
            ['nama_sekolah', 'bentuk_pendidikan', 'npsn', 'status_sekolah', 'kabupaten', 'kecamatan', 'desa_kelurahan', 'alamat_jalan'],
            ['SD NEGERI 1 PLOSO', 'SD', 20319201, 'N', 'Kab. Kudus', 'Kec. Jati', 'Ploso', 'Jl. Ploso'],
            ['MIS NU Nurul Ulum', 'MIS', null, 'S', 'Kab. Kudus', 'Jekulo', null, null],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'ref') . '.xlsx';
        (new Xlsx($sheet->getParent()))->save($path);

        try {
            $report = (new SchoolImporter())->import((new SchoolImporter())->readFile($path, 'xlsx'), 'import');
        } finally {
            unlink($path);
        }

        $this->assertSame(2, $report['created'], json_encode($report['errors']));
        $ploso = model(SchoolModel::class, false)->where('npsn', '20319201')->first();
        $this->assertSame(['Ploso', 'Jl. Ploso', 'negeri', 'Jati'], [$ploso['village'], $ploso['address'], $ploso['ownership'], $ploso['district']]);
        $this->assertSame('MI NU Nurul Ulum', model(SchoolModel::class, false)->where('level', 'MI')->first()['name']);
    }

    public function testFileWithoutRequiredColumnsIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(lang('Referensi.importBadHeader'));

        (new SchoolImporter())->mapRows([['NPSN', 'Alamat'], ['20319001', 'Jl. A']]);
    }

    public function testUnknownFileTypeIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(lang('Referensi.importBadType'));

        (new SchoolImporter())->readFile(self::fixture('sekolah.csv'), 'pdf');
    }
}
