<?php

declare(strict_types=1);

namespace Tests\Support\Kartu;

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Referensi\Models\SchoolModel;

/**
 * Applicants, waves and staff for Kartu and Situs tests, inserted through the
 * shared models (the Pendaftaran pages may not exist yet).
 */
trait KartuFixtures
{
    private int $sequence = 0;

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeWave(array $overrides = []): int
    {
        return (int) model(WaveModel::class)->insert($overrides + [
            'academic_year' => '2027/2028',
            'name'          => 'Gelombang 1',
            'starts_on'     => '2027-01-04',
            'ends_on'       => '2027-02-27',
            'is_active'     => 1,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed> applicants row
     */
    private function makeApplicant(array $overrides = []): array
    {
        $this->sequence++;

        if (! array_key_exists('wave_id', $overrides)) {
            $overrides['wave_id'] = $this->makeWave(['name' => 'Gelombang Uji ' . $this->sequence]);
        }

        if (! array_key_exists('previous_school_id', $overrides) && ! array_key_exists('previous_school_name', $overrides)) {
            $overrides['previous_school_id'] = model(SchoolModel::class)->insert(['npsn' => '2031900' . $this->sequence, 'name' => 'SD Negeri ' . $this->sequence . ' Contoh', 'level' => 'SD', 'source' => 'manual']);
        }

        $id = model(ApplicantModel::class)->insert($overrides + [
            'registration_number' => sprintf('PMB-2027-%04d', $this->sequence),
            'academic_year'       => '2027/2028',
            'method'              => 'online',
            'full_name'           => 'Ahmad Fauzi ' . $this->sequence,
            'name_key'            => 'ahmad fauzi ' . $this->sequence,
            'gender'              => 'L',
            'birth_place'         => 'Kudus',
            'birth_date'          => '2015-05-17',
            'nik'                 => '3319011705150001',
            'nisn'                => '0151234567',
            'address'             => 'Jl. Melati 7',
            'parent_phone'        => '081234567890',
            'registered_at'       => '2027-01-05 08:00:00',
        ]);

        return model(ApplicantModel::class)->find($id);
    }

    private function makeUser(string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'u' . str_replace('-', '', $group), 'email' => $group . '@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }
}
