<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Services\ApplicantService;
use Tests\Support\Pendaftaran\PendaftaranFixtures;
use Tests\Support\ViewerAccounts;

/**
 * Dashboard layout (D-081, D-087): the mobile bottom bar per role, the raised
 * "Cari" button and its quick search, and the developer credit in the footer.
 *
 * @internal
 */
final class AdminLayoutTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PendaftaranFixtures;
    use ViewerAccounts;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetVisitor();
    }

    protected function tearDown(): void
    {
        $this->forgetPendaftaranSettings();
        $this->resetVisitor();
        parent::tearDown();
    }

    /**
     * Labels of the bottom bar items, in order.
     *
     * @return list<string>
     */
    private function bottomBar(TestResponse $result): array
    {
        $this->assertSame(1, preg_match('#<nav class="nav-bawah[^"]*"[^>]*>(.*?)</nav>#s', $result->getBody(), $nav), 'bottom bar missing');
        // The raised middle button has no visible text; its name is in a screen-reader-only span.
        preg_match_all('#<span class="(?:nav-bawah-label|sr-only)">([^<]*)</span>#', $nav[1], $labels);

        return array_map(static fn (string $label): string => html_entity_decode($label), $labels[1]);
    }

    public function testAdminSekolahBottomBar(): void
    {
        $result = $this->actingAs($this->makeUser('admin'))->get('admin');

        $result->assertOK();
        $this->assertSame(['Dasbor', 'Pendaftar', 'Cari', 'Berkas', 'Menu'], $this->bottomBar($result));
        $result->assertSee('data-dialog-open="cari-cepat"');
    }

    public function testPanitiaBottomBar(): void
    {
        $result = $this->actingAs($this->makeUser('panitia'))->get('admin');

        $result->assertOK();
        $this->assertSame(['Dasbor', 'Pendaftar', 'Cari', 'Berkas', 'Menu'], $this->bottomBar($result));
    }

    public function testNoSearchButtonWithoutApplicantList(): void
    {
        // Test attendance only: the free slots take the attendance pages, no "Cari".
        $result = $this->actingAs($this->viewer('kehadiran', ['dasbor.view', 'tes.kehadiran']))->get('admin');

        $result->assertOK();
        $this->assertSame(['Dasbor', 'Kehadiran', 'Peserta', 'Menu'], $this->bottomBar($result));
        $result->assertDontSee('data-dialog-open="cari-cepat"');
        $result->assertDontSee('id="cari-cepat"');
    }

    public function testSearchButtonWithApplicantListOnly(): void
    {
        $result = $this->actingAs($this->viewer('lihat'))->get('admin');

        $result->assertOK();
        $this->assertContains('Cari', $this->bottomBar($result));
        $result->assertSee('id="cari-cepat"');
    }

    public function testQuickSearchGoesToApplicantList(): void
    {
        $this->makeWave();
        $data = ApplicantForm::clean($this->applicantInput(['full_name' => 'Budi Santoso']), [...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE]);
        (new ApplicantService())->register($data, 'offline');
        $user = $this->makeUser('panitia');

        $page = $this->actingAs($user)->get('admin');
        $this->assertSame(1, preg_match('#<dialog id="cari-cepat".*?<form method="get" action="([^"]+)".*?</dialog>#s', $page->getBody(), $form));
        $this->assertStringEndsWith('admin/pendaftar', $form[1]);
        $this->assertMatchesRegularExpression('#<dialog id="cari-cepat".*?<input type="search" id="cari-cepat-q" name="q"#s', $page->getBody());

        $result = $this->actingAs($user)->get('admin/pendaftar?q=Budi');
        $result->assertOK();
        $result->assertSee('Budi Santoso');

        $this->actingAs($user)->get('admin/pendaftar?q=TidakAda')->assertDontSee('Budi Santoso');
    }

    public function testFooterCredit(): void
    {
        foreach (['admin', 'panitia'] as $group) {
            $result = $this->actingAs($this->makeUser($group))->get('admin');
            $result->assertSee('Developed by : Mr. F');
            $this->assertMatchesRegularExpression('#<footer[^>]*>.*Developed by : Mr\. F.*</footer>#s', $result->getBody());
        }
    }

    public function testListFiltersFoldOnPhonesUntilUsed(): void
    {
        $user = $this->makeUser('admin');

        $folded = $this->actingAs($user)->get('admin/pendaftar');
        $folded->assertSee('Saring pendaftar');
        $folded->assertSee('id="pendaftar-saring" class="hidden ');

        $open = $this->actingAs($user)->get('admin/pendaftar?cara=online');
        $open->assertSee('id="pendaftar-saring" class="sm:col-span-2');
    }
}
