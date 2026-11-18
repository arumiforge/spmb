<?php

declare(strict_types=1);

use App\Libraries\ModuleRegistry;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Kartu\Services\CardPdf;
use Modules\Panduan\Libraries\GuideBuilder;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Models\TestSessionModel;
use Tests\Support\Tes\TesFixtures;

/**
 * Phase 3 foundation: schema, shared read helpers, the schedule on the card
 * and guide sections from Guide/<audience>/*.md.
 *
 * @internal
 */
final class TesFoundationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use TesFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        setting()->forget('Tes.scheduleVisible');
    }

    public function testLabelsAreIndonesian(): void
    {
        $this->assertSame('Sabtu, 12 Juni 2027', TestSchedule::dayLabel('2027-06-12'));
        $this->assertSame('07.30–09.30 WIB', TestSchedule::timeLabel('07:30:00', '09:30:00'));
        $this->assertSame(
            'Sabtu, 12 Juni 2027 · 07.30–09.30 WIB · Ruang A',
            TestSchedule::sessionLabel(['test_date' => '2027-06-12', 'starts_at' => '07:30:00', 'ends_at' => '09:30:00', 'room_name' => 'Ruang A']),
        );
    }

    public function testScheduleOfAnApplicant(): void
    {
        $applicant = $this->makeApplicant();
        $schedule  = new TestSchedule();

        $this->assertNull($schedule->forApplicant((int) $applicant['id']));
        $this->assertSame('Belum dijadwalkan', $schedule->cardLine((int) $applicant['id']));

        $session = $this->makeSession(['room_id' => $this->makeRoom(['name' => 'Lab Komputer'])]);
        $this->makeAssignment((int) $applicant['id'], $session, ['seat_number' => 3]);

        $row = $schedule->forApplicant((int) $applicant['id']);
        $this->assertSame($session, $row['session_id']);
        $this->assertSame('pending', $row['status']);
        $this->assertSame('Belum tes', $row['status_label']);
        $this->assertSame('Lab Komputer', $row['room']);
        $this->assertSame('Gedung A lantai 1', $row['location']);
        $this->assertSame('Sabtu, 12 Juni 2027 · 07.30–09.30 WIB · Lab Komputer', $row['label']);
        $this->assertSame($row['label'], $schedule->cardLine((int) $applicant['id']));
        $this->assertSame($row['label'], CardPdf::details($applicant)['testStatus'], 'the card shows the schedule');

        setting('Tes.scheduleVisible', false);
        $this->assertSame('Belum dijadwalkan', $schedule->cardLine((int) $applicant['id']), 'hidden until the committee shows it');
    }

    public function testSessionCountsLeaveOutCancelledAndDeletedApplicants(): void
    {
        $room    = $this->makeRoom();
        $early   = $this->makeSession(['room_id' => $room, 'starts_at' => '07:30:00', 'quota' => 3]);
        $late    = $this->makeSession(['room_id' => $room, 'starts_at' => '10:00:00', 'ends_at' => '12:00:00']);
        $other   = $this->makeSession(['room_id' => $room, 'academic_year' => '2026/2027']);
        $present = $this->makeApplicant();
        $absent  = $this->makeApplicant();
        $gone    = $this->makeApplicant(['status' => 'cancelled']);
        $deleted = $this->makeApplicant();

        $this->makeAssignment((int) $present['id'], $early, ['status' => 'present']);
        $this->makeAssignment((int) $absent['id'], $early, ['status' => 'absent']);
        $this->makeAssignment((int) $gone['id'], $early);
        $this->makeAssignment((int) $deleted['id'], $early, ['status' => 'rescheduled']);
        model(ApplicantModel::class, false)->delete($deleted['id']);

        $rows = model(TestSessionModel::class)->withStats(['academic_year' => '2027/2028']);

        $this->assertSame([$early, $late], array_map('intval', array_column($rows, 'id')), 'time order, one year only');
        $this->assertSame([2, 1, 1, 0, 1], [$rows[0]['filled'], $rows[0]['present'], $rows[0]['absent'], $rows[0]['pending'], $rows[0]['free']]);
        $this->assertSame([0, 20], [$rows[1]['filled'], $rows[1]['free']]);
        $this->assertNotContains($other, array_column($rows, 'id'));
        $this->assertSame(2, model(TestSessionModel::class)->findWithStats($early)['filled']);

        $names = array_column(model(TestAssignmentModel::class)->forSession($early), 'full_name');
        $this->assertSame([$present['full_name'], $absent['full_name']], $names);
    }

    public function testGuideSectionsFromAFolderFollowTheMainFile(): void
    {
        $registry = new ModuleRegistry(SUPPORTPATH . 'ModuleSets/guide-parts', [], []);
        $builder  = new GuideBuilder($registry, ['admin-sekolah' => ['title' => 'Admin', 'description' => '', 'group' => null, 'icon' => 'cog']]);

        $this->assertSame(
            ['admin-sekolah.md', '10-pertama.md', '20-kedua.md'],
            array_map('basename', $builder->files('admin-sekolah')),
        );
    }
}
