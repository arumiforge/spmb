<?php

declare(strict_types=1);

use App\Commands\Branding;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\StreamFilterTrait;
use CodeIgniter\Test\TestResponse;
use Modules\Asisten\Libraries\AvatarStorage;
use Modules\Asisten\Libraries\ProviderKeys;
use Modules\Asisten\Libraries\QuickChoices;
use Modules\Asisten\Models\ProviderModel;
use Modules\Asisten\Models\QuestionLogModel;
use Tests\Support\Asisten\AsistenFixtures;
use Tests\Support\Asisten\FakeAiHttp;
use Tests\Support\Pendaftaran\PendaftaranFixtures;

/**
 * Tanya Asisten staff pages (D-110): Admin Sekolah (asisten.atur) switches the widget, edits the
 * greeting and quick choices and reads the question log; only Super Admin (asisten.ai) manages AI
 * connections, whose keys are stored encrypted and never shown, logged or audited.
 *
 * @internal
 */
final class AsistenAdminTest extends CIUnitTestCase
{
    use AsistenFixtures;
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PendaftaranFixtures;
    use StreamFilterTrait;

    private const KEY = 'sk-live-SUPERSECRET-wxyz';

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetAsisten();
        $this->resetVisitor();
        service('routes')->loadRoutes();
    }

    protected function tearDown(): void
    {
        AvatarStorage::delete((string) setting('Asisten.avatar'));
        $this->cleanUpAsisten();
        parent::tearDown();
    }

    private function body(TestResponse $result): string
    {
        return html_entity_decode((string) $result->response()->getBody(), ENT_QUOTES | ENT_HTML5);
    }

    public function testPagesByPermission(): void
    {
        $this->get('admin/asisten')->assertRedirectTo(site_url('masuk'));

        $panitia = $this->makeUser('panitia');
        $this->actingAs($panitia)->get('admin/asisten')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($panitia)->get('admin/asisten/pertanyaan')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($panitia)->post('admin/asisten', ['enabled' => '1'])->assertRedirectTo(site_url('admin/akses-ditolak'));

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get('admin/asisten')->assertOK();
        $this->actingAs($admin)->get('admin/asisten/pertanyaan')->assertOK();
        $this->actingAs($admin)->get('admin/asisten/ai')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($admin)->post('admin/asisten/ai', ['aiEnabled' => '1'])->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($admin)->get('admin/asisten/ai/koneksi/tambah')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->assertFalse((bool) setting('Asisten.aiEnabled'));

        $super = $this->makeUser('superadmin');
        $this->actingAs($super)->get('admin/asisten')->assertOK();
        $this->actingAs($super)->get('admin/asisten/ai')->assertOK();

        $this->assertTrue($admin->can('asisten.atur'));
        $this->assertFalse($admin->can('asisten.ai'));
        $this->assertTrue($super->can('asisten.ai'));
        $this->assertFalse($panitia->can('asisten.atur'));
    }

    public function testAdminSwitchesTheWidgetOnAndEditsTheGreeting(): void
    {
        $admin = $this->makeUser('admin');
        $page  = $this->actingAs($admin)->get('admin/asisten');
        $page->assertSee('Tanya Asisten belum tampil di website.');
        $page->assertSee('Jawaban AI mati');
        $this->assertStringNotContainsString(url_to('asisten.ai'), $this->body($page), 'no link to the Super Admin page');

        $result = $this->actingAs($admin)->post('admin/asisten', ['enabled' => '1', 'greeting' => 'Halo, ada yang bisa dibantu?', 'greeting_en' => 'Hello, how can we help?']);
        $result->assertRedirectTo(site_url('admin/asisten'));

        $this->assertTrue(setting('Asisten.enabled'));
        $this->assertSame('Halo, ada yang bisa dibantu?', setting('Asisten.greeting'));
        $this->assertSame('Hello, how can we help?', setting('Asisten.greeting_en'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_type' => 'settings', 'subject_id' => 'Asisten']);

        $this->get('/')->assertSee('Halo, ada yang bisa dibantu?');
    }

    public function testQuickChoicesCanBeEditedAndReset(): void
    {
        $admin = $this->makeUser('admin');
        $this->assertFalse(QuickChoices::isCustom());

        $this->actingAs($admin)->post('admin/asisten/pilihan/tambah', ['kind' => 'link', 'label' => 'Seragam', 'url' => 'javascript:alert(1)'])
            ->assertSessionHas('errors');
        $this->assertFalse(QuickChoices::isCustom());

        $this->actingAs($admin)->post('admin/asisten/pilihan/tambah', ['kind' => 'answer', 'label' => 'Seragam', 'answer' => 'Seragam dibagikan saat daftar ulang.', 'label_en' => 'Uniform'])
            ->assertRedirectTo(site_url('admin/asisten'));
        $choices = QuickChoices::all();
        $this->assertCount(7, $choices);
        $this->assertSame(['kind' => 'answer', 'label' => 'Seragam', 'label_en' => 'Uniform', 'answer' => 'Seragam dibagikan saat daftar ulang.'], $choices[6]);

        $this->actingAs($admin)->post('admin/asisten/pilihan/2/ubah', ['kind' => 'link', 'label' => 'Syarat', 'url' => 'persyaratan'])->assertRedirectTo(site_url('admin/asisten'));
        $this->assertSame(['kind' => 'link', 'label' => 'Syarat', 'url' => '/persyaratan'], QuickChoices::find(2));

        $this->actingAs($admin)->post('admin/asisten/pilihan/7/pindah', ['arah' => 'naik']);
        $this->assertSame('Seragam', QuickChoices::find(6)['label']);

        $this->actingAs($admin)->post('admin/asisten/pilihan/6/hapus');
        $this->assertCount(6, QuickChoices::all());
        $this->seeInDatabase('audit_logs', ['subject_id' => 'AsistenQuickChoices']);

        setting('Asisten.enabled', true);
        $this->get('en/')->assertSee('Syarat');

        $this->actingAs($admin)->post('admin/asisten/pilihan/kembalikan');
        $this->assertFalse(QuickChoices::isCustom());
    }

    public function testSuperAdminAddsAConnectionWithAnEncryptedKey(): void
    {
        $super = $this->makeUser('superadmin');

        $this->actingAs($super)->post('admin/asisten/ai/koneksi/tambah', [
            'name'            => 'Utama', 'base_url' => 'http://api.example.test/v1', 'model' => 'gpt-mini', 'api_key' => self::KEY,
            'timeout_seconds' => '20', 'max_tokens' => '400', 'temperature' => '0,3', 'is_active' => '1',
        ])->assertSessionHas('errors');
        $this->assertSame(0, model(ProviderModel::class)->countAllResults(), 'base URL must be https');
        $this->assertStringNotContainsString(self::KEY, json_encode($_SESSION), 'the key is not kept for the form');

        $this->actingAs($super)->post('admin/asisten/ai/koneksi/tambah', [
            'name'            => 'Utama', 'base_url' => 'https://api.example.test/v1/', 'model' => 'gpt-mini', 'api_key' => self::KEY,
            'timeout_seconds' => '20', 'max_tokens' => '400', 'temperature' => '0,3', 'is_active' => '1',
        ])->assertRedirectTo(site_url('admin/asisten/ai'));

        $row = db_connect()->table('asisten_providers')->get()->getRowArray();
        $this->assertSame('https://api.example.test/v1', $row['base_url']);
        $this->assertSame('0.30', (string) $row['temperature']);
        $this->assertSame('wxyz', $row['key_hint']);
        $this->assertStringNotContainsString(self::KEY, (string) $row['api_key'], 'not stored as plain text');
        $this->assertStringNotContainsString('SUPERSECRET', implode('|', array_map('strval', $row)));
        $this->assertSame(self::KEY, ProviderKeys::decrypt($row['api_key']));

        $list = $this->actingAs($super)->get('admin/asisten/ai');
        $list->assertSee('Utama');
        $list->assertSee('••••wxyz');
        $this->assertStringNotContainsString('SUPERSECRET', $this->body($list));
        $this->assertStringNotContainsString((string) $row['api_key'], $this->body($list));

        $edit = $this->actingAs($super)->get('admin/asisten/ai/koneksi/' . $row['id'] . '/ubah');
        $edit->assertOK();
        $this->assertStringNotContainsString('SUPERSECRET', $this->body($edit));
        $this->assertStringNotContainsString((string) $row['api_key'], $this->body($edit));

        // Saving without a new key keeps the old one.
        $this->actingAs($super)->post('admin/asisten/ai/koneksi/' . $row['id'] . '/ubah', [
            'name'            => 'Utama baru', 'base_url' => 'https://api.example.test/v1', 'model' => 'gpt-mini', 'api_key' => '',
            'timeout_seconds' => '30', 'max_tokens' => '300', 'temperature' => '0.2', 'is_active' => '1',
        ])->assertRedirectTo(site_url('admin/asisten/ai'));
        $after = db_connect()->table('asisten_providers')->get()->getRowArray();
        $this->assertSame(self::KEY, ProviderKeys::decrypt($after['api_key']));
        $this->assertSame('Utama baru', $after['name']);

        // A new key replaces it; the audit log only says that it changed.
        $this->actingAs($super)->post('admin/asisten/ai/koneksi/' . $row['id'] . '/ubah', [
            'name'            => 'Utama baru', 'base_url' => 'https://api.example.test/v1', 'model' => 'gpt-mini', 'api_key' => 'sk-another-SECRET2-1111',
            'timeout_seconds' => '30', 'max_tokens' => '300', 'temperature' => '0.2', 'is_active' => '1',
        ]);
        $this->assertSame('1111', db_connect()->table('asisten_providers')->get()->getRowArray()['key_hint']);

        $audit = json_encode(db_connect()->table('audit_logs')->get()->getResultArray());
        $this->assertStringNotContainsString('SUPERSECRET', $audit);
        $this->assertStringNotContainsString('SECRET2', $audit);
        $this->assertStringNotContainsString((string) $row['api_key'], $audit);
        $this->assertStringContainsString('[disembunyikan]', $audit);
        $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'asisten_providers']);
    }

    public function testConnectionTestReportsWithoutTheKey(): void
    {
        $super = $this->makeUser('superadmin');
        $id    = $this->addProvider('Utama', self::KEY);
        $http  = FakeAiHttp::install()->reply('OK')->fail(401, 'Invalid key ' . self::KEY);

        $this->actingAs($super)->post('admin/asisten/ai/koneksi/' . $id . '/tes')->assertSessionHas('message');
        $this->assertStringContainsString('berhasil', (string) session('message'));
        $this->assertSame('Bearer ' . self::KEY, $http->requests[0]['headers']['Authorization']);

        $this->actingAs($super)->post('admin/asisten/ai/koneksi/' . $id . '/tes')->assertSessionHas('error');
        $error = (string) session('error');
        $this->assertStringContainsString('API key ditolak (HTTP 401)', $error);
        $this->assertStringNotContainsString('SUPERSECRET', $error);
        $this->assertNull(db_connect()->table('asisten_providers')->get()->getRowArray()['rested_until'], 'a test changes nothing in the rotation');
    }

    public function testSuperAdminSavesAiSettingsAndManagesConnections(): void
    {
        $super = $this->makeUser('superadmin');
        $first = $this->addProvider('Satu');
        $this->addProvider('Dua');

        $this->actingAs($super)->post('admin/asisten/ai', [
            'aiEnabled'         => '1', 'dailyVisitorLimit' => '5', 'dailySiteLimit' => '100', 'perMinuteLimit' => '4', 'maxLength' => '200',
            'extraInstructions' => 'Jawab dengan sopan.',
        ])->assertRedirectTo(site_url('admin/asisten/ai'));
        $this->assertTrue(setting('Asisten.aiEnabled'));
        $this->assertSame(5, setting('Asisten.dailyVisitorLimit'));
        $this->assertSame('Jawab dengan sopan.', setting('Asisten.extraInstructions'));

        $this->actingAs($super)->post('admin/asisten/ai', ['aiEnabled' => '1', 'dailyVisitorLimit' => '-1', 'dailySiteLimit' => '100', 'perMinuteLimit' => '4', 'maxLength' => '200'])
            ->assertSessionHas('errors');

        $this->actingAs($super)->post('admin/asisten/ai/koneksi/' . $first . '/aktifkan', ['aktif' => '0']);
        $this->assertSame('0', (string) model(ProviderModel::class)->find($first)['is_active']);

        $this->actingAs($super)->post('admin/asisten/ai/koneksi/' . $first . '/pindah', ['arah' => 'turun']);
        $this->assertSame(['Dua', 'Satu'], array_column(model(ProviderModel::class)->ordered(), 'name'));

        $this->actingAs($super)->post('admin/asisten/ai/koneksi/' . $first . '/hapus');
        $this->assertNull(model(ProviderModel::class)->find($first));
    }

    public function testQuestionLogShowsUnansweredQuestionsAndPrefillsTanyaJawab(): void
    {
        $log = model(QuestionLogModel::class);
        $log->record('Apakah ada asrama?', 'id', 'none');
        $log->record('asrama ada?', 'id', 'ai');
        $log->record('Berapa biaya?', 'id', 'qa', ['site_question_id' => 1]);
        $log->record('Biaya', 'id', 'chip', ['choice_label' => 'Biaya']);

        $admin = $this->makeUser('admin');
        $page  = $this->actingAs($admin)->get('admin/asisten/pertanyaan');
        $page->assertOK();
        $page->assertSee('Paling sering ditanyakan, belum ada di Tanya Jawab');
        $page->assertSee('Ditanyakan 2 kali');
        $page->assertSee('Jadikan tanya jawab');
        $this->assertStringContainsString(site_url('admin/tanya-jawab/tambah') . '?pertanyaan=asrama+ada%3F', $this->body($page));

        $form = $this->actingAs($admin)->get('admin/tanya-jawab/tambah?pertanyaan=asrama+ada%3F');
        $form->assertOK();
        $this->assertStringContainsString('value="asrama ada?"', $this->body($form));

        $filtered = $this->actingAs($admin)->get('admin/asisten/pertanyaan?jenis=chip');
        $filtered->assertSee('Pilihan cepat');

        $this->actingAs($admin)->post('admin/asisten/pertanyaan/pengaturan', ['retentionDays' => '3'])->assertSessionHas('errors');
        db_connect()->table('asisten_questions')->where('answered_by', 'chip')->update(['created_at' => date('Y-m-d H:i:s', strtotime('-20 days'))]);
        $this->actingAs($admin)->post('admin/asisten/pertanyaan/pengaturan', ['retentionDays' => '10'])->assertRedirectTo(site_url('admin/asisten/pertanyaan'));
        $this->assertSame(10, setting('Asisten.retentionDays'));
        $this->assertSame(3, db_connect()->table('asisten_questions')->countAllResults(), 'older entries are removed at once');
    }

    public function testBrandingPackLoadsTheAssistantPictureOnce(): void
    {
        $dir = WRITEPATH . 'uploads/test-asisten-pack-' . bin2hex(random_bytes(4));
        mkdir($dir, 0775, true);
        file_put_contents($dir . '/branding.json', '{"settings": {}}');
        $image = imagecreatetruecolor(600, 600);
        imagepng($image, $dir . '/asisten.png');

        try {
            $this->runCommand(Branding::class, [$dir]);
            $stored = (string) setting('Asisten.avatar');
            $this->assertMatchesRegularExpression('/\Aasisten-[0-9a-f]{16}\.png\z/', $stored);
            $this->assertSame([512, 512], array_slice(getimagesize(AvatarStorage::DIR . $stored) ?: [], 0, 2), 'scaled down to 512 px');
            $this->seeInDatabase('audit_logs', ['subject_id' => 'Asisten', 'description' => 'Gambar Tanya Asisten dimuat dari paket branding']);

            // Running again keeps the installed picture.
            $this->runCommand(Branding::class, [$dir]);
            $this->assertSame($stored, setting('Asisten.avatar'));

            // The widget and the admin page show it; it is served without a language prefix.
            setting('Asisten.enabled', true);
            $this->assertStringContainsString(site_url('asisten/gambar') . '?v=', $this->body($this->get('en/')));
            $picture = $this->get('asisten/gambar');
            $picture->assertOK();
            $this->assertSame('image/png', $picture->response()->getHeaderLine('Content-Type'));
            $this->get('en/asisten/gambar')->assertRedirectTo(base_url('asisten/gambar'));

            $admin = $this->makeUser('admin');
            $this->actingAs($admin)->post('admin/asisten/gambar/hapus')->assertRedirectTo(site_url('admin/asisten'));
            $this->assertSame('', setting('Asisten.avatar'));
            $this->assertFileDoesNotExist(AvatarStorage::DIR . $stored);
        } finally {
            array_map('unlink', glob($dir . '/*') ?: []);
            rmdir($dir);
        }
    }
}
