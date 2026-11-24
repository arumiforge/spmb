<?php

declare(strict_types=1);

namespace Modules\Asisten\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Asisten\Libraries\AiGateway;
use Modules\Asisten\Libraries\ProviderKeys;
use Modules\Asisten\Libraries\UsageLimiter;
use Modules\Asisten\Models\ProviderModel;
use RuntimeException;

/**
 * Pengaturan → Koneksi AI (asisten.ai, Super Admin only, D-110): the AI switch, limits, question
 * length and extra instruction, and the list of OpenAI-compatible connections (name, base URL over
 * https, model, API key, timeout, max tokens, temperature, on/off, order) with "Tes koneksi".
 *
 * The API key is encrypted before it is stored and never shown again: the page shows only its last
 * 4 characters, an empty key field keeps the saved key, and the audit log records only that the key
 * changed ("[disembunyikan]").
 */
class KoneksiAi extends BaseController
{
    private const SETTINGS = [
        'dailyVisitorLimit' => [0, 1000],
        'dailySiteLimit'    => [0, 100000],
        'perMinuteLimit'    => [1, 60],
        'maxLength'         => [50, 1000],
    ];

    private ProviderModel $providers;

    public function __construct()
    {
        $this->providers = model(ProviderModel::class);
    }

    public function index(): string
    {
        return view('Modules\Asisten\Views\admin\ai', [
            'title'       => lang('Asisten.aiTitle'),
            'subtitle'    => lang('Asisten.aiIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => lang('Asisten.aiTitle')]],
            'values'      => [
                'aiEnabled'         => setting('Asisten.aiEnabled') ? '1' : '0',
                'dailyVisitorLimit' => (string) setting('Asisten.dailyVisitorLimit'),
                'dailySiteLimit'    => (string) setting('Asisten.dailySiteLimit'),
                'perMinuteLimit'    => (string) setting('Asisten.perMinuteLimit'),
                'maxLength'         => (string) setting('Asisten.maxLength'),
                'extraInstructions' => (string) setting('Asisten.extraInstructions'),
            ],
            'limits'    => self::SETTINGS,
            'providers' => $this->providers->ordered(),
            'usedToday' => (new UsageLimiter($this->request))->siteCountToday(),
            'widgetOn'  => (bool) setting('Asisten.enabled'),
            'hasKey'    => (string) config('Encryption')->key !== '',
        ]);
    }

    public function update(): RedirectResponse
    {
        $rules = [];

        foreach (self::SETTINGS as $key => [$min, $max]) {
            $rules[$key] = ['label' => lang('Asisten.field' . ucfirst($key)), 'rules' => "required|is_natural|greater_than_equal_to[{$min}]|less_than_equal_to[{$max}]"];
        }

        $rules['extraInstructions'] = ['label' => lang('Asisten.fieldExtraInstructions'), 'rules' => 'permit_empty|max_length[2000]'];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $values = ['aiEnabled' => $this->request->getPost('aiEnabled') === '1'];

        foreach (array_keys(self::SETTINGS) as $key) {
            $values[$key] = (int) $this->request->getPost($key);
        }

        $values['extraInstructions'] = trim(str_replace("\r\n", "\n", (string) $this->request->getPost('extraInstructions')));
        $old                         = [];
        $new                         = [];

        foreach ($values as $key => $value) {
            $current = setting('Asisten.' . $key);

            if ($current !== $value) {
                $old[$key] = $current;
                $new[$key] = $value;
                setting('Asisten.' . $key, $value);
            }
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'Asisten', $old, $new, 'Pengaturan AI Tanya Asisten diubah');
        }

        return redirect()->route('asisten.ai')->with('message', lang('Asisten.saved'));
    }

    public function new(): string
    {
        return $this->form(null);
    }

    public function create(): RedirectResponse
    {
        $data = $this->posted(true);

        if ($data === null) {
            return $this->backWithErrors();
        }

        $data['sort_order'] = $this->providers->nextSortOrder();
        $this->providers->insert($data);
        $id = (int) $this->providers->getInsertID();
        service('audit')->log('created', 'asisten_providers', $id, null, self::auditValues($data), 'Koneksi AI ditambah: ' . $data['name']);

        return redirect()->route('asisten.ai')->with('message', lang('Asisten.providerSaved', [$data['name']]));
    }

    public function edit(int $id): RedirectResponse|string
    {
        $provider = $this->providers->find($id);

        if ($provider === null) {
            return redirect()->route('asisten.ai')->with('error', lang('Asisten.providerNotFound'));
        }

        return $this->form($provider);
    }

    public function updateProvider(int $id): RedirectResponse
    {
        $provider = $this->providers->find($id);

        if ($provider === null) {
            return redirect()->route('asisten.ai')->with('error', lang('Asisten.providerNotFound'));
        }

        $data = $this->posted(false);

        if ($data === null) {
            return $this->backWithErrors();
        }

        // A changed address, model or key gets a fresh start in the rotation.
        if (isset($data['api_key']) || $data['base_url'] !== $provider['base_url'] || $data['model'] !== $provider['model']) {
            $data['rested_until'] = null;
            $data['last_error']   = null;
        }

        $this->providers->update($id, $data);
        $this->logChange($id, $provider, $data, 'Koneksi AI diubah: ' . $data['name']);

        return redirect()->route('asisten.ai')->with('message', lang('Asisten.providerSaved', [$data['name']]));
    }

    public function toggle(int $id): RedirectResponse
    {
        $provider = $this->providers->find($id);

        if ($provider === null) {
            return redirect()->route('asisten.ai')->with('error', lang('Asisten.providerNotFound'));
        }

        $active = $this->request->getPost('aktif') === '1';
        $data   = ['is_active' => (int) $active, 'rested_until' => null];
        $this->providers->update($id, $data);
        $this->logChange($id, $provider, $data, ($active ? 'Koneksi AI dinyalakan: ' : 'Koneksi AI dimatikan: ') . $provider['name']);

        return redirect()->to(url_to('asisten.ai') . '#koneksi-' . $id)->with('message', lang($active ? 'Asisten.providerOn' : 'Asisten.providerOff', [$provider['name']]));
    }

    public function move(int $id): RedirectResponse
    {
        $list  = $this->providers->ordered();
        $ids   = array_map('intval', array_column($list, 'id'));
        $index = array_search($id, $ids, true);

        if ($index !== false) {
            $target = $this->request->getPost('arah') === 'naik' ? $index - 1 : $index + 1;

            if (isset($ids[$target])) {
                [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

                foreach ($ids as $position => $providerId) {
                    $this->providers->builder()->where('id', $providerId)->update(['sort_order' => ($position + 1) * 10]);
                }
            }
        }

        return redirect()->to(url_to('asisten.ai') . '#koneksi-' . $id);
    }

    public function test(int $id): RedirectResponse
    {
        $provider = $this->providers->find($id);

        if ($provider === null) {
            return redirect()->route('asisten.ai')->with('error', lang('Asisten.providerNotFound'));
        }

        $result = (new AiGateway($this->providers))->test($provider);

        return redirect()->to(url_to('asisten.ai') . '#koneksi-' . $id)
            ->with($result['ok'] ? 'message' : 'error', lang($result['ok'] ? 'Asisten.testPassed' : 'Asisten.testFailed', [$provider['name'], $result['message']]));
    }

    public function delete(int $id): RedirectResponse
    {
        $provider = $this->providers->find($id);

        if ($provider === null) {
            return redirect()->route('asisten.ai')->with('error', lang('Asisten.providerNotFound'));
        }

        $this->providers->delete($id);
        service('audit')->log('deleted', 'asisten_providers', $id, self::auditValues($provider), null, 'Koneksi AI dihapus: ' . $provider['name']);

        return redirect()->route('asisten.ai')->with('message', lang('Asisten.providerDeleted', [$provider['name']]));
    }

    /**
     * Back to the form with the typed values, except the API key (never kept in the session).
     */
    private function backWithErrors(): RedirectResponse
    {
        service('superglobals')->unsetPost('api_key');

        return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
    }

    /**
     * @param array<string, mixed>|null $provider
     */
    private function form(?array $provider): string
    {
        $isNew = $provider === null;

        return view('Modules\Asisten\Views\admin\koneksi_form', [
            'title'       => lang($isNew ? 'Asisten.providerAddTitle' : 'Asisten.providerEditTitle'),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Pengaturan'],
                ['label' => lang('Asisten.aiTitle'), 'url' => 'admin/asisten/ai'],
                ['label' => lang($isNew ? 'Asisten.providerCrumbAdd' : 'Asisten.providerCrumbEdit')],
            ],
            // The key itself never goes back to the browser.
            'provider' => $provider === null
                ? ['name' => '', 'base_url' => 'https://', 'model' => '', 'key_hint' => null, 'timeout_seconds' => 20, 'max_tokens' => 400, 'temperature' => '0.30', 'is_active' => 1]
                : array_diff_key($provider, ['api_key' => true]),
            'isNew'  => $isNew,
            'hasKey' => (string) config('Encryption')->key !== '',
        ]);
    }

    /**
     * Validated connection data from the form, or null (errors in $this->validator). The API key is
     * encrypted here; an empty key field on an existing connection keeps the saved key.
     *
     * @return array<string, mixed>|null
     */
    private function posted(bool $isNew): ?array
    {
        $post = [
            'name'            => trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('name'))),
            'base_url'        => rtrim(trim((string) $this->request->getPost('base_url')), '/'),
            'model'           => trim((string) $this->request->getPost('model')),
            'timeout_seconds' => trim((string) $this->request->getPost('timeout_seconds')),
            'max_tokens'      => trim((string) $this->request->getPost('max_tokens')),
            'temperature'     => str_replace(',', '.', trim((string) $this->request->getPost('temperature'))),
        ];
        $key = trim((string) $this->request->getPost('api_key'));

        $rules = [
            'name'            => ['label' => lang('Asisten.fieldName'), 'rules' => 'required|max_length[80]'],
            'base_url'        => ['label' => lang('Asisten.fieldBaseUrl'), 'rules' => 'required|max_length[255]|valid_url_strict[https]', 'errors' => ['valid_url_strict' => lang('Asisten.baseUrlHttps')]],
            'model'           => ['label' => lang('Asisten.fieldModel'), 'rules' => 'required|max_length[120]|regex_match[/\A[^\s]+\z/]'],
            'timeout_seconds' => ['label' => lang('Asisten.fieldTimeout'), 'rules' => 'required|is_natural|greater_than_equal_to[3]|less_than_equal_to[120]'],
            'max_tokens'      => ['label' => lang('Asisten.fieldMaxTokens'), 'rules' => 'required|is_natural|greater_than_equal_to[50]|less_than_equal_to[4000]'],
            'temperature'     => ['label' => lang('Asisten.fieldTemperature'), 'rules' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[2]'],
        ];

        $check = $post + ['api_key' => $key];

        if ($isNew) {
            $rules['api_key'] = ['label' => lang('Asisten.fieldApiKey'), 'rules' => 'required|max_length[500]', 'errors' => ['required' => lang('Asisten.apiKeyRequired')]];
        } else {
            $rules['api_key'] = ['label' => lang('Asisten.fieldApiKey'), 'rules' => 'permit_empty|max_length[500]'];
        }

        if (! $this->validateData($check, $rules)) {
            return null;
        }

        $data                    = $post + ['is_active' => (int) ($this->request->getPost('is_active') === '1')];
        $data['timeout_seconds'] = (int) $data['timeout_seconds'];
        $data['max_tokens']      = (int) $data['max_tokens'];
        $data['temperature']     = number_format((float) $data['temperature'], 2, '.', '');

        if ($key !== '') {
            try {
                $data['api_key'] = ProviderKeys::encrypt($key);
            } catch (RuntimeException) {
                $this->validator->setError('api_key', lang('Asisten.noEncryptionKey'));

                return null;
            }

            $data['key_hint'] = ProviderKeys::hint($key);
        }

        return $data;
    }

    /**
     * Logs the changed columns; a new key is recorded as "[disembunyikan]" only.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $data
     */
    private function logChange(int $id, array $before, array $data, string $description): void
    {
        [$old, $new] = service('audit')->diff(self::auditValues($before), self::auditValues($data));

        if (isset($data['api_key'])) {
            $old['api_key'] = '[disembunyikan]';
            $new['api_key'] = '[disembunyikan]';
        }

        unset($old['rested_until'], $new['rested_until'], $old['last_error'], $new['last_error']);

        if ($new !== []) {
            service('audit')->log('updated', 'asisten_providers', $id, $old, $new, $description);
        }
    }

    /**
     * Columns safe for the audit log: never the key, its hint or rotation details.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private static function auditValues(array $row): array
    {
        return array_intersect_key($row, array_flip(['name', 'base_url', 'model', 'timeout_seconds', 'max_tokens', 'temperature', 'is_active']));
    }
}
