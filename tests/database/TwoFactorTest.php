<?php

declare(strict_types=1);

use App\Libraries\TwoFactor;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use PragmaRX\Google2FA\Google2FA;

/**
 * @internal
 */
final class TwoFactorTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;
    private TwoFactor $twoFactor;
    private Google2FA $google2fa;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'siti', 'email' => 'siti@example.test', 'password' => 'Rahasia-Kuat-2027']));

        $this->userId    = (int) $users->getInsertID();
        $this->twoFactor = service('twoFactor', false);
        $this->google2fa = new Google2FA();
    }

    public function testSetupIsOffUntilConfirmed(): void
    {
        $setup = $this->twoFactor->beginSetup($this->userId, 'siti@example.test');

        $this->assertStringStartsWith('otpauth://totp/', $setup['uri']);
        $this->assertStringContainsString($setup['secret'], $setup['uri']);
        $this->assertFalse($this->twoFactor->isEnabled($this->userId));
    }

    public function testSecretIsStoredEncrypted(): void
    {
        $setup = $this->twoFactor->beginSetup($this->userId, 'siti@example.test');

        $stored = $this->db->table('user_two_factor')->where('user_id', $this->userId)->get()->getRowArray()['secret'];

        $this->assertStringNotContainsString($setup['secret'], $stored);
    }

    public function testWrongCodeDoesNotConfirm(): void
    {
        $this->twoFactor->beginSetup($this->userId, 'siti@example.test');

        $this->assertNull($this->twoFactor->confirmSetup($this->userId, '000000'));
        $this->assertFalse($this->twoFactor->isEnabled($this->userId));
    }

    public function testConfirmEnablesAndReturnsRecoveryCodes(): void
    {
        $codes = $this->enable();

        $this->assertTrue($this->twoFactor->isEnabled($this->userId));
        $this->assertCount(TwoFactor::RECOVERY_CODE_COUNT, $codes);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{5}-[A-Z2-9]{5}$/', $codes[0]);
    }

    public function testCodeUsedForConfirmationCannotBeReused(): void
    {
        $secret = $this->enable(returnSecret: true);

        $this->assertFalse($this->twoFactor->verify($this->userId, $this->google2fa->getCurrentOtp($secret)));
    }

    public function testNextTimeStepCodeIsAccepted(): void
    {
        $secret = $this->enable(returnSecret: true);
        $next   = $this->google2fa->oathTotp($secret, $this->google2fa->getTimestamp() + 1);

        $this->assertTrue($this->twoFactor->verify($this->userId, $next));
        $this->assertFalse($this->twoFactor->verify($this->userId, $next), 'replay must fail');
    }

    public function testRecoveryCodeWorksOnce(): void
    {
        $codes = $this->enable();

        $this->assertTrue($this->twoFactor->verify($this->userId, strtolower($codes[0])));
        $this->assertFalse($this->twoFactor->verify($this->userId, $codes[0]));
        $this->assertSame(TwoFactor::RECOVERY_CODE_COUNT - 1, $this->twoFactor->remainingRecoveryCodes($this->userId));
    }

    public function testDisable(): void
    {
        $this->enable();
        $this->twoFactor->disable($this->userId);

        $this->assertFalse($this->twoFactor->isEnabled($this->userId));
    }

    /**
     * @return list<string>|string Recovery codes, or the secret when $returnSecret is true.
     */
    private function enable(bool $returnSecret = false): array|string
    {
        $secret = $this->twoFactor->beginSetup($this->userId, 'siti@example.test')['secret'];
        $codes  = $this->twoFactor->confirmSetup($this->userId, $this->google2fa->getCurrentOtp($secret));

        $this->assertIsArray($codes);

        return $returnSecret ? $secret : $codes;
    }
}
