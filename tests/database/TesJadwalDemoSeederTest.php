<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Tes\Database\Seeds\DemoSeeder;
use Modules\Tes\Models\TestHolidayModel;
use Modules\Tes\Models\TestRoomModel;
use Modules\Tes\Models\TestSessionModel;

/**
 * Package A — demo rooms, holidays and sessions are created once.
 *
 * @internal
 */
final class TesJadwalDemoSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;

    public function testSeederIsIdempotent(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(4, model(TestRoomModel::class)->countAllResults());
        $this->assertSame(2, model(TestHolidayModel::class)->countAllResults());
        $this->assertSame(6 * 2 * 4, model(TestSessionModel::class)->where('academic_year', setting('Pendaftaran.academicYear'))->countAllResults());

        $days = DemoSeeder::testDays();
        $this->assertSame(date('Y-m-d', strtotime('+30 days')) <= $days[0], true);
        $this->assertNotContains('7', array_map(static fn (string $d): string => date('N', strtotime($d)), $days));

        foreach (model(TestHolidayModel::class)->findAll() as $holiday) {
            $this->assertSame(0, model(TestSessionModel::class)->countOnDate($holiday['date']), 'no session on a holiday');
        }
    }

    public function testSixMondayToSaturdayDates(): void
    {
        // 2027-05-13 + 30 days = Saturday 2027-06-12; Sunday is skipped.
        $this->assertSame(['2027-06-12', '2027-06-14', '2027-06-15', '2027-06-16', '2027-06-17', '2027-06-18'], DemoSeeder::testDays('2027-05-13'));
    }
}
