<?php

declare(strict_types=1);

namespace Tests\Support\Referensi;

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Referensi\Models\SchoolModel;

/**
 * Helpers for the Referensi tests: staff accounts, schools and applicants
 * written through the shared models.
 */
trait ReferensiFixtures
{
    private int $applicantSeq = 0;

    protected static function fixture(string $name): string
    {
        return __DIR__ . '/fixtures/' . $name;
    }

    protected function makeStaff(string $username, ?string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => $username, 'email' => $username . '@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => ucfirst($username) . ' Test']));
        $user = $users->findById($users->getInsertID());

        if ($group !== null) {
            $user->addGroup($group);
        }

        $user->activate();

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function makeSchool(array $data): int
    {
        return (int) model(SchoolModel::class, false)->insert($data + ['level' => 'SD', 'province' => 'Jawa Tengah', 'regency' => 'Kab. Kudus', 'source' => 'manual']);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function makeApplicant(array $data = []): int
    {
        $n = ++$this->applicantSeq;

        return (int) model(ApplicantModel::class, false)->insert($data + [
            'registration_number' => sprintf('PMB-2027-%04d', $n),
            'academic_year'       => '2027/2028',
            'method'              => 'online',
            'full_name'           => 'Anak Uji ' . $n,
            'name_key'            => 'anak uji ' . $n,
            'gender'              => 'L',
            'birth_place'         => 'Kudus',
            'birth_date'          => '2015-05-17',
            'parent_phone'        => '0812345678' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'registered_at'       => '2027-01-05 08:00:00',
        ]);
    }
}
