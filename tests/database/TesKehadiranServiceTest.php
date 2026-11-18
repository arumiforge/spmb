<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Kartu\Services\CardTokenService;
use Modules\Tes\Libraries\AttendanceSheetPdf;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Models\TestSessionModel;
use Modules\Tes\Services\Attendance;
use Tests\Support\Tes\TesFixtures;

/**
 * Attendance service (FR-TEST-03, D-061): resolving cards and numbers,
 * recording Hadir / Tidak hadir / undo with one audit event each, and
 * marking the rest of an ended session absent.
 *
 * @internal
 */
final class TesKehadiranServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use TesFixtures;

    protected $namespace;
    protected $refresh = true;

    private function staffId(): int
    {
        return (int) $this->makeStaff('petugas-tes')->id;
    }

    private function assignment(int $applicantId): array
    {
        return db_connect()->table('test_assignments')->where('applicant_id', $applicantId)->get()->getRowArray();
    }

    public function testResolvesNumbersInAnyCase(): void
    {
        $applicant = $this->makeApplicant();
        $session   = $this->makeSession();
        $this->makeAssignment((int) $applicant['id'], $session);

        $found = (new Attendance())->resolve('  pmb-2027-0001 ');

        $this->assertSame((int) $applicant['id'], (int) $found['applicant']['id']);
        $this->assertSame($session, $found['schedule']['session_id']);
        $this->assertNull((new Attendance())->resolve('PMB-2027-9999'));
        $this->assertNull((new Attendance())->resolve(''));
    }

    public function testResolvesCardUrlsAndCodes(): void
    {
        $applicant = $this->makeApplicant();
        $token     = (new CardTokenService())->tokenFor((int) $applicant['id']);
        $service   = new Attendance();

        $this->assertSame((int) $applicant['id'], (int) $service->resolve(site_url('kartu/' . $token))['applicant']['id']);
        $this->assertSame((int) $applicant['id'], (int) $service->resolve($token)['applicant']['id']);
        $this->assertNull($service->resolve($token)['schedule']);
        $this->assertNull($service->resolve('https://contoh.test/kartu/' . str_repeat('x', 43)));
    }

    public function testRevokedCardIsRefusedWithAClearMessage(): void
    {
        $applicant = $this->makeApplicant();
        $cards     = new CardTokenService();
        $old       = $cards->tokenFor((int) $applicant['id']);
        $cards->renew((int) $applicant['id'], $this->staffId());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah tidak berlaku');
        (new Attendance())->resolve(site_url('kartu/' . $old));
    }

    public function testRecordsPresentAbsentAndUndo(): void
    {
        $applicant = $this->makeApplicant();
        $id        = (int) $applicant['id'];
        $session   = $this->makeSession();
        $this->makeAssignment($id, $session);
        $staff   = $this->staffId();
        $service = new Attendance();

        $result = $service->record($id, 'present', $staff);
        $this->assertTrue($result['changed']);
        $this->assertSame('pending', $result['previous']);
        $row = $this->assignment($id);
        $this->assertSame('present', $row['status']);
        $this->assertNotNull($row['attended_at']);
        $this->assertSame($staff, (int) $row['recorded_by']);

        // The same status again changes nothing and logs nothing.
        $this->assertFalse($service->record($id, 'present', $staff)['changed']);

        $service->record($id, 'absent', $staff);
        $row = $this->assignment($id);
        $this->assertSame('absent', $row['status']);
        $this->assertNull($row['attended_at']);

        $service->record($id, 'pending', $staff);
        $row = $this->assignment($id);
        $this->assertSame('pending', $row['status']);
        $this->assertNull($row['recorded_by']);

        $events = db_connect()->table('audit_logs')->where('event', 'test_attendance')->where('subject_id', (string) $id)->orderBy('id')->get()->getResultArray();
        $this->assertCount(3, $events);
        $this->assertSame(['status' => 'pending'], json_decode((string) $events[0]['old_values'], true));
        $this->assertStringContainsString('Belum tes → Hadir', $events[0]['description']);
        $this->assertSame(0, db_connect()->table('audit_logs')->where('subject_type', 'test_assignments')->where('event <>', 'created')->countAllResults());
    }

    public function testRecordRefusals(): void
    {
        $service = new Attendance();
        $staff   = $this->staffId();
        $free    = $this->makeApplicant();
        $gone    = $this->makeApplicant(['status' => 'cancelled']);
        $this->makeAssignment((int) $gone['id'], $this->makeSession());

        foreach ([
            [(int) $free['id'], 'present', 'belum punya jadwal'],
            [(int) $gone['id'], 'present', 'sudah dibatalkan'],
            [999, 'present', 'tidak ditemukan'],
            [(int) $free['id'], 'rescheduled', 'belum sesuai'],
        ] as [$id, $status, $message]) {
            try {
                $service->record($id, $status, $staff);
                $this->fail('Expected a refusal for ' . $status);
            } catch (DomainException $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }

    public function testMarkRemainingAbsentAfterTheSession(): void
    {
        $session = $this->makeSession(['test_date' => date('Y-m-d', strtotime('-1 day'))]);
        $ids     = [];

        foreach (['pending', 'rescheduled', 'present', 'absent'] as $status) {
            $applicant = $this->makeApplicant();
            $ids[]     = (int) $applicant['id'];
            $this->makeAssignment((int) $applicant['id'], $session, ['status' => $status]);
        }

        $cancelled = $this->makeApplicant(['status' => 'cancelled']);
        $this->makeAssignment((int) $cancelled['id'], $session);

        $staff = $this->staffId();
        $this->assertSame(2, (new Attendance())->markRemainingAbsent($session, $staff));
        $this->assertSame(['absent', 'absent', 'present', 'absent'], array_map(fn (int $id): string => $this->assignment($id)['status'], $ids));
        $this->assertSame('pending', $this->assignment((int) $cancelled['id'])['status']);
        $this->assertSame(2, db_connect()->table('audit_logs')->where('event', 'test_attendance')->countAllResults());
        $this->assertSame(0, (new Attendance())->markRemainingAbsent($session, $staff));
    }

    public function testMarkRemainingAbsentWaitsForTheEnd(): void
    {
        $session = $this->makeSession(['test_date' => date('Y-m-d', strtotime('+1 day'))]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('belum selesai');
        (new Attendance())->markRemainingAbsent($session, $this->staffId());
    }

    public function testSessionTimeHelpers(): void
    {
        $past   = ['test_date' => date('Y-m-d', strtotime('-1 day')), 'starts_at' => '07:30:00', 'ends_at' => '09:30:00'];
        $future = ['test_date' => date('Y-m-d', strtotime('+1 day')), 'starts_at' => '07:30:00', 'ends_at' => '09:30:00'];
        $now    = ['test_date' => date('Y-m-d'), 'starts_at' => '00:00:00', 'ends_at' => '23:59:59'];

        $this->assertTrue(Attendance::hasEnded($past));
        $this->assertFalse(Attendance::hasEnded($future));
        $this->assertFalse(Attendance::isRunning($future));
        $this->assertTrue(Attendance::isRunning($now));
    }

    public function testAttendanceSheetHasEveryParticipant(): void
    {
        $session = $this->makeSession(['label' => 'Sesi Pagi']);

        for ($i = 0; $i < 30; $i++) {
            $applicant = $this->makeApplicant(['gender' => $i % 3 === 0 ? 'L' : 'P', 'full_name' => 'Peserta Dengan Nama Yang Sangat Panjang Sekali Nomor ' . $i]);
            $this->makeAssignment((int) $applicant['id'], $session, ['seat_number' => $i + 1, 'status' => $i === 0 ? 'present' : 'pending']);
        }

        $pdf = (new AttendanceSheetPdf())->render(
            model(TestSessionModel::class)->findWithStats($session),
            model(TestAssignmentModel::class)->forSession($session),
        );

        $this->assertStringStartsWith('%PDF', $pdf);
        // 30 rows do not fit on one page: two pages.
        $this->assertSame(2, preg_match_all('#/Type /Page\b#', $pdf));

        $empty = (new AttendanceSheetPdf())->render(model(TestSessionModel::class)->findWithStats($this->makeSession()), []);
        $this->assertStringStartsWith('%PDF', $empty);
    }
}
