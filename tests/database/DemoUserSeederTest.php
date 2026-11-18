<?php

declare(strict_types=1);

use App\Database\Seeds\DemoUserSeeder;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class DemoUserSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;

    public function testCreatesOneAccountPerRoleAndIsIdempotent(): void
    {
        $seeder = new DemoUserSeeder(config('Database'));

        $this->assertCount(count(DemoUserSeeder::ACCOUNTS), $seeder->createAll('Demo-SPMB-2027'));
        $this->assertSame([], $seeder->createAll('Demo-SPMB-2027'));

        $user = model(UserModel::class, false)->where('username', 'panitia')->first();

        $this->assertTrue($user->inGroup('panitia'));
        $this->assertTrue($user->can('tes.kehadiran'));
        $this->assertFalse($user->can('pendaftar.delete'));
        $this->assertTrue($user->isActivated());
    }
}
