<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Modules\Statistik\Libraries\Angka;
use Modules\Statistik\Libraries\StatistikFilter;
use Modules\Statistik\Libraries\StatistikQuery;
use Modules\Statistik\Libraries\StatistikReport;
use Tests\Support\Pendaftaran\PendaftaranFixtures;

/**
 * Statistik (D-111): aggregates (location drill-down, origin schools, income
 * order, cross-tabs, filters), small-count masking, access, CSV download and
 * the module switch.
 *
 * @internal
 */
final class StatistikTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PendaftaranFixtures;

    protected $namespace;
    protected $refresh    = true;
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetVisitor();
        $this->resetServices();
    }

    protected function tearDown(): void
    {
        $this->forgetPendaftaranSettings();
        setting()->forget('Features.disabled');
        // Routes built while a module was off must not leak into the next test class.
        $this->resetServices();
        parent::tearDown();
    }

    /**
     * Inserts one applicant straight into the table (only what the statistics read).
     *
     * @param array<string, mixed> $overrides
     */
    private function applicant(array $overrides = []): void
    {
        $n = ++$this->sequence;
        db_connect()->table('applicants')->insert($overrides + [
            'registration_number' => 'TES-' . $n,
            'academic_year'       => (string) setting('Pendaftaran.academicYear'),
            'method'              => 'online',
            'status'              => 'registered',
            'document_status'     => 'incomplete',
            'full_name'           => 'Calon ' . $n,
            'name_key'            => 'calon ' . $n,
            'gender'              => 'L',
            'birth_place'         => 'Kudus',
            'birth_date'          => '2015-03-01',
            'parent_phone'        => '081234567890',
            'registered_at'       => '2026-11-10 09:00:00',
        ]);
    }

    /**
     * @param array<string, string> $get
     */
    private function query(array $get = []): StatistikQuery
    {
        return new StatistikQuery(StatistikFilter::fromQuery($get));
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, int> label => count, in order
     */
    private static function counts(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            $out[$row['label']] = $row['count'];
        }

        return $out;
    }

    /**
     * @param array<string, int> $cells
     *
     * @return array<string, int>
     */
    private static function sorted(array $cells): array
    {
        ksort($cells, SORT_STRING);

        return $cells;
    }

    private function school(string $name, string $district, string $regency, string $level = 'SD', ?string $ownership = 'negeri'): int
    {
        db_connect()->table('ref_sekolah')->insert(['name' => $name, 'level' => $level, 'ownership' => $ownership, 'province' => 'Jawa Tengah', 'regency' => $regency, 'district' => $district]);

        return (int) db_connect()->insertID();
    }

    private function seedPlaces(): void
    {
        foreach ([['Kudus', 'Jati', 'Getas Pejaten'], [' kudus ', 'JATI', 'getas pejaten '], ['Kudus', 'Jati', 'Ploso'], ['Kudus', 'Bae', 'Dersalam'], ['Pati', 'Juwana', 'Bakaran'], ['Pati', 'Juwana', 'Bakaran'], ['Pati', 'Juwana', 'Bakaran'], [null, null, null]] as [$regency, $district, $village]) {
            $this->applicant(['regency' => $regency, 'district' => $district, 'village' => $village]);
        }
    }

    public function testLocationDrillsDownFromRegencyToVillage(): void
    {
        $this->seedPlaces();

        $top = $this->query()->location();
        $this->assertSame('regency', $top['level']);
        $this->assertSame(['Kudus' => 4, 'Pati' => 3, '' => 1], self::counts($top['rows']), 'trimmed and case-insensitive; not filled in last');
        $this->assertSame('t:kudus', $top['rows'][0]['key']);
        $this->assertSame('', $top['rows'][2]['key'], 'an empty place cannot be opened');

        $regency = $this->query(['kab' => 't:kudus'])->location();
        $this->assertSame('district', $regency['level']);
        $this->assertSame('Kudus', $regency['regency']['label']);
        $this->assertSame(['jati' => 3, 'bae' => 1], array_change_key_case(self::counts($regency['rows'])));

        $district = $this->query(['kab' => 't:kudus', 'kec' => $regency['rows'][0]['key']])->location();
        $this->assertSame('village', $district['level']);
        $this->assertSame(['getas pejaten' => 2, 'ploso' => 1], array_change_key_case(self::counts($district['rows'])));

        $unknown = $this->query(['kab' => 't:tidak-ada'])->location();
        $this->assertSame('regency', $unknown['level'], 'a stale drill-down value falls back to the top level');
    }

    public function testLocationUsesRegionCodesWhenTheColumnExists(): void
    {
        // The region list (D-111) adds the code columns; add one here only when it is missing.
        $forge = Database::forge();
        $added = ! db_connect()->fieldExists('regency_code', 'applicants');

        if ($added) {
            $forge->addColumn('applicants', ['regency_code' => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true]]);
            db_connect()->resetDataCache();
        }

        try {
            $this->applicant(['regency' => 'KABUPATEN KUDUS', 'regency_code' => '33.19']);
            $this->applicant(['regency' => 'Kudus', 'regency_code' => '33.19']);
            $this->applicant(['regency' => 'Kudus']);

            $rows = $this->query()->location()['rows'];

            $this->assertSame([2, 1], array_column($rows, 'count'));
            $this->assertSame('33.19', $rows[0]['key']);
            $this->assertSame('t:kudus', $rows[1]['key'], 'rows without a code keep the text grouping');
            $this->assertSame('district', $this->query(['kab' => '33.19'])->location()['level']);
        } finally {
            if ($added) {
                $forge->dropColumn('applicants', 'regency_code');
                db_connect()->resetDataCache();
            }
        }
    }

    public function testOriginSchoolsByDistrictRegencyAndSchool(): void
    {
        $jati1 = $this->school('SD Negeri 1 Jati', 'Jati', 'Kab. Kudus');
        $jati2 = $this->school('MI NU Jati', 'Jati', 'Kab. Kudus', 'MI', 'swasta');
        $bae   = $this->school('SD Negeri 1 Bae', 'Bae', 'Kab. Kudus');
        $pati  = $this->school('MI Juwana', 'Juwana', 'Kab. Pati', 'MI', null);

        foreach ([$jati1, $jati1, $jati2, $bae, $pati] as $id) {
            $this->applicant(['previous_school_id' => $id]);
        }

        $this->applicant(['previous_school_name' => 'SD IT Contoh']);
        $this->applicant(['previous_school_name' => ' sd it contoh']);
        $this->applicant();

        $query = $this->query();

        $this->assertSame(['Jati, Kab. Kudus' => 3, 'Bae, Kab. Kudus' => 1, 'Juwana, Kab. Pati' => 1, '' => 1], self::counts(array_filter($query->schools('kecamatan'), static fn (array $r): bool => $r['key'] !== 'lain')));
        $this->assertSame(2, array_column($query->schools('kecamatan'), 'count', 'key')['lain'], 'typed-in schools form one group');
        $this->assertSame([4, 1, 2, 1], array_column($query->schools('kabupaten'), 'count'), 'Kab. Kudus, Kab. Pati, other, not filled in');

        $bySchool = $query->schools('nama');
        $this->assertSame(['SD IT Contoh' => 2, 'SD Negeri 1 Jati' => 2], self::counts(array_slice($bySchool, 0, 2)), 'typed names grouped case-insensitively; ties by name');
        $this->assertSame('lain', $bySchool[0]['note']);
        $this->assertSame('Jati, Kab. Kudus', $bySchool[1]['note']);

        $this->assertSame(['SD' => 3, 'MI' => 2, 'lain' => 2, '' => 1], array_column($query->schoolLevels(), 'count', 'key'));
        $this->assertSame(['negeri' => 3, 'swasta' => 1, 'lain' => 2, '?' => 1, '' => 1], array_column($query->schoolOwnership(), 'count', 'key'));
    }

    public function testIncomeFollowsTheListOrder(): void
    {
        setting('Pendaftaran.incomeOptions', ['Kecil', 'Sedang', 'Besar']);

        foreach (['Besar', 'Besar', 'besar ', 'Sedang', 'Kecil', 'Lama', null] as $income) {
            $this->applicant(['father_income' => $income]);
        }

        $this->assertSame(['Kecil' => 1, 'Sedang' => 1, 'Besar' => 3, 'Lama' => 1, '' => 1], self::counts($this->query()->incomeCounts('father_income')));
    }

    public function testCrossTabsOfIncome(): void
    {
        setting('Pendaftaran.incomeOptions', ['Kecil', 'Besar']);
        $first  = $this->makeWave(['name' => 'Gelombang 1', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30']);
        $second = $this->makeWave(['name' => 'Gelombang 2', 'starts_on' => '2026-12-01', 'ends_on' => '2026-12-31']);

        foreach ([[$second, 'Besar', 'Jati'], [$first, 'Kecil', 'Jati'], [$first, 'Kecil', 'Bae'], [$first, 'Besar', 'Bae'], [null, null, 'Gebog'], [$second, 'Kecil', 'Dawe']] as [$wave, $income, $district]) {
            $this->applicant(['wave_id' => $wave, 'father_income' => $income, 'regency' => 'Kudus', 'district' => $district]);
        }

        $byWave = $this->query()->incomeByWave('father_income');
        $this->assertSame(['Gelombang 1', 'Gelombang 2', ''], array_column($byWave['groups'], 'label'), 'waves by start date, no wave last');
        $this->assertSame(['kecil', 'besar', ''], array_column($byWave['income'], 'key'));
        $this->assertSame(['besar' => 1, 'kecil' => 2], self::sorted($byWave['cells'][(string) $first]));
        $this->assertSame(['' => 1], $byWave['cells']['0']);

        $byDistrict = $this->query()->incomeByDistrict('father_income', 2);
        $this->assertSame(['Bae', 'Jati', ''], array_column($byDistrict['groups'], 'label'), 'the two biggest districts, the rest folded');
        $this->assertSame('lain', $byDistrict['groups'][2]['key']);
        $this->assertSame(2, $byDistrict['groups'][2]['count']);
        $this->assertSame(['' => 1, 'kecil' => 1], self::sorted($byDistrict['cells']['lain']));
    }

    public function testFiltersByYearWaveAndStatus(): void
    {
        $wave = $this->makeWave();
        $this->applicant(['wave_id' => $wave]);
        $this->applicant(['wave_id' => $wave, 'status' => 'verified', 'method' => 'offline', 'document_status' => 'complete']);
        $this->applicant(['status' => 'cancelled']);
        $this->applicant(['academic_year' => '2026/2027']);
        $this->applicant(['deleted_at' => '2026-11-11 10:00:00']);

        $this->assertSame(['total' => 2, 'online' => 1, 'offline' => 1, 'complete' => 1], $this->query()->totals(), 'cancelled, other years and deleted rows left out');
        $this->assertSame(3, $this->query(['status' => 'semua'])->totals()['total']);
        $this->assertSame(1, $this->query(['status' => 'cancelled'])->totals()['total']);
        $this->assertSame(1, $this->query(['status' => 'verified'])->totals()['total']);
        $this->assertSame(2, $this->query(['gelombang' => (string) $wave])->totals()['total']);
        $this->assertSame(0, $this->query(['gelombang' => 'tanpa'])->totals()['total']);
        $this->assertSame(1, $this->query(['tahun' => '2026/2027'])->totals()['total']);
        $this->assertSame(2, $this->query(['tahun' => '1999/2000', 'gelombang' => '999', 'status' => 'x'])->totals()['total'], 'unknown values fall back to the defaults');
    }

    public function testAgesAndTimeline(): void
    {
        $this->applicant(['birth_date' => '2014-11-11', 'registered_at' => '2026-11-10 08:00:00']);
        $this->applicant(['birth_date' => '2014-11-10', 'registered_at' => '2026-11-10 09:00:00']);
        $this->applicant(['birth_date' => '2009-01-01', 'registered_at' => '2026-11-13 09:00:00']);

        $this->assertSame([11 => 1, 12 => 1, 17 => 1], $this->query()->ages());

        $days = $this->query()->timeline('hari');
        $this->assertSame(['2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13'], array_column($days, 'date'));
        $this->assertSame([2, 0, 0, 1], array_column($days, 'count'));
        $this->assertSame([['date' => '2026-11-09', 'count' => 3]], $this->query()->timeline('minggu'), 'weeks start on Monday');

        $report = new StatistikReport(StatistikFilter::fromQuery([]), $this->query(), site_url('admin/statistik'));
        $ages   = $report->section('murid')['cards'][2];
        $this->assertSame(['Di bawah 11 tahun' => 0, '11 tahun' => 1, '12 tahun' => 1, '13 tahun' => 0, '14 tahun' => 0, '15 tahun atau lebih' => 1], self::counts($ages['rows']));
    }

    public function testSmallCountsAreHidden(): void
    {
        $this->assertSame('0', Angka::show(0));
        $this->assertSame('< 3', Angka::show(1));
        $this->assertSame('< 3', Angka::show(2));
        $this->assertSame('3', Angka::show(3));
        $this->assertSame('1.250', Angka::show(1250));
        $this->assertSame('', Angka::percent(2, 10));
        $this->assertSame('30%', Angka::percent(3, 10));
        $this->assertSame('2,5%', Angka::percent(3, 120));

        foreach (['L', 'L', 'L', 'L', 'P', 'P'] as $gender) {
            $this->applicant(['gender' => $gender]);
        }

        $page = $this->actingAs($this->makeUser('admin'))->get('admin/statistik');
        $page->assertOK();
        $body = (string) $page->getBody();
        $this->assertMatchesRegularExpression('#Perempuan</span>.*?<td[^>]*>&lt; 3</td>\s*<td[^>]*></td>#s', $body);
        // The HTML minifier may switch the attribute to single quotes.
        preg_match_all('/data-chart=(?:"([^"]+)"|\'([^\']+)\')/', $body, $charts);
        $charts = array_filter([...$charts[1], ...$charts[2]]);
        $this->assertNotEmpty($charts);

        foreach ($charts as $json) {
            $this->assertSame(3, json_decode(html_entity_decode($json, ENT_QUOTES), true)['spmb']['mask'] ?? null, 'chart tooltips hide small numbers too');
        }

        $this->assertStringNotContainsString('Calon 1', $body, 'no names on the page');
    }

    public function testAccessByPermission(): void
    {
        $this->get('admin/statistik')->assertRedirectTo(site_url('masuk'));

        $panitia = $this->makeUser('panitia');
        $this->actingAs($panitia)->get('admin/statistik')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($panitia)->get('admin/statistik/unduh/asal')->assertRedirectTo(site_url('admin/akses-ditolak'));

        $this->seedPlaces();
        $page = $this->actingAs($this->makeUser('admin'))->get('admin/statistik?kab=t%3Akudus');
        $page->assertOK();
        $page->assertSee('Statistik pendaftar');
        $page->assertSee('Tempat tinggal');
        $page->assertSee('Semua kabupaten/kota');
        $page->assertSee('Penghasilan per kecamatan');
        $page->assertSee('Unduh CSV');
        $this->assertStringContainsString('href="' . site_url('admin/statistik') . '?kab=t%3Akudus&amp;kec=', (string) $page->getBody(), 'districts link to their villages');
        $this->assertStringContainsString('href="' . site_url('admin/statistik/unduh/asal') . '?kab=t%3Akudus"', (string) $page->getBody(), 'the download keeps the view');
        $this->assertStringContainsString('data-chart=', (string) $page->getBody());
    }

    public function testEmptyYearShowsTheEmptyState(): void
    {
        $page = $this->actingAs($this->makeUser('admin'))->get('admin/statistik');

        $page->assertOK();
        $page->assertSee('Belum ada pendaftar');
        $this->assertStringNotContainsString('data-chart=', (string) $page->getBody());
    }

    public function testCsvDownload(): void
    {
        $this->seedPlaces();
        $admin = $this->makeUser('admin');

        $result = $this->actingAs($admin)->get('admin/statistik/unduh/asal?kab=t%3Akudus');
        $result->assertOK();
        $this->assertStringStartsWith('text/csv', $result->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment; filename="statistik-asal-', $result->response()->getHeaderLine('Content-Disposition'));

        $csv = (string) $result->response()->getBody();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Tempat tinggal', $csv);
        $this->assertStringContainsString('Kecamatan,Jumlah,Persen', $csv);
        $this->assertMatchesRegularExpression('/^jati,3,75%$/mi', $csv);
        $this->assertStringContainsString('Bae,"< 3",', $csv, 'small counts stay hidden in the file');
        $this->assertStringNotContainsString('Calon 1', $csv);

        foreach (StatistikReport::SECTIONS as $section) {
            $this->actingAs($admin)->get('admin/statistik/unduh/' . $section)->assertOK();
        }

        $this->actingAs($admin)->get('admin/statistik/unduh/lain')->assertStatus(404);
    }

    public function testHiddenWhileTheModuleIsOff(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get('admin')->assertSee('admin/statistik');

        foreach (['Statistik', 'Pendaftaran'] as $module) {
            setting('Features.disabled', [$module]);
            $this->resetServices();
            $this->actingAs($admin)->get('admin/statistik')->assertStatus(404);
            $this->actingAs($admin)->get('admin')->assertDontSee('admin/statistik');
        }
    }
}
