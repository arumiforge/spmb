<?php

declare(strict_types=1);

namespace Tests\Support\Asisten;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\Commands;
use Config\Services;
use Modules\Asisten\Libraries\ProviderKeys;
use Modules\Asisten\Models\ProviderModel;
use Modules\Situs\Models\SiteQuestionModel;

/**
 * Shared set-up for Tanya Asisten tests: settings to forget, published questions, AI connections
 * with encrypted keys and the fake HTTP client.
 */
trait AsistenFixtures
{
    /**
     * @var list<string>
     */
    protected array $asistenSettings = [
        'Asisten.enabled', 'Asisten.greeting', 'Asisten.greeting_en', 'Asisten.greeting_ar', 'Asisten.quickChoices', 'Asisten.avatar',
        'Asisten.retentionDays', 'Asisten.aiEnabled', 'Asisten.dailyVisitorLimit', 'Asisten.dailySiteLimit', 'Asisten.perMinuteLimit',
        'Asisten.maxLength', 'Asisten.extraInstructions', 'Asisten.restMinutes', 'Asisten.matchThreshold',
        'School.whatsapp', 'School.name', 'School.address', 'Features.disabled',
    ];

    protected function resetAsisten(): void
    {
        foreach ($this->asistenSettings as $key) {
            setting()->forget($key);
        }

        Services::resetSingle('curlrequest');
        Services::resetSingle('modules');
        Services::resetSingle('throttler');
        cache()->clean();
        $_SESSION = [];
    }

    /**
     * Leaves no shared service, cookie or referer behind for the next test class.
     */
    protected function cleanUpAsisten(): void
    {
        $this->resetAsisten();
        service('superglobals')->setCookieArray([]);
        service('superglobals')->setServer('HTTP_REFERER', '');
        $this->resetServices();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function addQuestion(array $overrides = []): int
    {
        $model = model(SiteQuestionModel::class);

        return (int) $model->insert($overrides + [
            'topic'        => 'Biaya',
            'question'     => 'Berapa biaya pendaftaran?',
            'answer'       => 'Pendaftaran **tidak dipungut biaya**. Lihat [persyaratan](/persyaratan).',
            'sort_order'   => $model->nextSortOrder(),
            'is_published' => 1,
        ]);
    }

    /**
     * An active AI connection with a stored (encrypted) key.
     *
     * @param array<string, mixed> $overrides
     */
    protected function addProvider(string $name, string $key = 'sk-test-key-1234', array $overrides = []): int
    {
        $model = model(ProviderModel::class);

        return (int) $model->insert($overrides + [
            'name'            => $name,
            'base_url'        => 'https://' . strtolower($name) . '.example.test/v1',
            'model'           => 'model-' . strtolower($name),
            'api_key'         => ProviderKeys::encrypt($key),
            'key_hint'        => ProviderKeys::hint($key),
            'timeout_seconds' => 20,
            'max_tokens'      => 400,
            'temperature'     => '0.30',
            'is_active'       => 1,
            'sort_order'      => $model->nextSortOrder(),
        ]);
    }

    /**
     * Runs a spark command directly, without command discovery (which can load a command class twice
     * after another test reset the services; see BerkasContohBrandingTest).
     *
     * @param class-string<BaseCommand> $class
     * @param list<string>              $params
     */
    protected function runCommand(string $class, array $params = []): mixed
    {
        $commands = new class (service('logger')) extends Commands {
            public function __construct($logger)
            {
                $this->logger = $logger;
            }
        };

        return (new $class(service('logger'), $commands))->run($params);
    }

    protected function enableWidget(bool $ai = false): void
    {
        setting('Asisten.enabled', true);
        setting('Asisten.aiEnabled', $ai);
    }
}
