<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Services\ApplicantService;
use Modules\Pendaftaran\Services\ApplicantStats;
use Tests\Support\Pendaftaran\PendaftaranFixtures;
use Tests\Support\ViewerAccounts;

/**
 * Dashboard registration counts (total, today, per wave, method, documents).
 *
 * @internal
 */
final class DashboardTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PendaftaranFixtures;
    use ViewerAccounts;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetVisitor();
    }

    protected function tearDown(): void
    {
        $this->forgetPendaftaranSettings();
        $this->resetVisitor();
        parent::tearDown();
    }

    private function register(string $method, array $overrides = []): array
    {
        $data = ApplicantForm::clean($this->applicantInput($overrides), [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE]);

        return (new ApplicantService())->register($data, $method)['applicant'];
    }

    public function testEmptyDashboard(): void
    {
        $result = $this->actingAs($this->makeUser('panitia'))->get('admin');

        $result->assertOK();
        $result->assertSee('Total Pendaftar');
        $result->assertSee('Belum ada pendaftar');
    }

    public function testCountsAndPermissions(): void
    {
        $this->makeWave(['name' => 'Gelombang Satu', 'quota' => 10]);
        $a = $this->register('online');
        $this->register('offline', ['nik' => '3319000000000002', 'nisn' => '2222222222', 'full_name' => 'Budi']);
        $c = $this->register('offline', ['nik' => '3319000000000003', 'nisn' => '3333333333', 'full_name' => 'Citra']);
        (new ApplicantService())->changeStatus((int) $c['id'], 'cancelled');
        db_connect()->table('applicants')->where('id', $a['id'])->update(['document_status' => 'complete']);

        $stats = (new ApplicantStats())->summary();
        $this->assertSame(2, $stats['total']);
        $this->assertSame(2, $stats['today']);
        $this->assertSame(1, $stats['online']);
        $this->assertSame(1, $stats['offline']);
        $this->assertSame(1, $stats['complete']);
        $this->assertSame([['name' => 'Gelombang Satu', 'starts_on' => date('Y-m-d', strtotime('-10 days')), 'ends_on' => date('Y-m-d', strtotime('+10 days')), 'quota' => 10, 'count' => 2]], $stats['waves']);
        $this->assertSame(0, $stats['noWave']);

        $page = $this->actingAs($this->viewer('lihat'))->get('admin');
        $page->assertSee('Pendaftar per gelombang');
        $page->assertSee('2 / 10 kuota');
    }
}
