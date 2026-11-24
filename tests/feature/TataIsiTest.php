<?php

declare(strict_types=1);

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Settings pages: one column of folding cards with the "Isi halaman" rail (D-100, D-102).
 *
 * @internal
 */
final class TataIsiTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        Services::resetSingle('auth');
    }

    public function testSettingsPagesUseTheRailAndFoldingCards(): void
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'uadmin', 'email' => 'admin@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $admin = $users->findById($users->getInsertID());
        $admin->addGroup('admin');

        $pages = [
            'admin/pengaturan/sekolah'     => 'form-sekolah',
            'admin/pengaturan/pendaftaran' => 'form-pengaturan-pendaftaran',
            'admin/pengaturan/tes'         => 'form-pengaturan-tes',
            'admin/pengaturan/kartu'       => 'form-pengaturan-kartu',
        ];

        foreach ($pages as $url => $formId) {
            $result = $this->actingAs($admin)->get($url);
            $result->assertOK();
            $body = (string) $result->getBody();

            $this->assertStringContainsString('class="tata-isi"', $body, $url);
            $this->assertStringContainsString('id="' . $formId . '"', $body, $url);
            $this->assertStringContainsString('form="' . $formId . '"', $body, $url);   // rail save button
            $this->assertStringContainsString('data-isi-halaman', $body, $url);
            $this->assertStringContainsString('data-lipatan="', $body, $url);
            $this->assertStringNotContainsString('xl:grid-cols-2', $body, $url);
        }
    }
}
