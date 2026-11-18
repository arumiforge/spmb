<?php

declare(strict_types=1);

namespace Tests\Support\Tes;

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Models\TestRoomModel;
use Modules\Tes\Models\TestSessionModel;

/**
 * Waves, applicants, rooms, sessions, assignments and staff for Tes tests,
 * inserted through the shared models (phase 3 foundation). Shared by all
 * phase 3 work packages: add new helpers in your own trait, do not change these.
 */
trait TesFixtures
{
    private int $tesSequence = 0;

    /**
     * @param array<string, mixed> $overrides
     */
    protected function makeWave(array $overrides = []): int
    {
        return (int) model(WaveModel::class, false)->insert($overrides + [
            'academic_year' => '2027/2028',
            'name'          => 'Gelombang 1',
            'starts_on'     => '2027-01-04',
            'ends_on'       => '2027-02-27',
            'is_active'     => 1,
        ]);
    }

    /**
     * Applicant of 2027/2028; numbers PMB-2027-0001, -0002, ... in insert order.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed> applicants row
     */
    protected function makeApplicant(array $overrides = []): array
    {
        $this->tesSequence++;

        $id = model(ApplicantModel::class, false)->insert($overrides + [
            'registration_number'  => sprintf('PMB-2027-%04d', $this->tesSequence),
            'academic_year'        => '2027/2028',
            'wave_id'              => null,
            'method'               => $this->tesSequence % 2 === 0 ? 'offline' : 'online',
            'full_name'            => 'Siti Aminah ' . $this->tesSequence,
            'name_key'             => 'siti aminah ' . $this->tesSequence,
            'gender'               => 'P',
            'birth_place'          => 'Kudus',
            'birth_date'           => '2015-03-09',
            'address'              => 'Jl. Mawar 3',
            'parent_phone'         => '081234567890',
            'previous_school_name' => 'SD Negeri 1 Contoh',
            'registered_at'        => '2027-01-05 08:00:00',
        ]);

        return model(ApplicantModel::class, false)->find($id);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function makeRoom(array $overrides = []): int
    {
        return (int) model(TestRoomModel::class, false)->insert($overrides + [
            'name'       => 'Ruang ' . ($this->tesSequence + 1),
            'capacity'   => 20,
            'location'   => 'Gedung A lantai 1',
            'sort_order' => 1,
            'is_active'  => 1,
        ]);
    }

    /**
     * Session on 2027-06-12 (a Saturday) 07:30–09:30 in a new room unless room_id is given.
     *
     * @param array<string, mixed> $overrides
     */
    protected function makeSession(array $overrides = []): int
    {
        $overrides['room_id'] ??= $this->makeRoom();

        return (int) model(TestSessionModel::class, false)->insert($overrides + [
            'academic_year' => '2027/2028',
            'wave_id'       => null,
            'test_date'     => '2027-06-12',
            'starts_at'     => '07:30:00',
            'ends_at'       => '09:30:00',
            'quota'         => 20,
            'label'         => 'Sesi 1',
            'is_active'     => 1,
        ]);
    }

    /**
     * Raw assignment for tests of read-side code. Real code assigns through
     * Services\Scheduler (package B) so the change is logged.
     *
     * @param array<string, mixed> $overrides
     */
    protected function makeAssignment(int $applicantId, int $sessionId, array $overrides = []): int
    {
        return (int) model(TestAssignmentModel::class, false)->insert($overrides + [
            'applicant_id' => $applicantId,
            'session_id'   => $sessionId,
            'status'       => TestAssignmentModel::PENDING,
        ]);
    }

    protected function makeStaff(string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'tes' . str_replace('-', '', $group), 'email' => 'tes-' . $group . '@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }
}
