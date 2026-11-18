<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

/**
 * Staff accounts with only some permissions, for "can see but not change"
 * and "denied" checks. Only three roles exist (D-063) and both staff roles
 * can change data, so these accounts have no role and get their permissions
 * one by one (Shield user permissions).
 */
trait ViewerAccounts
{
    /**
     * @param list<string> $permissions default: dashboard and applicant list, read only
     */
    protected function viewer(string $username, array $permissions = ['dasbor.view', 'pendaftar.view']): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => $username, 'email' => $username . '@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => ucfirst($username) . ' Test']));
        $user = $users->findById($users->getInsertID());
        $user->activate();

        if ($permissions !== []) {
            $user->addPermission(...$permissions);
        }

        return $user;
    }
}
