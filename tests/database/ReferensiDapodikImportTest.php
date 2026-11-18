<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Referensi\Libraries\DapodikClient;
use Modules\Referensi\Libraries\DapodikImport;
use Modules\Referensi\Libraries\SchoolImporter;
use Modules\Referensi\Models\SchoolModel;
use Tests\Support\Referensi\ReferensiFixtures;

/**
 * Dapodik importer against recorded fixtures (tests/_support/Referensi/fixtures).
 *
 * @internal
 */
final class ReferensiDapodikImportTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use ReferensiFixtures;

    protected $namespace;
    protected $refresh = true;

    /**
     * @var list<string>
     */
    private array $calls = [];

    private function client(): DapodikClient
    {
        $this->calls = [];

        return new DapodikClient(function (string $path, array $query): mixed {
            $this->calls[] = $path . '?' . http_build_query($query);
            $this->assertSame('SD', $query['jenjang']);

            $file = match ($path) {
                'provinsi'         => 'dapodik-provinsi.json',
                'kabupaten'        => 'dapodik-kabupaten.json',
                'kecamatan'        => 'dapodik-kecamatan-' . $query['kode_kabupaten'] . '.json',
                'kecamatan/school' => 'dapodik-sekolah-' . $query['kode_kecamatan'] . '.json',
            };

            return json_decode((string) file_get_contents(self::fixture($file)), true);
        });
    }

    public function testClientReadsEveryResponseShape(): void
    {
        $client = $this->client();

        $this->assertSame(['code' => '030000', 'name' => 'Prov. Jawa Tengah'], $client->provinces('SD')[1]);
        $this->assertSame('Kab. Kudus', $client->regencies('030000', 'SD')[0]['name']);
        $this->assertCount(2, $client->districts('031900', 'SD'));
        $this->assertCount(3, $client->schools('031901', 'SD'));
        $this->assertSame('kabupaten?kode_provinsi=030000&jenjang=SD', $this->calls[1]);
    }

    public function testUnexpectedAnswerIsAnError(): void
    {
        $this->expectException(RuntimeException::class);

        (new DapodikClient(static fn (): array => ['message' => 'Unauthorized']))->provinces('SD');
    }

    public function testRunImportsSchoolsOfTheConfiguredProvince(): void
    {
        $lines  = [];
        $report = (new DapodikImport($this->client(), new SchoolImporter()))
            ->run(['Jawa Tengah'], ['SD', 'MI'], null, static function (string $line) use (&$lines): void {
                $lines[] = $line;
            });

        $this->assertSame(4, $report['created'], 'SD and SDLB only: the SLB and the nameless row are left out');
        $this->assertSame(3, $report['districts']);
        $this->assertSame(['MI'], $report['skippedLevels']);
        $this->assertCount(3, $lines);

        $school = model(SchoolModel::class, false)->where('npsn', '20319002')->first();
        $this->assertSame(
            ['SD ISLAM AL HIDAYAH', 'SD', 'swasta', 'Jawa Tengah', 'Kab. Kudus', 'Jati', 'dapodik', '1'],
            [$school['name'], $school['level'], $school['ownership'], $school['province'], $school['regency'], $school['district'], $school['source'], (string) $school['is_active']],
        );
        $this->assertNull(model(SchoolModel::class, false)->where('npsn', '20319003')->first(), 'SLB is not a configured level');

        $again = (new DapodikImport($this->client(), new SchoolImporter()))->run(['Jawa Tengah'], ['SD']);
        $this->assertSame([0, 4], [$again['created'], $again['unchanged']]);
    }

    public function testRegencyFilterAndDryRun(): void
    {
        $report = (new DapodikImport($this->client(), new SchoolImporter()))->run(['Jawa Tengah'], ['SD'], 'jepara', null, true);

        $this->assertSame(1, $report['created']);
        $this->assertSame(1, $report['districts']);
        $this->assertNotContains('kecamatan?kode_kabupaten=031900&jenjang=SD', $this->calls);
        $this->assertSame(0, model(SchoolModel::class, false)->countAllResults());
    }

    public function testUnknownProvinceStops(): void
    {
        $this->expectException(RuntimeException::class);

        (new DapodikImport($this->client(), new SchoolImporter()))->run(['Papua Pegunungan'], ['SD']);
    }
}
