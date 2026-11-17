<?php

declare(strict_types=1);

use App\Libraries\ModuleRegistry;
use CodeIgniter\Test\CIUnitTestCase;
use Modules\Riwayat\Libraries\AuditPresenter;

/**
 * Module requirements, module-provided lists (public menu) and panels,
 * using the fixture modules in tests/_support/ModuleSets/requires.
 *
 * @internal
 */
final class ModuleRegistryTest extends CIUnitTestCase
{
    private const PATH = SUPPORTPATH . 'ModuleSets/requires';

    public function testModuleIsOffWhenARequiredModuleIsOffOrMissing(): void
    {
        $registry = new ModuleRegistry(self::PATH, [], []);
        $this->assertSame(['Cucu', 'Dasar', 'Turunan'], $registry->enabled());
        $this->assertSame(['Yatim'], $registry->disabled());
        $this->assertSame(['Dasar'], $registry->info('Turunan')['requires']);

        $registry = new ModuleRegistry(self::PATH, [], ['Dasar']);
        $this->assertSame([], $registry->enabled());
        $this->assertNull($registry->file('Turunan', 'Config/PublicMenu.php'));
    }

    public function testCoreModuleIgnoresRequirements(): void
    {
        $registry = new ModuleRegistry(self::PATH, ['Yatim'], []);

        $this->assertTrue($registry->isEnabled('Yatim'));
    }

    public function testCollectsListsFromEnabledModulesOnly(): void
    {
        $registry = new ModuleRegistry(self::PATH, [], []);
        $this->assertSame(
            [['label' => 'Dasar', 'url' => 'dasar', 'order' => 10, 'module' => 'Dasar'], ['label' => 'Turunan', 'url' => 'turunan', 'order' => 20, 'module' => 'Turunan']],
            $registry->collect('Config/PublicMenu.php'),
        );

        $registry = new ModuleRegistry(self::PATH, [], ['Turunan']);
        $this->assertSame(['Dasar'], array_column($registry->collect('Config/PublicMenu.php'), 'module'));
    }

    public function testPanelsOfEnabledModules(): void
    {
        $this->assertSame(
            ['Modules\Dasar\Views\panels\contoh', 'Modules\Turunan\Views\panels\contoh'],
            (new ModuleRegistry(self::PATH, [], []))->panels('contoh'),
        );
        $this->assertSame(['Modules\Dasar\Views\panels\contoh'], (new ModuleRegistry(self::PATH, [], ['Turunan']))->panels('contoh'));
        $this->assertSame([], (new ModuleRegistry(self::PATH, [], []))->panels('tidak-ada'));
    }

    public function testRealModulesDeclareTheirRequirements(): void
    {
        $registry = new ModuleRegistry(APPPATH . 'Modules', config('Features')->core, ['Pendaftaran']);

        foreach (['Berkas', 'Kartu', 'Situs'] as $module) {
            $this->assertFalse($registry->isEnabled($module), $module . ' needs Pendaftaran');
        }

        $this->assertTrue($registry->isEnabled('Referensi'));
    }

    public function testAuditPresenterUsesModuleLabels(): void
    {
        $presenter = new AuditPresenter([], AuditPresenter::moduleLabels(['Dasar', 'Turunan'], self::PATH));

        $this->assertSame('Kegiatan dasar', $presenter->eventLabel('dasar_event'));
        $this->assertSame('green', $presenter->eventColor('dasar_event'));
        $this->assertSame('Nama dari modul', $presenter->fieldLabel('nama'));
        $this->assertSame(lang('App.yes'), $presenter->format('ada', '1'));
        // Riwayat's own labels win over a module's.
        $this->assertSame(lang('Riwayat.events.created'), $presenter->eventLabel('created'));
        $this->assertSame('Tidak dikenal', $presenter->eventLabel('tidak_dikenal'));
    }

    public function testEveryRealModuleAuditFileIsWellFormed(): void
    {
        $labels = AuditPresenter::moduleLabels(service('modules')->all());

        foreach ($labels as $section => $entries) {
            $this->assertContains($section, ['events', 'subjects', 'settingGroups', 'fields', 'eventColors', 'boolFields'], $section);
            $this->assertNotSame([], $entries);
        }

        $this->assertSame('Pendaftar', $labels['subjects']['applicants']);
    }
}
