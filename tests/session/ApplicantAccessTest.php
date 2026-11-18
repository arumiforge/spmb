<?php

declare(strict_types=1);

use App\Libraries\ApplicantAccess;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Session grant for applicants without an account (D-008).
 *
 * @internal
 */
final class ApplicantAccessTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    public function testGrantCheckAndRevoke(): void
    {
        $access = new ApplicantAccess(service('session'), 30);

        $this->assertNull($access->id());
        $access->grant(7);
        $this->assertSame(7, $access->id());
        $this->assertTrue($access->allows(7));
        $this->assertFalse($access->allows(8));

        $access->revoke();
        $this->assertNull($access->id());
    }

    public function testExpiredGrantIsDropped(): void
    {
        $access = new ApplicantAccess(service('session'), 30);
        service('session')->set(ApplicantAccess::SESSION_KEY, ['id' => 7, 'expires' => time() - 1]);

        $this->assertNull($access->id());
        $this->assertNull(service('session')->get(ApplicantAccess::SESSION_KEY));
    }
}
