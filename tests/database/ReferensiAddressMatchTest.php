<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Referensi\Libraries\AddressBackfill;
use Modules\Referensi\Libraries\RegionMatcher;
use Tests\Support\Referensi\ReferensiFixtures;
use Tests\Support\Referensi\RegionFixtures;

/**
 * Matching typed addresses to the region list (D-111): RegionMatcher examples
 * and the one-time backfill of earlier applicants (AddressBackfill).
 *
 * @internal
 */
final class ReferensiAddressMatchTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use ReferensiFixtures;
    use RegionFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetRegionSettings();
        $this->loadRegions();
    }

    protected function tearDown(): void
    {
        $this->forgetRegionSettings();
        parent::tearDown();
    }

    /**
     * @param array<string, string> $address
     *
     * @return array{level: int, codes: array<string, string|null>, names: array<string, string|null>, unmatched: list<string>}
     */
    private static function match(array $address, bool $similar = true): array
    {
        return (new RegionMatcher())->match($address, $similar);
    }

    public function testPrefixesCaseAndSpacesAreIgnored(): void
    {
        $result = self::match(['village' => 'Ds.  GETAS PEJATEN', 'district' => 'Kec. Jati', 'regency' => 'Kudus', 'province' => 'Prov. Jateng']);

        $this->assertSame(4, $result['level']);
        $this->assertSame([], $result['unmatched']);
        $this->assertSame(['province' => '33', 'regency' => '33.19', 'district' => '33.19.03', 'village' => '33.19.03.2008'], $result['codes']);
        $this->assertSame(['province' => 'Jawa Tengah', 'regency' => 'Kab. Kudus', 'district' => 'Jati', 'village' => 'Getaspejaten'], $result['names']);
    }

    public function testATypoIsAcceptedOnlyInsideTheKnownDistrict(): void
    {
        $typo = ['village' => 'Plosso', 'district' => 'Jatii', 'regency' => 'Kab Kudus', 'province' => ''];

        $similar = self::match($typo);
        $this->assertSame('33.19.03.2006', $similar['codes']['village']);
        $this->assertSame('Ploso', $similar['names']['village']);

        $exact = self::match($typo, false);
        $this->assertSame(2, $exact['level'], 'without guesses only the regency is sure');
        $this->assertSame(['district', 'village'], $exact['unmatched']);
    }

    public function testAnUnknownVillageKeepsTheLevelsAbove(): void
    {
        $result = self::match(['village' => 'Desa Contoh 1', 'district' => 'Bae', 'regency' => 'Kudus', 'province' => 'Jawa Tengah']);

        $this->assertSame(3, $result['level']);
        $this->assertSame('33.19.07', $result['codes']['district']);
        $this->assertNull($result['codes']['village']);
        $this->assertSame(['village'], $result['unmatched']);
    }

    public function testRegencyAndCityOfTheSameName(): void
    {
        $this->assertSame('33.22', self::match(['regency' => 'Semarang'])['codes']['regency'], 'a bare name means the regency');
        $this->assertSame('33.74', self::match(['regency' => 'Kota Semarang'])['codes']['regency']);
        $this->assertSame('33.22', self::match(['regency' => 'Kabupaten Semarang'])['codes']['regency']);
    }

    public function testADistrictNamedKotaIsNotTakenForACity(): void
    {
        $result = self::match(['district' => 'Kota Kudus', 'regency' => 'Kudus']);

        $this->assertSame('33.19.02', $result['codes']['district']);
        $this->assertSame('Kota Kudus', $result['names']['district']);
    }

    public function testAmbiguousNamesAreNotGuessed(): void
    {
        // Two districts called Jati in Jawa Tengah (Blora and Kudus): only the province is sure.
        $result = self::match(['district' => 'Jati', 'province' => 'Jawa Tengah']);
        $this->assertSame(1, $result['level']);
        $this->assertSame(['district'], $result['unmatched']);

        // The village settles it: only Jati in Kudus has a Ploso.
        $this->assertSame('33.19.03.2006', self::match(['village' => 'Ploso', 'district' => 'Jati'])['codes']['village']);

        $none = self::match(['village' => 'Atlantis', 'district' => 'Negeri Antah']);
        $this->assertSame(0, $none['level']);
        $this->assertSame(['district', 'village'], $none['unmatched']);
        $this->assertSame(0, self::match([])['level']);
    }

    public function testBackfillPreviewsThenSavesAndIsSafeToRepeat(): void
    {
        $full    = $this->makeApplicant(['village' => 'Ds. Getas Pejaten', 'district' => 'Kec. Jati', 'regency' => 'Kudus', 'province' => 'Jateng']);
        $partial = $this->makeApplicant(['village' => 'Desa Contoh', 'district' => 'Bae', 'regency' => 'Kudus', 'province' => 'Jawa Tengah']);
        $none    = $this->makeApplicant(['village' => 'Antah', 'district' => 'Berantah', 'regency' => 'Entah', 'province' => 'Tidak Tahu']);
        $empty   = $this->makeApplicant(['village' => null, 'district' => null, 'regency' => null, 'province' => null]);

        $backfill = new AddressBackfill();
        $this->assertSame(3, $backfill->pendingCount(), 'the applicant without any address text is left out');

        $preview = $backfill->run(true);
        $this->assertSame([3, 1, 1, 1, 2], [$preview['checked'], $preview['full'], $preview['partial'], $preview['none'], $preview['changed']]);
        $this->assertNull(model(ApplicantModel::class, false)->find($full)['village_code'], 'a preview writes nothing');
        $this->assertSame('Ds. Getas Pejaten, Kec. Jati, Kudus, Jateng', $preview['rows'][0]['typed']);
        $this->assertSame('Getaspejaten, Jati, Kab. Kudus, Jawa Tengah', $preview['rows'][0]['result']);

        $saved = $backfill->run(false);
        $this->assertSame(2, $saved['changed']);

        $model = model(ApplicantModel::class, false);
        $row   = $model->find($full);
        $this->assertSame(['33', '33.19', '33.19.03', '33.19.03.2008'], [$row['province_code'], $row['regency_code'], $row['district_code'], $row['village_code']]);
        $this->assertSame(['Jawa Tengah', 'Kab. Kudus', 'Jati', 'Getaspejaten'], [$row['province'], $row['regency'], $row['district'], $row['village']]);

        $row = $model->find($partial);
        $this->assertSame(['33.19.07', null, 'Desa Contoh', 'Bae'], [$row['district_code'], $row['village_code'], $row['village'], $row['district']]);

        $row = $model->find($none);
        $this->assertSame(['Antah', null], [$row['village'], $row['province_code']]);
        $this->assertNull($model->find($empty)['province_code']);

        // Each change is in the audit log, plus one summary.
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'applicants', 'subject_id' => (string) $full]);
        $this->seeInDatabase('audit_logs', ['event' => 'addresses_matched']);

        // Again: the complete row is skipped, the others are checked but nothing changes.
        $again = (new AddressBackfill())->run(false);
        $this->assertSame([2, 0], [$again['checked'], $again['changed']]);
    }
}
