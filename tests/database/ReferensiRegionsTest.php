<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Referensi\Database\Seeds\InstallSeeder;
use Modules\Referensi\Libraries\BundledRegions;
use Modules\Referensi\Libraries\BundledSchools;
use Modules\Referensi\Models\RegionModel;
use Tests\Support\Referensi\RegionFixtures;

/**
 * The national region list shipped with SPMB (D-111): bundled data, the
 * version-gated import and the region model (children, chain, search).
 *
 * @internal
 */
final class ReferensiRegionsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use RegionFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetRegionSettings();
    }

    protected function tearDown(): void
    {
        $this->forgetRegionSettings();
        parent::tearDown();
    }

    public function testShippedListIsCompleteAndMatchesItsManifest(): void
    {
        $bundle   = new BundledRegions();
        $manifest = $bundle->manifest();

        $this->assertSame(38, $manifest['provinces']);
        $this->assertGreaterThanOrEqual(514, $manifest['regencies']);
        $this->assertGreaterThan(7000, $manifest['districts']);
        $this->assertGreaterThan(80000, $manifest['villages']);

        // rows() checks the checksum, every code and that parents come first.
        $rows = $bundle->rows();
        $this->assertCount($manifest['regions'], $rows);
        $byCode = array_column($rows, null, 'code');
        $this->assertSame('Kabupaten Kudus', $byCode['33.19']['name']);
        $this->assertSame(['33.19.03', 4, 'Ploso'], [$byCode['33.19.03.2006']['parent_code'], $byCode['33.19.03.2006']['level'], $byCode['33.19.03.2006']['name']]);
    }

    public function testImportIsVersionedAndIdempotent(): void
    {
        $bundle = self::regionBundle();
        $this->assertFalse($bundle->isImported());

        $dry = $bundle->import(true);
        $this->assertSame(1670, $dry['created']);
        $this->assertSame(0, db_connect()->table('ref_wilayah')->countAllResults());
        $this->assertSame('', (string) setting('Referensi.regionsVersion'));

        $first = $bundle->import();
        $this->assertSame(['created' => 1670, 'updated' => 0, 'unchanged' => 0, 'removed' => 0], $first);
        $this->assertTrue($bundle->isImported());
        $this->assertSame('2099-01-01', setting('Referensi.regionsVersion'));

        $again = self::regionBundle()->import();
        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 1670, 'removed' => 0], $again);
        $this->assertSame(1670, db_connect()->table('ref_wilayah')->countAllResults());
    }

    public function testANewerListUpdatesRenamesAndRemoves(): void
    {
        $this->loadRegions();

        $dir    = WRITEPATH . 'cache/wilayah-test-' . bin2hex(random_bytes(4));
        $rows   = array_map(static fn (array $r): array => [$r['code'], $r['name']], self::regionBundle()->rows());
        $rows   = array_values(array_filter($rows, static fn (array $r): bool => $r[0] !== '33.19.09.2018')); // a village dropped
        $rows   = array_map(static fn (array $r): array => $r[0] === '33.19.03.2008' ? [$r[0], 'Getas Pejaten'] : $r, $rows); // renamed
        $rows[] = ['33.19.03.2099', 'Desa Baru'];
        usort($rows, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));

        mkdir($dir);
        $gz = gzencode((string) json_encode($rows));
        file_put_contents($dir . '/wilayah.json.gz', $gz);
        file_put_contents($dir . '/manifest.json', (string) json_encode(['version' => '2099-02-01', 'file' => 'wilayah.json.gz', 'sha256' => hash('sha256', $gz), 'regions' => count($rows)]));

        try {
            $report = (new BundledRegions($dir))->import();
        } finally {
            array_map('unlink', glob($dir . '/*') ?: []);
            rmdir($dir);
        }

        $this->assertSame([1, 1, 1], [$report['created'], $report['updated'], $report['removed']]);
        $this->assertSame('2099-02-01', setting('Referensi.regionsVersion'));
        $model = model(RegionModel::class, false);
        $this->assertSame('Getas Pejaten', $model->region('33.19.03.2008')['name']);
        $this->assertNull($model->region('33.19.09.2018'));
    }

    public function testADamagedFileIsRefused(): void
    {
        $dir = WRITEPATH . 'cache/wilayah-rusak-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/wilayah.json.gz', gzencode('[["33","Jawa Tengah"],["33.19.03","Jati"]]'));
        file_put_contents($dir . '/manifest.json', '{"version":"x","file":"wilayah.json.gz"}');

        try {
            (new BundledRegions($dir))->import();
            $this->fail('a region before its parent must be refused');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('before its parent', $e->getMessage());
        } finally {
            array_map('unlink', glob($dir . '/*') ?: []);
            rmdir($dir);
        }

        $this->assertSame(0, db_connect()->table('ref_wilayah')->countAllResults());
    }

    public function testInstallSeederSkipsListsAlreadyLoaded(): void
    {
        setting('Referensi.regionsVersion', (new BundledRegions())->version());
        setting('Referensi.bundledVersion', (new BundledSchools())->version());

        (new InstallSeeder(config('Database')))->run();

        $this->assertSame(0, db_connect()->table('ref_wilayah')->countAllResults());
        setting()->forget('Referensi.bundledVersion');
    }

    public function testNamesCodesAndChildren(): void
    {
        $this->loadRegions();
        $model = model(RegionModel::class, false);

        $this->assertSame('Kab. Kudus', RegionModel::displayName('Kabupaten Kudus', 2));
        $this->assertSame('Kota Semarang', RegionModel::displayName('Kota Semarang', 2));
        $this->assertSame('Kabupaten Baru', RegionModel::displayName('Kabupaten Baru', 4), 'only regencies are shortened');
        $this->assertSame([1, 2, 3, 4, null], array_map(RegionModel::levelOf(...), ['33', '33.19', '33.19.03', '33.19.03.2006', '33.19.3']));
        $this->assertSame(['kelurahan', 'desa', null], [RegionModel::villageKind('33.19.02.1001'), RegionModel::villageKind('33.19.03.2006'), RegionModel::villageKind('33.19')]);

        $provinces = $model->children(null);
        $this->assertCount(38, $provinces);
        $this->assertSame('Aceh', $provinces[0]['name']);

        $regencies = array_column($model->children('33'), 'label');
        // Sorted by name without "Kabupaten"/"Kota"; the regency before the city of the same name.
        $this->assertLessThan(array_search('Kab. Pati', $regencies, true), array_search('Kab. Kudus', $regencies, true));
        $this->assertSame(array_search('Kab. Semarang', $regencies, true) + 1, array_search('Kota Semarang', $regencies, true));

        $this->assertContains(['code' => '33.19.03', 'name' => 'Jati', 'label' => 'Jati'], $model->children('33.19'));
        $this->assertSame([], $model->children('33.19.03.2006'), 'villages have no children');
        $this->assertSame([], $model->children('bogus'));

        $chain = $model->chain('33.19.03.2006');
        $this->assertSame([1, 2, 3, 4], array_keys($chain));
        $this->assertSame('Ploso, Jati, Kab. Kudus, Jawa Tengah', RegionModel::pathOf($chain));
        $this->assertNull($model->chain('33.19.03.9999'));

        $options = $model->regencyOptions();
        $this->assertSame('Kab. Kudus', $options['Jawa Tengah']['33.19']);
        $this->assertSame([], $options['Aceh'] ?? [], 'the fixture has regencies of Jawa Tengah only');
    }

    public function testSearchFindsVillagesAndDistrictsWithTheirPath(): void
    {
        $this->loadRegions();
        $model = model(RegionModel::class, false);

        $this->assertSame([], $model->search('pl'), 'at least three letters');

        $plain = $model->search('ploso');
        $this->assertGreaterThanOrEqual(3, count($plain));
        $this->assertSame('Ploso', $plain[0]['name']);

        setting('School.regionCode', '33.19');
        $home = $model->search('ploso');
        $this->assertSame('33.19.03.2006', $home[0]['code'], 'the school\'s regency first');
        $this->assertSame('Ploso, Jati, Kab. Kudus, Jawa Tengah', $home[0]['path']);
        $this->assertSame(['province' => '33', 'regency' => '33.19', 'district' => '33.19.03', 'village' => '33.19.03.2006'], $home[0]['codes']);
        $this->assertSame('Kab. Kudus', $home[0]['names']['regency']);
        $this->assertSame('desa', $home[0]['kind']);

        // A word of the district narrows the villages: "ploso karangtengah" is the one in Demak.
        $demak = $model->search('Desa Ploso Karangtengah');
        $this->assertSame('33.21.05.2001', $demak[0]['code']);

        // Districts are found too.
        $jati = $model->search('jati');
        $this->assertSame([3, 'Jati'], [$jati[0]['level'], $jati[0]['name']]);
        $this->assertSame('33.19.03', $jati[0]['code']);

        // Names written without the space: "getas" and "getas pejaten" both find "Getaspejaten".
        $this->assertContains('33.19.03.2008', array_column($model->search('getas'), 'code'));
        $this->assertSame('33.19.03.2008', $model->search('getas pejaten')[0]['code']);
    }
}
