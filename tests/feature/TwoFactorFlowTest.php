<?php

declare(strict_types=1);

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-step verification screens: setup on the profile page, the login step,
 * role enforcement and the forced password change.
 *
 * @internal
 */
final class TwoFactorFlowTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const PASSWORD = 'Rahasia-Kuat-2027';

    protected $namespace;
    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'budi', 'email' => 'budi@example.test', 'password' => self::PASSWORD, 'full_name' => 'Budi Santoso']));
        $this->user = $users->findById($users->getInsertID());
        $this->user->addGroup('panitia');
        $this->user->activate();
    }

    protected function tearDown(): void
    {
        setting()->forget('TwoFactor.requiredRoles');
        parent::tearDown();
    }

    private function currentCode(): string
    {
        $setup = service('twoFactor')->pendingSetup((int) $this->user->id, 'budi@example.test');

        return (new Google2FA())->getCurrentOtp($setup['secret']);
    }

    /**
     * Turns 2FA on directly and returns [secret, recovery codes].
     *
     * @return array{0: string, 1: list<string>}
     */
    private function enableTwoFactor(): array
    {
        $twoFactor = service('twoFactor');
        $setup     = $twoFactor->beginSetup((int) $this->user->id, 'budi@example.test');
        $codes     = $twoFactor->confirmSetup((int) $this->user->id, (new Google2FA())->getCurrentOtp($setup['secret']));

        // Allow the current time step again, as if the setup happened a minute ago.
        $this->db->table('user_two_factor')->where('user_id', $this->user->id)->update(['last_used_timestep' => 0]);

        return [$setup['secret'], $codes];
    }

    public function testSetupPageShowsQrCodeAndManualKey(): void
    {
        $result = $this->actingAs($this->user)->get('admin/profil/verifikasi-dua-langkah');

        $result->assertOK();
        $this->assertStringContainsString('src="data:image/svg+xml;base64,', $result->getBody());
        $result->assertSee('Aktifkan');
    }

    public function testReloadingSetupKeepsTheSameKey(): void
    {
        $first  = service('twoFactor')->pendingSetup((int) $this->user->id, 'x');
        $second = service('twoFactor')->pendingSetup((int) $this->user->id, 'x');

        $this->assertSame($first['secret'], $second['secret']);
    }

    public function testCorrectCodeEnablesAndShowsRecoveryCodesOnce(): void
    {
        $code   = $this->currentCode();
        $result = $this->actingAs($this->user)->post('admin/profil/verifikasi-dua-langkah/aktifkan', ['code' => $code]);

        $result->assertRedirectTo(site_url('admin/profil/verifikasi-dua-langkah/kode-cadangan'));
        $this->assertTrue(service('twoFactor')->isEnabled((int) $this->user->id));

        $codes = $this->withSession()->get('admin/profil/verifikasi-dua-langkah/kode-cadangan');
        $codes->assertOK();
        $codes->assertSee('Simpan Kode Cadangan Anda');
        $this->assertMatchesRegularExpression('/[A-Z2-9]{5}-[A-Z2-9]{5}/', $codes->getBody());

        $again = $this->withSession()->get('admin/profil/verifikasi-dua-langkah/kode-cadangan');
        $again->assertRedirectTo(site_url('admin/profil/verifikasi-dua-langkah'));
    }

    public function testWrongCodeDoesNotEnable(): void
    {
        $this->currentCode();
        $result = $this->actingAs($this->user)->post('admin/profil/verifikasi-dua-langkah/aktifkan', ['code' => '000000']);

        $result->assertRedirect();
        $result->assertSessionHas('errors');
        $this->assertFalse(service('twoFactor')->isEnabled((int) $this->user->id));
    }

    public function testLoginAsksForCodeWhenTwoFactorIsOn(): void
    {
        [$secret] = $this->enableTwoFactor();

        $login = $this->post('masuk', ['email' => 'budi@example.test', 'password' => self::PASSWORD]);
        $login->assertRedirectTo(site_url('verifikasi'));
        $this->assertFalse(auth()->loggedIn());

        $page = $this->withSession()->get('verifikasi');
        $page->assertOK();
        $page->assertSee('Verifikasi Dua Langkah');

        $verify = $this->withSession()->post('verifikasi', ['code' => (new Google2FA())->getCurrentOtp($secret)]);
        $verify->assertRedirect();
        $this->assertStringEndsWith('/admin', $verify->getRedirectUrl());
        $this->assertTrue(auth()->loggedIn());
    }

    public function testRecoveryCodeWorksOnceAtLogin(): void
    {
        [, $codes] = $this->enableTwoFactor();

        $this->post('masuk', ['email' => 'budi@example.test', 'password' => self::PASSWORD]);
        $this->withSession()->post('verifikasi', ['code' => strtolower($codes[0])])->assertRedirect();
        $this->assertTrue(auth()->loggedIn());
        $this->assertSame(7, service('twoFactor')->remainingRecoveryCodes((int) $this->user->id));
    }

    public function testWrongCodesCancelTheLogin(): void
    {
        $this->enableTwoFactor();
        $this->post('masuk', ['email' => 'budi@example.test', 'password' => self::PASSWORD]);

        for ($i = 1; $i < 5; $i++) {
            $this->withSession()->post('verifikasi', ['code' => '000000'])->assertRedirectTo(site_url('verifikasi'));
        }

        $last = $this->withSession()->post('verifikasi', ['code' => '000000']);
        $last->assertRedirectTo(site_url('masuk'));
        $this->assertFalse(auth()->loggedIn());
        $this->assertNull(auth('session')->getAuthenticator()->getPendingUser());
    }

    public function testUserWithoutTwoFactorLogsInDirectly(): void
    {
        $login = $this->post('masuk', ['email' => 'budi@example.test', 'password' => self::PASSWORD]);

        $this->assertStringEndsWith('/admin', $login->getRedirectUrl());
        $this->assertTrue(auth()->loggedIn());
    }

    public function testRoleRequirementSendsUserToSetup(): void
    {
        setting('TwoFactor.requiredRoles', ['panitia']);

        $result = $this->actingAs($this->user)->get('admin');
        $result->assertRedirectTo(site_url('admin/profil/verifikasi-dua-langkah'));

        $this->actingAs($this->user)->get('admin/profil/verifikasi-dua-langkah')->assertOK();
    }

    public function testRequiredTwoFactorCannotBeTurnedOff(): void
    {
        $this->enableTwoFactor();
        setting('TwoFactor.requiredRoles', ['panitia']);

        $this->actingAs($this->user)->post('admin/profil/verifikasi-dua-langkah/matikan', ['password' => self::PASSWORD]);

        $this->assertTrue(service('twoFactor')->isEnabled((int) $this->user->id));
    }

    public function testDisableNeedsThePassword(): void
    {
        $this->enableTwoFactor();

        $this->actingAs($this->user)->post('admin/profil/verifikasi-dua-langkah/matikan', ['password' => 'salah']);
        $this->assertTrue(service('twoFactor')->isEnabled((int) $this->user->id));

        $this->actingAs($this->user)->post('admin/profil/verifikasi-dua-langkah/matikan', ['password' => self::PASSWORD]);
        $this->assertFalse(service('twoFactor')->isEnabled((int) $this->user->id));
    }

    public function testForcedPasswordChange(): void
    {
        $this->user->forcePasswordReset();

        $this->actingAs($this->user)->get('admin')->assertRedirectTo(site_url('admin/profil/kata-sandi'));

        $result = $this->actingAs($this->user)->post('admin/profil/kata-sandi', [
            'current_password' => self::PASSWORD,
            'password'         => 'Pagi-Cerah-Sekali-88',
            'password_confirm' => 'Pagi-Cerah-Sekali-88',
        ]);

        $result->assertRedirectTo(site_url('admin/profil'));
        $user = model(UserModel::class, false)->findById($this->user->id);
        $this->assertFalse($user->requiresPasswordReset());
        $this->assertTrue(service('passwords')->verify('Pagi-Cerah-Sekali-88', $user->password_hash));
    }

    public function testPasswordChangeNeedsCurrentPassword(): void
    {
        $result = $this->actingAs($this->user)->post('admin/profil/kata-sandi', [
            'current_password' => 'bukan-ini',
            'password'         => 'Pagi-Cerah-Sekali-88',
            'password_confirm' => 'Pagi-Cerah-Sekali-88',
        ]);

        $result->assertSessionHas('errors');
        $user = model(UserModel::class, false)->findById($this->user->id);
        $this->assertTrue(service('passwords')->verify(self::PASSWORD, $user->password_hash));
    }
}
