<?php

declare(strict_types=1);

namespace Tests\Support\Pendaftaran;

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use Config\Services;
use Modules\Pendaftaran\Models\WaveModel;

/**
 * Shared helpers for the Pendaftaran tests: staff users, waves and a valid
 * applicant form submission.
 */
trait PendaftaranFixtures
{
    /**
     * Pendaftaran.* settings the tests may change; forgotten in tearDown.
     *
     * @var list<string>
     */
    protected array $pendaftaranSettings = [
        'academicYear', 'numberFormat', 'numberScope', 'onlineOpen', 'requiredFields', 'hiddenFields', 'statusCheckAttempts',
    ];

    /**
     * Drops the signed-in user and applicant grant left by an earlier test
     * (the database is refreshed, so that user no longer exists).
     */
    protected function resetVisitor(): void
    {
        $_SESSION = [];
        Services::resetSingle('auth');
        Services::resetSingle('applicantAccess');
    }

    protected function forgetPendaftaranSettings(): void
    {
        foreach ($this->pendaftaranSettings as $key) {
            setting()->forget('Pendaftaran.' . $key);
        }
    }

    protected function makeUser(string $group): User
    {
        $users = model(UserModel::class, false);
        $name  = 'u' . str_replace('-', '', $group) . bin2hex(random_bytes(3));
        $users->save(new User(['username' => $name, 'email' => $name . '@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => 'Petugas ' . $group]));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }

    /**
     * A wave of the configured academic year; open today unless dates are given.
     *
     * @param array<string, mixed> $overrides
     */
    protected function makeWave(array $overrides = []): int
    {
        return (int) model(WaveModel::class)->insert($overrides + [
            'academic_year' => (string) setting('Pendaftaran.academicYear'),
            'name'          => 'Gelombang 1',
            'starts_on'     => date('Y-m-d', strtotime('-10 days')),
            'ends_on'       => date('Y-m-d', strtotime('+10 days')),
            'is_active'     => 1,
        ]);
    }

    /**
     * Form input of a valid applicant (default settings: NISN required).
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function applicantInput(array $overrides = []): array
    {
        return $overrides + [
            'full_name'            => 'Ahmad Fauzi',
            'gender'               => 'L',
            'birth_place'          => 'Kudus',
            'birth_date'           => date('Y', strtotime('-12 years')) . '-05-17',
            'nisn'                 => '0123456789',
            'nik'                  => '3319011705150001',
            'religion'             => 'Islam',
            'address'              => 'Jl. Contoh No. 1, RT 01/RW 02',
            'village'              => 'Getas Pejaten',
            'district'             => 'Jati',
            'regency'              => 'Kudus',
            'province'             => 'Jawa Tengah',
            'father_name'          => 'Budi Santoso',
            'mother_name'          => 'Siti Aminah',
            'parent_phone'         => '0812-3456-7890',
            'parent_email'         => 'orangtua@example.test',
            'previous_school_name' => 'SD Negeri 1 Contoh',
            'graduation_year'      => date('Y', strtotime('+1 year')),
        ];
    }
}
