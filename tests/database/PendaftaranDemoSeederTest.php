<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Pendaftaran\Database\Seeds\DemoSeeder;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;

/**
 * Demo data for development (spmb:install --demo) is complete and idempotent.
 *
 * @internal
 */
final class PendaftaranDemoSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;

    public function testSeedsWavesAndApplicantsOnce(): void
    {
        $seeder = new DemoSeeder(config('Database'));
        $seeder->run();

        $this->assertCount(3, model(WaveModel::class)->forYear('2027/2028'));
        $this->assertSame('Gelombang 1', model(WaveModel::class)->current()['name']);
        $this->assertSame(30, model(ApplicantModel::class)->countAllResults());
        $this->assertGreaterThanOrEqual(2, model(ApplicantModel::class)->where('possible_duplicate', 1)->countAllResults());
        $this->assertGreaterThan(0, model(ApplicantModel::class)->where('method', 'online')->countAllResults());
        $this->assertGreaterThan(0, model(ApplicantModel::class)->where('method', 'offline')->countAllResults());
        $this->assertSame(0, model(ApplicantModel::class)->where('registered_at >', date('Y-m-d H:i:s'))->countAllResults());

        $seeder->run();
        $this->assertCount(3, model(WaveModel::class)->forYear('2027/2028'));
        $this->assertSame(30, model(ApplicantModel::class)->countAllResults());
    }
}
