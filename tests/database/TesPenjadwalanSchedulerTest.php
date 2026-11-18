<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Models\TestHolidayModel;
use Modules\Tes\Services\Scheduler;
use Modules\Tes\Services\SchedulerException;
use Tests\Support\Tes\TesFixtures;

/**
 * Services\Scheduler (package B): automatic allocation order (FR-TEST-02,
 * D-060), giving, moving and removing a seat, refusals, the change log
 * (FR-TEST-04) and audit events.
 *
 * @internal
 */
final class TesPenjadwalanSchedulerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use TesFixtures;

    protected $namespace;
    protected $refresh = true;
    private Scheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new Scheduler();
    }

    protected function tearDown(): void
    {
        foreach (['eligibleStatuses', 'requireCompleteDocuments'] as $key) {
            setting()->forget('Tes.' . $key);
        }

        parent::tearDown();
    }

    /**
     * @return array<int, array{session: int, seat: int, status: string}> applicant id => seat
     */
    private function seats(): array
    {
        $out = [];

        foreach (db_connect()->table('test_assignments')->orderBy('applicant_id')->get()->getResultArray() as $row) {
            $out[(int) $row['applicant_id']] = ['session' => (int) $row['session_id'], 'seat' => (int) $row['seat_number'], 'status' => $row['status']];
        }

        return $out;
    }

    private function allocate(array $options = []): array
    {
        return $this->scheduler->allocate($options + ['from_date' => '2027-06-01']);
    }

    // --- Allocation order ------------------------------------------------

    public function testPlacesInRegistrationOrderIntoSessionsInTimeOrder(): void
    {
        // Created out of order: the later session first, two rooms at the same time.
        $late   = $this->makeSession(['test_date' => '2027-06-14', 'quota' => 5]);
        $roomB  = $this->makeRoom(['name' => 'Ruang B', 'sort_order' => 2]);
        $roomA  = $this->makeRoom(['name' => 'Ruang A', 'sort_order' => 1]);
        $second = $this->makeSession(['room_id' => $roomB, 'quota' => 2]);
        $first  = $this->makeSession(['room_id' => $roomA, 'quota' => 2]);
        $later  = $this->makeSession(['room_id' => $roomA, 'starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'quota' => 1]);

        $ids = [];

        for ($i = 0; $i < 7; $i++) {
            $ids[] = (int) $this->makeApplicant()['id']; // online, offline, online, … (equal)
        }

        $report = $this->allocate();

        $this->assertSame(7, $report['placed']);
        $this->assertSame(0, $report['unplaced']);
        $seats = $this->seats();
        $this->assertSame(['session' => $first, 'seat' => 1, 'status' => 'pending'], $seats[$ids[0]]);
        $this->assertSame(['session' => $first, 'seat' => 2, 'status' => 'pending'], $seats[$ids[1]]);
        $this->assertSame(['session' => $second, 'seat' => 1, 'status' => 'pending'], $seats[$ids[2]]);
        $this->assertSame(['session' => $second, 'seat' => 2, 'status' => 'pending'], $seats[$ids[3]]);
        $this->assertSame(['session' => $later, 'seat' => 1, 'status' => 'pending'], $seats[$ids[4]]);
        $this->assertSame(['session' => $late, 'seat' => 1, 'status' => 'pending'], $seats[$ids[5]]);
        $this->assertSame(['session' => $late, 'seat' => 2, 'status' => 'pending'], $seats[$ids[6]]);

        $this->assertSame([$first, $second, $later, $late], array_column($report['sessions'], 'session_id'));
        $this->assertSame(['PMB-2027-0001', 'PMB-2027-0002', 2], [$report['sessions'][0]['first_number'], $report['sessions'][0]['last_number'], $report['sessions'][0]['count']]);
        $this->assertSame('Sabtu, 12 Juni 2027 · 07.30–09.30 WIB · Ruang A', $report['sessions'][0]['label']);
    }

    public function testOnlineAndOfflineAreTreatedTheSame(): void
    {
        $session = $this->makeSession(['quota' => 2]);
        $offline = $this->makeApplicant(['method' => 'offline']);
        $online  = $this->makeApplicant(['method' => 'online']);
        $late    = $this->makeApplicant(['method' => 'online']);

        $report = $this->allocate();

        $seats = $this->seats();
        $this->assertSame(1, $seats[(int) $offline['id']]['seat']);
        $this->assertSame(2, $seats[(int) $online['id']]['seat']);
        $this->assertArrayNotHasKey((int) $late['id'], $seats);
        $this->assertSame([$late['registration_number']], $report['unplacedNumbers']);
        $this->assertSame($session, $report['sessions'][0]['session_id']);
    }

    public function testWaveSessionsTakeOnlyTheirWave(): void
    {
        $waveOne = $this->makeWave();
        $waveTwo = $this->makeWave(['name' => 'Gelombang 2', 'starts_on' => '2027-03-01', 'ends_on' => '2027-04-30']);
        $forTwo  = $this->makeSession(['wave_id' => $waveTwo, 'quota' => 5]);
        $forAll  = $this->makeSession(['test_date' => '2027-06-13', 'quota' => 5]);

        $one    = $this->makeApplicant(['wave_id' => $waveOne]);
        $two    = $this->makeApplicant(['wave_id' => $waveTwo]);
        $noWave = $this->makeApplicant();

        $this->allocate();

        $seats = $this->seats();
        $this->assertSame($forAll, $seats[(int) $one['id']]['session']);
        $this->assertSame($forTwo, $seats[(int) $two['id']]['session']);
        $this->assertSame($forAll, $seats[(int) $noWave['id']]['session']);
    }

    public function testOneWaveOnlyPlacesThatWave(): void
    {
        $waveOne = $this->makeWave();
        $waveTwo = $this->makeWave(['name' => 'Gelombang 2', 'starts_on' => '2027-03-01', 'ends_on' => '2027-04-30']);
        $this->makeSession(['wave_id' => $waveTwo]);
        $forOne = $this->makeSession(['wave_id' => $waveOne, 'test_date' => '2027-06-13']);
        $one    = $this->makeApplicant(['wave_id' => $waveOne]);
        $two    = $this->makeApplicant(['wave_id' => $waveTwo]);

        $report = $this->allocate(['wave_id' => $waveOne]);

        $this->assertSame(1, $report['placed']);
        $this->assertSame([(int) $one['id'] => ['session' => $forOne, 'seat' => 1, 'status' => 'pending']], $this->seats());
        $this->assertArrayNotHasKey((int) $two['id'], $this->seats());
    }

    public function testSkipsHolidaysInactiveSessionsAndDatesBeforeTheStart(): void
    {
        $holiday  = $this->makeSession(['test_date' => '2027-06-12']);
        $inactive = $this->makeSession(['test_date' => '2027-06-13', 'is_active' => 0]);
        $early    = $this->makeSession(['test_date' => '2027-06-05']);
        $open     = $this->makeSession(['test_date' => '2027-06-14']);
        model(TestHolidayModel::class, false)->insert(['date' => '2027-06-12', 'note' => 'Libur']);
        $applicant = $this->makeApplicant();

        $report = $this->allocate(['from_date' => '2027-06-10']);

        $this->assertSame(1, $report['placed']);
        $this->assertSame($open, $this->seats()[(int) $applicant['id']]['session']);
        $this->assertNotContains($holiday, array_column($report['sessions'], 'session_id'));
        $this->assertNotContains($inactive, array_column($report['sessions'], 'session_id'));
        $this->assertNotContains($early, array_column($report['sessions'], 'session_id'));
    }

    public function testFullSessionsAreSkippedAndSeatNumbersContinue(): void
    {
        $full    = $this->makeSession(['quota' => 1]);
        $partial = $this->makeSession(['test_date' => '2027-06-13', 'quota' => 3]);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $full, ['seat_number' => 1]);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $partial, ['seat_number' => 4]);
        $new = $this->makeApplicant();

        $this->allocate();

        $this->assertSame(['session' => $partial, 'seat' => 5, 'status' => 'pending'], $this->seats()[(int) $new['id']]);
    }

    public function testRunningAgainMovesNobody(): void
    {
        $this->makeSession(['quota' => 2]);
        $this->makeApplicant();
        $this->makeApplicant();
        $this->allocate();
        $before = $this->seats();

        $report = $this->allocate();

        $this->assertSame(0, $report['placed']);
        $this->assertSame([], $report['sessions']);
        $this->assertSame($before, $this->seats());
        $this->assertSame(2, db_connect()->table('test_schedule_changes')->countAllResults());
        $this->assertSame(1, db_connect()->table('audit_logs')->where('event', 'test_allocated')->countAllResults());
    }

    public function testCancelledApplicantFreesTheSeat(): void
    {
        $session   = $this->makeSession(['quota' => 2]);
        $first     = $this->makeApplicant();
        $cancelled = $this->makeApplicant();
        $this->allocate();
        model(ApplicantModel::class, false)->update((int) $cancelled['id'], ['status' => 'cancelled']);
        $new = $this->makeApplicant();

        $report = $this->allocate();

        $this->assertSame(1, $report['placed']);
        $seats = $this->seats();
        $this->assertSame(['session' => $session, 'seat' => 3, 'status' => 'pending'], $seats[(int) $new['id']]);
        $this->assertArrayHasKey((int) $cancelled['id'], $seats, 'the cancelled row stays for the record');
        $this->assertSame(1, $seats[(int) $first['id']]['seat']);
    }

    public function testOnlyEligibleApplicantsGetASeat(): void
    {
        $this->makeSession(['quota' => 10]);
        $registered = $this->makeApplicant();
        $verified   = $this->makeApplicant(['status' => 'verified']);
        $this->makeApplicant(['status' => 'cancelled']);
        $deleted = $this->makeApplicant();
        model(ApplicantModel::class, false)->delete((int) $deleted['id']);
        $this->makeApplicant(['academic_year' => '2028/2029']);

        $this->allocate();
        $this->assertSame([(int) $registered['id'], (int) $verified['id']], array_keys($this->seats()));

        setting('Tes.eligibleStatuses', ['verified']);
        $onlyVerified = $this->makeApplicant(['status' => 'verified']);
        $this->makeApplicant();
        $this->allocate();
        $this->assertArrayHasKey((int) $onlyVerified['id'], $this->seats());
        $this->assertCount(3, $this->seats());
    }

    public function testCompleteDocumentsWhenRequired(): void
    {
        setting('Tes.requireCompleteDocuments', true);
        $this->makeSession();
        $complete = $this->makeApplicant(['document_status' => 'complete']);
        $this->makeApplicant(['document_status' => 'pending']);

        $report = $this->allocate();

        $this->assertSame(1, $report['placed']);
        $this->assertSame([(int) $complete['id']], array_keys($this->seats()));
    }

    public function testDryRunWritesNothing(): void
    {
        $this->makeSession(['quota' => 1]);
        $this->makeApplicant();
        $this->makeApplicant();

        $report = $this->scheduler->allocate(['from_date' => '2027-06-01'], true);

        $this->assertSame(1, $report['placed']);
        $this->assertSame(1, $report['unplaced']);
        $this->assertSame(2, $report['eligible']);
        $this->assertSame(['PMB-2027-0002'], $report['unplacedNumbers']);
        $this->assertSame(0, db_connect()->table('test_assignments')->countAllResults());
        $this->assertSame(0, db_connect()->table('test_schedule_changes')->countAllResults());
        $this->assertSame(0, db_connect()->table('audit_logs')->where('event', 'test_allocated')->countAllResults());
    }

    public function testAllocationIsLoggedAsSystemWithOneAuditEvent(): void
    {
        $session = $this->makeSession();
        $staff   = $this->makeStaff('admin');
        $a       = $this->makeApplicant();
        $this->makeApplicant();

        $this->scheduler->allocate(['from_date' => '2027-06-01', 'staff_id' => (int) $staff->id]);

        $change = db_connect()->table('test_schedule_changes')->where('applicant_id', $a['id'])->get()->getRowArray();
        $this->assertSame('allocated', $change['kind']);
        $this->assertNull($change['from_session_id']);
        $this->assertSame((string) $session, (string) $change['to_session_id']);
        $this->assertNull($change['changed_by']);
        $this->assertNull(model(TestAssignmentModel::class)->forApplicant((int) $a['id'])['assigned_by']);

        $audit = db_connect()->table('audit_logs')->where('event', 'test_allocated')->get()->getResultArray();
        $this->assertCount(1, $audit);
        $this->assertSame((string) $staff->id, (string) $audit[0]['user_id']);
        $this->assertSame(2, json_decode($audit[0]['new_values'], true)['placed']);
        $this->assertSame(0, db_connect()->table('audit_logs')->where('subject_type', 'test_assignments')->countAllResults(), 'no generic row events');
    }

    public function testDefaultStartIsTomorrow(): void
    {
        $today    = $this->makeSession(['test_date' => date('Y-m-d'), 'academic_year' => '2027/2028']);
        $tomorrow = $this->makeSession(['test_date' => Scheduler::tomorrow()]);
        $a        = $this->makeApplicant();

        $this->scheduler->allocate(['academic_year' => '2027/2028']);

        $this->assertSame($tomorrow, $this->seats()[(int) $a['id']]['session']);
        $this->assertNotSame($today, $tomorrow);
    }

    // --- Assign ------------------------------------------------------------

    public function testAssignGivesTheFirstSeat(): void
    {
        $session = $this->makeSession();
        $this->makeAssignment((int) $this->makeApplicant()['id'], $session, ['seat_number' => 7]);
        $a     = $this->makeApplicant();
        $staff = $this->makeStaff('panitia');

        $row = $this->scheduler->assign((int) $a['id'], $session, (int) $staff->id, '  Daftar susulan ');

        $this->assertSame(8, (int) $row['seat_number']);
        $this->assertSame('pending', $row['status']);
        $this->assertSame((string) $staff->id, (string) $row['assigned_by']);
        $this->assertSame('2027-06-12', $row['test_date']);

        $change = db_connect()->table('test_schedule_changes')->get()->getRowArray();
        $this->assertSame(['assigned', 'Daftar susulan', (string) $staff->id], [$change['kind'], $change['reason'], (string) $change['changed_by']]);
        $audit = db_connect()->table('audit_logs')->where('event', 'test_assigned')->get()->getRowArray();
        $this->assertSame(['applicants', (string) $a['id']], [$audit['subject_type'], $audit['subject_id']]);
        $this->assertStringContainsString('PMB-2027-0002', $audit['description']);
    }

    public function testAssignRefusals(): void
    {
        $staff     = (int) $this->makeStaff('admin')->id;
        $session   = $this->makeSession(['quota' => 1]);
        $scheduled = $this->makeApplicant();
        $this->scheduler->assign((int) $scheduled['id'], $session, $staff);

        $cases = [
            'alreadyScheduled'   => [(int) $scheduled['id'], $this->makeSession(['test_date' => '2027-06-13'])],
            'sessionFull'        => [(int) $this->makeApplicant()['id'], $session],
            'applicantCancelled' => [(int) $this->makeApplicant(['status' => 'cancelled'])['id'], $this->makeSession(['test_date' => '2027-06-14'])],
            'sessionInactive'    => [(int) $this->makeApplicant()['id'], $this->makeSession(['is_active' => 0])],
            'otherYear'          => [(int) $this->makeApplicant(['academic_year' => '2028/2029'])['id'], $this->makeSession(['test_date' => '2027-06-15'])],
            'otherWave'          => [(int) $this->makeApplicant()['id'], $this->makeSession(['wave_id' => $this->makeWave()])],
            'past'               => [(int) $this->makeApplicant()['id'], $this->makeSession(['test_date' => '2026-01-10', 'academic_year' => '2027/2028'])],
            'sessionMissing'     => [(int) $this->makeApplicant()['id'], 999999],
            'applicantMissing'   => [999999, $this->makeSession(['test_date' => '2027-06-16'])],
        ];

        model(TestHolidayModel::class, false)->insert(['date' => '2027-06-20']);
        $cases['holiday'] = [(int) $this->makeApplicant()['id'], $this->makeSession(['test_date' => '2027-06-20'])];

        foreach ($cases as $key => [$applicantId, $sessionId]) {
            try {
                $this->scheduler->assign($applicantId, $sessionId, $staff);
                $this->fail('Expected refusal: ' . $key);
            } catch (SchedulerException $e) {
                $this->assertSame(lang('TesPenjadwalan.err.' . $key), $e->getMessage(), $key);
            }
        }

        $this->assertSame(1, db_connect()->table('test_assignments')->countAllResults());
        $this->assertSame(1, db_connect()->table('test_schedule_changes')->countAllResults());
    }

    public function testAssignOverQuotaWhenConfirmed(): void
    {
        $session = $this->makeSession(['quota' => 1]);
        $staff   = (int) $this->makeStaff('panitia')->id;
        $this->scheduler->assign((int) $this->makeApplicant()['id'], $session, $staff);

        $row = $this->scheduler->assign((int) $this->makeApplicant()['id'], $session, $staff, null, true);

        $this->assertSame(2, (int) $row['seat_number']);
    }

    // --- Move --------------------------------------------------------------

    public function testMoveReschedulesAndClearsAttendance(): void
    {
        $staff = $this->makeStaff('panitia');
        $from  = $this->makeSession();
        $to    = $this->makeSession(['test_date' => '2027-06-13']);
        $a     = $this->makeApplicant();
        $this->makeAssignment((int) $a['id'], $from, ['seat_number' => 1, 'status' => 'absent', 'attended_at' => '2027-06-12 08:00:00', 'recorded_by' => $staff->id]);

        $row = $this->scheduler->move((int) $a['id'], $to, (int) $staff->id, 'Sakit, pindah hari');

        $this->assertSame((string) $to, (string) $row['session_id']);
        $this->assertSame('rescheduled', $row['status']);
        $this->assertNull($row['attended_at']);
        $this->assertNull($row['recorded_by']);
        $this->assertSame(1, (int) $row['seat_number']);

        $change = db_connect()->table('test_schedule_changes')->get()->getRowArray();
        $this->assertSame(['moved', (string) $from, (string) $to, 'Sakit, pindah hari'], [$change['kind'], (string) $change['from_session_id'], (string) $change['to_session_id'], $change['reason']]);
        $audit = db_connect()->table('audit_logs')->where('event', 'test_moved')->get()->getRowArray();
        $this->assertSame((string) $from, (string) json_decode($audit['old_values'], true)['session_id']);
        $this->assertSame('rescheduled', json_decode($audit['new_values'], true)['status']);
        $this->assertStringContainsString('→', $audit['description']);
    }

    public function testMoveRefusals(): void
    {
        $petugas = (int) $this->makeStaff('panitia')->id;
        $from    = $this->makeSession();
        $full    = $this->makeSession(['test_date' => '2027-06-13', 'quota' => 1]);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $full, ['seat_number' => 1]);
        $a = $this->makeApplicant();
        $this->makeAssignment((int) $a['id'], $from, ['seat_number' => 1]);
        $past = $this->makeSession(['test_date' => '2026-01-10']);

        $cases = [
            'reason'          => fn () => $this->scheduler->move((int) $a['id'], $full, $petugas, ' ok '),
            'sameSession'     => fn () => $this->scheduler->move((int) $a['id'], $from, $petugas, 'Salah sesi'),
            'sessionFullMove' => fn () => $this->scheduler->move((int) $a['id'], $full, $petugas, 'Minta pindah'),
            'past'            => fn () => $this->scheduler->move((int) $a['id'], $past, $petugas, 'Koreksi data'),
            'notScheduled'    => fn () => $this->scheduler->move((int) $this->makeApplicant()['id'], $full, $petugas, 'Minta pindah'),
        ];

        foreach ($cases as $key => $call) {
            try {
                $call();
                $this->fail('Expected refusal: ' . $key);
            } catch (SchedulerException $e) {
                $this->assertSame(lang('TesPenjadwalan.err.' . $key, [Scheduler::MIN_REASON]), $e->getMessage(), $key);
            }
        }

        $this->assertSame((string) $from, (string) model(TestAssignmentModel::class)->forApplicant((int) $a['id'])['session_id']);
        $this->assertSame(0, db_connect()->table('test_schedule_changes')->countAllResults());
    }

    public function testMoveOverQuotaAndIntoThePastForTesAtur(): void
    {
        $admin = (int) $this->makeStaff('admin')->id;
        $from  = $this->makeSession();
        $past  = $this->makeSession(['test_date' => '2026-01-10', 'quota' => 1]);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $past, ['seat_number' => 1]);
        $a = $this->makeApplicant();
        $this->makeAssignment((int) $a['id'], $from, ['seat_number' => 1]);

        $row = $this->scheduler->move((int) $a['id'], $past, $admin, 'Koreksi: ikut tes susulan', true);

        $this->assertSame((string) $past, (string) $row['session_id']);
        $this->assertSame(2, (int) $row['seat_number']);
    }

    // --- Remove ------------------------------------------------------------

    public function testRemoveFreesTheSeatAndKeepsTheLog(): void
    {
        $staff   = (int) $this->makeStaff('admin')->id;
        $session = $this->makeSession();
        $a       = $this->makeApplicant();
        $this->scheduler->assign((int) $a['id'], $session, $staff);

        $this->scheduler->remove((int) $a['id'], $staff, 'Mengundurkan diri');

        $this->assertNull(model(TestAssignmentModel::class)->forApplicant((int) $a['id']));
        $kinds = db_connect()->table('test_schedule_changes')->orderBy('id')->get()->getResultArray();
        $this->assertSame(['assigned', 'removed'], array_column($kinds, 'kind'));
        $this->assertNull($kinds[1]['to_session_id']);
        $this->assertSame(1, db_connect()->table('audit_logs')->where('event', 'test_removed')->where('subject_id', (string) $a['id'])->countAllResults());
    }

    public function testRemoveRefusals(): void
    {
        $staff   = (int) $this->makeStaff('admin')->id;
        $session = $this->makeSession();
        $present = $this->makeApplicant();
        $this->makeAssignment((int) $present['id'], $session, ['status' => 'present']);

        foreach ([
            'alreadyPresent' => [(int) $present['id'], 'Salah catat'],
            'notScheduled'   => [(int) $this->makeApplicant()['id'], 'Salah catat'],
            'reason'         => [(int) $present['id'], ''],
        ] as $key => [$id, $reason]) {
            try {
                $this->scheduler->remove($id, $staff, $reason);
                $this->fail('Expected refusal: ' . $key);
            } catch (SchedulerException $e) {
                $this->assertSame(lang('TesPenjadwalan.err.' . $key, [Scheduler::MIN_REASON]), $e->getMessage(), $key);
            }
        }
    }

    public function testCancelledApplicantCanStillBeRemoved(): void
    {
        $staff = (int) $this->makeStaff('admin')->id;
        $a     = $this->makeApplicant();
        $this->makeAssignment((int) $a['id'], $this->makeSession());
        model(ApplicantModel::class, false)->update((int) $a['id'], ['status' => 'cancelled']);

        $this->scheduler->remove((int) $a['id'], $staff, 'Pendaftaran dibatalkan');

        $this->assertSame(0, db_connect()->table('test_assignments')->countAllResults());
    }

    // --- Read helpers ------------------------------------------------------

    public function testEligibleCountsAndChoices(): void
    {
        $wave = $this->makeWave();
        $this->makeApplicant(['wave_id' => $wave]);
        $this->makeApplicant(['wave_id' => $wave]);
        $noWave = $this->makeApplicant();

        $this->assertEqualsCanonicalizing([(string) $wave => 2, '' => 1], $this->scheduler->eligibleCounts('2027/2028'));

        $forWave = $this->makeSession(['wave_id' => $wave]);
        $forAll  = $this->makeSession(['test_date' => '2027-06-13']);
        $this->makeSession(['test_date' => '2027-06-14', 'is_active' => 0]);

        $this->assertSame([$forAll], array_map('intval', array_column($this->scheduler->choicesFor($noWave), 'id')));
        $this->assertSame([$forWave, $forAll], array_map('intval', array_column($this->scheduler->choicesFor(model(ApplicantModel::class)->where('wave_id', $wave)->first()), 'id')));
    }
}
