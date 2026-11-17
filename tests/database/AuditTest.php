<?php

declare(strict_types=1);

use App\Libraries\AuditLogger;
use App\Models\AuditLogModel;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class AuditTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;

    private function createUser(string $username = 'budi'): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User([
            'username'  => $username,
            'email'     => $username . '@example.test',
            'password'  => 'Rahasia-Kuat-2027',
            'full_name' => 'Budi Santoso',
        ]));

        return $users->findById($users->getInsertID());
    }

    public function testInsertIsLogged(): void
    {
        $user = $this->createUser();

        $log = model(AuditLogModel::class, false)->forSubject('users', $user->id);

        $this->assertCount(1, $log);
        $this->assertSame('created', $log[0]['event']);
        $this->assertSame('Budi Santoso', json_decode($log[0]['new_values'], true)['full_name']);
    }

    public function testUpdateLogsOnlyChangedColumns(): void
    {
        $user  = $this->createUser();
        $users = model(UserModel::class, false);

        $users->update($user->id, ['full_name' => 'Budi S.', 'username' => 'budi']);

        $log = model(AuditLogModel::class, false)->forSubject('users', $user->id)[0];

        $this->assertSame('updated', $log['event']);
        $this->assertSame(['full_name' => 'Budi Santoso'], json_decode($log['old_values'], true));
        $this->assertSame(['full_name' => 'Budi S.'], json_decode($log['new_values'], true));
    }

    public function testUpdateWithoutChangesIsNotLogged(): void
    {
        $user = $this->createUser();

        model(UserModel::class, false)->update($user->id, ['full_name' => 'Budi Santoso']);

        $this->assertCount(1, model(AuditLogModel::class, false)->forSubject('users', $user->id));
    }

    public function testSoftDeleteIsLoggedWithOldValues(): void
    {
        $user = $this->createUser();

        model(UserModel::class, false)->delete($user->id);

        $log = model(AuditLogModel::class, false)->forSubject('users', $user->id)[0];

        $this->assertSame('deleted', $log['event']);
        $this->assertSame('budi', json_decode($log['old_values'], true)['username']);
    }

    public function testSensitiveValuesAreRedacted(): void
    {
        service('audit')->log('custom', 'users', 1, ['password' => 'lama'], ['password' => 'baru', 'phone' => '0812']);

        $row = model(AuditLogModel::class, false)->orderBy('id', 'DESC')->first();

        $this->assertSame(AuditLogger::REDACTED, json_decode($row['old_values'], true)['password']);
        // MySQL JSON columns do not preserve key order.
        $new = json_decode($row['new_values'], true);
        ksort($new);
        $this->assertSame(['password' => AuditLogger::REDACTED, 'phone' => '0812'], $new);
    }
}
