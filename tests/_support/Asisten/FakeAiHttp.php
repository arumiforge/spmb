<?php

declare(strict_types=1);

namespace Tests\Support\Asisten;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use Config\App;
use Config\Services;
use RuntimeException;
use Throwable;

/**
 * Stand-in for service('curlrequest') in Tanya Asisten tests: never touches the network. Queue
 * replies (status + body) or failures in order; every request is recorded with its decoded body.
 *
 *     $http = FakeAiHttp::install()->reply('Jawaban')->fail(429);
 */
final class FakeAiHttp extends CURLRequest
{
    /**
     * @var list<array{method: string, url: string, headers: array<string, string>, body: array<string, mixed>|null, options: array<string, mixed>}>
     */
    public array $requests = [];

    /**
     * @var list<array{0: int, 1: string}|Throwable>
     */
    private array $queue = [];

    public function __construct()
    {
        $config = config(App::class);

        parent::__construct($config, new URI(), new Response($config), []);
    }

    public static function install(): self
    {
        $fake = new self();
        Services::injectMock('curlrequest', $fake);

        return $fake;
    }

    /**
     * A successful chat/completions reply with $content.
     */
    public function reply(string $content, int $promptTokens = 120, int $completionTokens = 30): self
    {
        $this->queue[] = [200, json_encode([
            'id'      => 'chatcmpl-test',
            'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => $content], 'finish_reason' => 'stop']],
            'usage'   => ['prompt_tokens' => $promptTokens, 'completion_tokens' => $completionTokens, 'total_tokens' => $promptTokens + $completionTokens],
        ], JSON_THROW_ON_ERROR)];

        return $this;
    }

    /**
     * An HTTP error reply (429, 500, 401, …).
     */
    public function fail(int $status, string $message = 'error'): self
    {
        $this->queue[] = [$status, json_encode(['error' => ['message' => $message]], JSON_THROW_ON_ERROR)];

        return $this;
    }

    /**
     * A network failure or timeout as CURLRequest reports it.
     */
    public function timeout(): self
    {
        $this->queue[] = HTTPException::forCurlError('28', 'Operation timed out after 20000 milliseconds');

        return $this;
    }

    public function request($method, string $url, array $options = []): ResponseInterface
    {
        $headers = [];

        foreach ((array) ($options['headers'] ?? []) as $name => $value) {
            $headers[(string) $name] = (string) $value;
        }

        $this->requests[] = [
            'method'  => (string) $method,
            'url'     => $url,
            'headers' => $headers,
            'body'    => isset($options['body']) ? json_decode((string) $options['body'], true) : null,
            'options' => $options,
        ];

        $next = array_shift($this->queue) ?? throw new RuntimeException('FakeAiHttp: no reply queued for ' . $url);

        if ($next instanceof Throwable) {
            throw $next;
        }

        $response = new Response(config(App::class));
        $response->setStatusCode($next[0]);
        $response->setBody($next[1]);

        return $response;
    }

    /**
     * Base URLs of the requests made, in order.
     *
     * @return list<string>
     */
    public function urls(): array
    {
        return array_column($this->requests, 'url');
    }
}
