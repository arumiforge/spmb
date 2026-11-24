<?php

declare(strict_types=1);

use App\Commands\Branding;
use CodeIgniter\CLI\Commands;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\StreamFilterTrait;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\TemplateStorage;
use Tests\Support\Berkas\BerkasTestHelpers;

/**
 * Example files from a client pack: spmb:branding installs
 * <client>/berkas-contoh/<code>.<ext> as the type's "Unduh contoh" file.
 *
 * The command runs directly, not through command(): discovering the command
 * list again after another test called resetServices() loads a module command
 * class twice (FileLocator first tries it under the App namespace).
 *
 * @internal
 */
final class BerkasContohBrandingTest extends CIUnitTestCase
{
    use BerkasTestHelpers;
    use DatabaseTestTrait;
    use StreamFilterTrait;

    private const PACK = SUPPORTPATH . 'Berkas/pack/branding';

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        // No signed-in user or services left over from earlier tests.
        $_SESSION = [];
        $this->resetServices();
        $this->seedTypes();
    }

    protected function tearDown(): void
    {
        $this->removeStoredFiles();
        parent::tearDown();
    }

    public function testBrandingCommandInstallsPackTemplatesOnce(): void
    {
        $this->branding(self::PACK);
        $sk = $this->type('sk-aktif');
        $this->assertSame(['sk-aktif.pdf', '1'], [$sk['template_name'], (string) $sk['template_enabled']]);
        $this->assertFileEquals(SUPPORTPATH . 'Berkas/pack/berkas-contoh/sk-aktif.pdf', WRITEPATH . 'uploads/berkas-contoh/' . $sk['template_path']);
        $output = $this->getStreamFilterBuffer();
        $this->assertStringContainsString('no document type with code tidak-ada', $output);
        $this->assertStringContainsString('catatan.txt skipped', $output);

        // Running again changes nothing, and a school's choice to hide it stays.
        model(DocumentTypeModel::class)->update($sk['id'], ['template_enabled' => 0]);
        $this->branding(self::PACK);
        $again = $this->type('sk-aktif');
        $this->assertSame([$sk['template_path'], '0'], [$again['template_path'], (string) $again['template_enabled']]);
        $this->assertCount(1, array_filter(array_column(model(DocumentTypeModel::class)->findAll(), 'template_path')));
    }

    public function testBrandingCommandKeepsTheSchoolsOwnTemplate(): void
    {
        $own = $this->withTemplate('sk-aktif', 'transparent.png');

        $this->branding(self::PACK);

        $this->assertSame($own['template_path'], $this->type('sk-aktif')['template_path']);
        $this->assertStringContainsString('kept the example the school uploaded', $this->getStreamFilterBuffer());
    }

    public function testCommandWithoutAPackFolderIsUnchanged(): void
    {
        $this->branding(SUPPORTPATH . 'Branding/pack');

        $this->assertSame([], array_filter(array_column(model(DocumentTypeModel::class)->findAll(), 'template_path')));
    }

    private function branding(string $dir): void
    {
        $commands = new class (service('logger')) extends Commands {
            public function __construct($logger)
            {
                // No command discovery: only Branding runs.
                $this->logger = $logger;
            }
        };

        (new Branding(service('logger'), $commands))->run([$dir]);
    }

    /**
     * @return array<string, mixed>
     */
    private function withTemplate(string $code, string $fixture): array
    {
        $type   = $this->type($code);
        $stored = (new TemplateStorage())->store($this->fixture($fixture), 'contoh-' . $fixture);
        model(DocumentTypeModel::class)->update($type['id'], $stored + ['template_enabled' => 1]);

        return $this->type($code);
    }
}
