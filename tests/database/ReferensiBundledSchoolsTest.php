<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Referensi\Database\Seeds\InstallSeeder;
use Modules\Referensi\Libraries\BundledSchools;
use Modules\Referensi\Models\SchoolModel;

/**
 * The national SD/MI list shipped with SPMB (D-058): manifest, province
 * filter, version gate and the install seeder.
 *
 * @internal
 */
final class ReferensiBundledSchoolsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        setting()->forget('Referensi.bundledVersion');
    }

    private static function fixtureBundle(): BundledSchools
    {
        return new BundledSchools(SUPPORTPATH . 'Referensi/fixtures/bundle');
    }

    public function testShippedManifestListsEveryProvinceFile(): void
    {
        $bundle   = new BundledSchools();
        $manifest = $bundle->manifest();
        $files    = $bundle->files();

        $this->assertCount(38, $files, 'all provinces of Indonesia');
        $this->assertSame($manifest['schools'], array_sum(array_column($files, 'schools')));
        $this->assertGreaterThan(170000, $manifest['schools']);

        foreach ($files as $file) {
            $this->assertFileExists($file['path']);
        }

        $this->assertSame(['Jawa Tengah'], array_column($bundle->files(['Prov. Jawa Tengah']), 'province'));
        $this->assertSame(['D.I. Yogyakarta'], array_column($bundle->files(['di-yogyakarta']), 'province'));
    }

    public function testImportLoadsAllProvincesAndRemembersTheVersion(): void
    {
        $bundle = self::fixtureBundle();
        $this->assertFalse($bundle->isImported());

        $seen   = [];
        $report = $bundle->import(null, false, static function (string $province) use (&$seen): void {
            $seen[] = $province;
        });

        $this->assertSame(['D.K.I. Jakarta', 'Jawa Tengah'], $seen);
        $this->assertSame(4, $report['created']);
        $this->assertSame(1, $report['failed'], 'the row without a name');
        $this->assertStringStartsWith('D.K.I. Jakarta: ', $report['errors'][0]['message']);
        $this->assertTrue($bundle->isImported());
        $this->assertSame('2099-01-01', setting('Referensi.bundledVersion'));

        $model = model(SchoolModel::class, false);
        $mi    = $model->where('npsn', '60713001')->first();
        $this->assertSame('MI NU MIFTAHUL ULUM', $mi['name']);
        $this->assertSame('Jawa Tengah', $mi['province']);
        $this->assertSame('Jati', $mi['district']);
        $this->assertSame('swasta', $mi['ownership']);
        $this->assertSame(BundledSchools::SOURCE, $mi['source']);

        $temporary = $model->where('name', 'SD PUTIK INDONESIA')->first();
        $this->assertNull($temporary['npsn'], 'a temporary "NP…" number is not an NPSN');

        $again = self::fixtureBundle()->import();
        $this->assertSame([0, 0, 4], [$again['created'], $again['updated'], $again['unchanged']]);
    }

    public function testProvinceFilterAndDryRunDoNotRecordTheVersion(): void
    {
        $report = self::fixtureBundle()->import(['Jawa Tengah']);
        $this->assertSame(2, $report['created']);
        $this->assertSame('', (string) setting('Referensi.bundledVersion'));

        self::fixtureBundle()->import(null, true);
        $this->assertSame('', (string) setting('Referensi.bundledVersion'));
        $this->assertSame(2, model(SchoolModel::class, false)->countAllResults());
    }

    public function testUnknownProvinceIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        self::fixtureBundle()->files(['Atlantis']);
    }

    public function testInstallSeederSkipsWhenThisVersionIsLoaded(): void
    {
        setting('Referensi.bundledVersion', (new BundledSchools())->version());

        (new InstallSeeder(config('Database')))->run();

        $this->assertSame(0, model(SchoolModel::class, false)->countAllResults());
    }
}
