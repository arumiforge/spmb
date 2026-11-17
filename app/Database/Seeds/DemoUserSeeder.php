<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use RuntimeException;

/**
 * One demo account per staff role, for development and demos only.
 * Run through "php spark spmb:install --demo".
 */
class DemoUserSeeder extends Seeder
{
    public const DEFAULT_PASSWORD = 'Demo-SPMB-2027';

    /**
     * username => group
     */
    public const ACCOUNTS = [
        'admin'              => 'admin',
        'kepsek'             => 'kepsek',
        'panitia'            => 'panitia',
        'petugastes'         => 'petugas-tes',
        'petugasdaftarulang' => 'petugas-daftar-ulang',
        'petugasseragam'     => 'petugas-seragam',
    ];

    public function run(): void
    {
        $this->createAll(self::DEFAULT_PASSWORD);
    }

    /**
     * @return list<array{username: string, email: string, group: string}> Accounts created now
     */
    public function createAll(string $password): array
    {
        if (ENVIRONMENT === 'production') {
            throw new RuntimeException('Demo accounts are not allowed in production.');
        }

        $users   = model(UserModel::class);
        $groups  = config('AuthGroups')->groups;
        $created = [];

        foreach (self::ACCOUNTS as $username => $group) {
            if ($users->where('username', $username)->first() !== null) {
                continue;
            }

            $user = new User([
                'username'  => $username,
                'email'     => $username . '@demo.test',
                'password'  => $password,
                'full_name' => 'Demo ' . $groups[$group]['title'],
            ]);

            $users->save($user);

            $user = $users->findById($users->getInsertID());
            $user->addGroup($group);
            $user->activate();

            $created[] = ['username' => $username, 'email' => $username . '@demo.test', 'group' => $groups[$group]['title']];
        }

        return $created;
    }
}
