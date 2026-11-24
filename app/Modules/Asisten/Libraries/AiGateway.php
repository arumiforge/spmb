<?php

declare(strict_types=1);

namespace Modules\Asisten\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use JsonException;
use Modules\Asisten\Models\ProviderModel;
use SensitiveParameter;
use Throwable;

/**
 * Talks to the OpenAI-compatible AI connections (D-110). Server side only: the browser never sees a key.
 *
 * Rotation: the active connections that are not resting, least recently used first (round robin).
 * When one fails (HTTP 429, 5xx, other refusals, timeout, network error, unreadable reply) the next
 * one is tried within the same request, and the failing one rests for setting('Asisten.restMinutes')
 * minutes. Requests: POST {base URL}/chat/completions with the standard "messages" format through
 * CodeIgniter's CURLRequest (service('curlrequest'), mocked in tests); the answer is
 * choices[0].message.content. Error texts are cleaned of the key before they are stored or logged.
 */
final class AiGateway
{
    private ProviderModel $providers;

    public function __construct(?ProviderModel $providers = null)
    {
        $this->providers = $providers ?? model(ProviderModel::class);
    }

    /**
     * Whether AI answers can be given at all: switched on and at least one active connection.
     */
    public static function available(): bool
    {
        try {
            return (bool) setting('Asisten.aiEnabled') && model(ProviderModel::class)->hasActive();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Sends the conversation to the first connection that answers.
     *
     * @param list<array{role: string, content: string}> $messages
     *
     * @return array{ok: false, error: string}|array{ok: true, content: string, provider: array<string, mixed>, tokensIn: int|null, tokensOut: int|null}
     */
    public function chat(array $messages): array
    {
        $rotation = $this->providers->rotation();

        if ($rotation === []) {
            return ['ok' => false, 'error' => 'no_connection'];
        }

        $rest = max(1, (int) setting('Asisten.restMinutes'));

        foreach ($rotation as $provider) {
            $key = ProviderKeys::decrypt($provider['api_key'] ?? null);

            if ($key === null) {
                $this->providers->rest((int) $provider['id'], $rest, lang('Asisten.errKeyUnreadable'));

                continue;
            }

            try {
                $reply = $this->call($provider, $key, $messages);
            } catch (ProviderFailure $e) {
                $this->providers->rest((int) $provider['id'], $rest, $e->getMessage());
                log_message('warning', 'Asisten: AI connection "{name}" failed, trying the next one: {reason}', ['name' => $provider['name'], 'reason' => $e->getMessage()]);

                continue;
            }

            $this->providers->markUsed((int) $provider['id']);

            return ['ok' => true, 'provider' => $provider] + $reply;
        }

        return ['ok' => false, 'error' => 'all_failed'];
    }

    /**
     * "Tes koneksi": a tiny prompt to one connection. Changes nothing in the rotation.
     *
     * @param array<string, mixed> $provider
     *
     * @return array{ok: bool, message: string}
     */
    public function test(array $provider): array
    {
        $key = ProviderKeys::decrypt($provider['api_key'] ?? null);

        if ($key === null) {
            return ['ok' => false, 'message' => lang('Asisten.errKeyUnreadable')];
        }

        try {
            $reply = $this->call($provider, $key, [
                ['role' => 'system', 'content' => 'You are a connection test. Reply with the single word OK.'],
                ['role' => 'user', 'content' => 'Reply with OK.'],
            ], 16);
        } catch (ProviderFailure $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return ['ok' => true, 'message' => lang('Asisten.testOk', [mb_substr(trim($reply['content']), 0, 60)])];
    }

    /**
     * @param array<string, mixed>                       $provider
     * @param list<array{role: string, content: string}> $messages
     *
     * @return array{content: string, tokensIn: int|null, tokensOut: int|null}
     *
     * @throws ProviderFailure
     */
    private function call(array $provider, #[SensitiveParameter] string $key, array $messages, ?int $maxTokens = null): array
    {
        $timeout = max(3, min(120, (int) ($provider['timeout_seconds'] ?? 20)));
        $payload = [
            'model'       => (string) $provider['model'],
            'messages'    => $messages,
            'max_tokens'  => $maxTokens ?? max(16, (int) ($provider['max_tokens'] ?? 400)),
            'temperature' => (float) ($provider['temperature'] ?? 0.3),
            'stream'      => false,
        ];

        try {
            /** @var CURLRequest $client */
            $client   = service('curlrequest');
            $response = $client->request('POST', rtrim((string) $provider['base_url'], '/') . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'body'            => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'timeout'         => $timeout,
                'connect_timeout' => min(10, $timeout),
                'http_errors'     => false,
                'verify'          => true,
            ]);
        } catch (Throwable $e) {
            throw new ProviderFailure(self::scrub(lang('Asisten.errNetwork', [mb_substr($e->getMessage(), 0, 140)]), $key));
        }

        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();

        try {
            $data = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $data = null;
        }

        if ($status < 200 || $status >= 300) {
            $detail = is_array($data) ? (string) ($data['error']['message'] ?? $data['message'] ?? '') : '';
            $reason = match (true) {
                $status === 429                  => lang('Asisten.errQuota'),
                $status >= 500                   => lang('Asisten.errServer'),
                $status === 401, $status === 403 => lang('Asisten.errAuth'),
                $status === 404                  => lang('Asisten.errNotFound'),
                default                          => lang('Asisten.errRefused'),
            };

            throw new ProviderFailure(self::scrub(trim($reason . ' (HTTP ' . $status . ')' . ($detail !== '' ? ' ' . mb_substr($detail, 0, 140) : '')), $key), $status);
        }

        $content = is_array($data) ? ($data['choices'][0]['message']['content'] ?? null) : null;

        // Some servers send the content as a list of parts.
        if (is_array($content)) {
            $content = implode('', array_map(static fn ($part): string => is_array($part) ? (string) ($part['text'] ?? '') : (string) $part, $content));
        }

        if (! is_string($content) || trim($content) === '') {
            throw new ProviderFailure(lang('Asisten.errEmpty'), $status);
        }

        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];

        return [
            'content'   => trim($content),
            'tokensIn'  => isset($usage['prompt_tokens']) ? (int) $usage['prompt_tokens'] : null,
            'tokensOut' => isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : null,
        ];
    }

    /**
     * Removes the key (and anything that looks like a bearer token) from a text.
     */
    public static function scrub(string $text, #[SensitiveParameter] string $key = ''): string
    {
        if ($key !== '') {
            $text = str_replace($key, '••••', $text);
        }

        return (string) preg_replace('/(Bearer\s+)\S+/i', '$1••••', $text);
    }
}
