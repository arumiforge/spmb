<?php

declare(strict_types=1);

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthFlowTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    private function user(?string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'ani', 'email' => 'ani@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => 'Ani Wijaya']));
        $user = $users->findById($users->getInsertID());

        if ($group !== null) {
            $user->addGroup($group);
        }

        return $user;
    }

    public function testLoginPageUsesIndonesianSlug(): void
    {
        $result = $this->get('masuk');
        $result->assertOK();
        $result->assertSee('assets/img/auth/masuk-dasbor.webp');
    }

    public function testEnglishAndRegistrationRoutesAreNotExposed(): void
    {
        foreach (['login', 'register', 'logout', 'login/magic-link'] as $path) {
            $this->get($path)->assertStatus(404);
        }
    }

    public function testAdminRequiresLogin(): void
    {
        $result = $this->get('admin');

        $result->assertRedirect();
        $this->assertStringEndsWith('/masuk', $result->getRedirectUrl());
    }

    public function testStaffWithRoleSeesDashboard(): void
    {
        $result = $this->actingAs($this->user('panitia'))->get('admin');

        $result->assertOK();
        $result->assertSee('Ani Wijaya');
        $result->assertSee('Panitia');
    }

    public function testUserWithoutPermissionIsTurnedAway(): void
    {
        $result = $this->actingAs($this->user(null))->get('admin');

        $result->assertRedirect();
    }

    public function testStaffSignInWithUsername(): void
    {
        $this->user('panitia');

        $result = $this->post('masuk', ['login' => 'ani', 'password' => 'Rahasia-Kuat-2027']);

        $result->assertRedirect();
        $this->assertTrue(auth()->loggedIn());
        $this->assertSame('ani', auth()->user()->username);
    }

    public function testStaffSignInWithEmail(): void
    {
        $this->user('panitia');

        $result = $this->post('masuk', ['login' => 'ani@example.test', 'password' => 'Rahasia-Kuat-2027']);

        $result->assertRedirect();
        $this->assertTrue(auth()->loggedIn());
    }

    public function testWrongPasswordIsRefusedForUsername(): void
    {
        $this->user('panitia');
        auth()->logout();

        $result = $this->post('masuk', ['login' => 'ani', 'password' => 'salah-sekali-2027']);

        $result->assertRedirect();
        $this->assertFalse(auth()->loggedIn());
    }

    public function testEmptyLoginFieldShowsFriendlyMessage(): void
    {
        auth()->logout();

        $result = $this->post('masuk', ['login' => '', 'password' => 'Rahasia-Kuat-2027']);

        $result->assertRedirect();
        $this->assertFalse(auth()->loggedIn());
        $this->assertContains(lang('Akun.loginFieldRequired'), (array) session('errors'));
    }
}
