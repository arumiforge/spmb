<?php

declare(strict_types=1);

namespace Modules\Referensi\Libraries;

use Closure;
use Config\Services;
use RuntimeException;
use Throwable;

/**
 * Reads the public school lists of the Dapodik portal, one region level at a
 * time: provinces → regencies → districts → schools of one district.
 * Endpoints and response shapes: docs/modules/referensi.md.
 *
 * The HTTP call is a closure so tests can feed fixtures; http() builds the
 * real one. Every request waits $delayMs first to stay polite.
 */
class DapodikClient
{
    public const PATH = '/api/progress-pengiriman/';

    /**
     * @param Closure(string, array<string, string>): mixed $fetch Returns decoded JSON for a path and query
     */
    public function __construct(private readonly Closure $fetch, private readonly int $delayMs = 0)
    {
    }

    /**
     * Client for the real portal. $token is optional (referensi.dapodikToken in .env).
     */
    public static function http(string $baseUrl, string $token = '', int $delayMs = 500, int $timeout = 30): self
    {
        $baseUrl = rtrim($baseUrl, '/');

        $fetch = static function (string $path, array $query) use ($baseUrl, $token, $timeout): mixed {
            $headers = ['Accept' => 'application/json', 'User-Agent' => 'SPMB-Referensi/1.0'];

            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $url = $baseUrl . self::PATH . $path . ($query === [] ? '' : '?' . http_build_query($query));

            try {
                $response = Services::curlrequest([], null, null, false)->get($url, [
                    'headers'     => $headers,
                    'timeout'     => $timeout,
                    'http_errors' => false,
                ]);
            } catch (Throwable $e) {
                throw new RuntimeException('Dapodik could not be reached: ' . $e->getMessage(), 0, $e);
            }

            $status = $response->getStatusCode();

            if ($status === 401 || $status === 403) {
                throw new RuntimeException('Dapodik refused the request (HTTP ' . $status . '). Set referensi.dapodikToken in .env if the portal requires an access key.');
            }

            if ($status !== 200) {
                throw new RuntimeException('Dapodik answered HTTP ' . $status . ' for ' . $path . '.');
            }

            $json = json_decode($response->getBody(), true);

            if (! is_array($json)) {
                throw new RuntimeException('Dapodik sent an unexpected answer for ' . $path . ' (not JSON).');
            }

            return $json;
        };

        return new self(Closure::fromCallable($fetch), $delayMs);
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function provinces(string $level): array
    {
        return $this->regions('provinsi', ['jenjang' => $level], 'kode_provinsi', 'provinsi');
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function regencies(string $provinceCode, string $level): array
    {
        return $this->regions('kabupaten', ['kode_provinsi' => $provinceCode, 'jenjang' => $level], 'kode_kabupaten', 'kabupaten');
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function districts(string $regencyCode, string $level): array
    {
        return $this->regions('kecamatan', ['kode_kabupaten' => $regencyCode, 'jenjang' => $level], 'kode_kecamatan', 'kecamatan');
    }

    /**
     * Raw school rows of one district (npsn, nama, jenjang, bentuk_pendidikan, status_sekolah, ...).
     *
     * @return list<array<string, mixed>>
     */
    public function schools(string $districtCode, string $level): array
    {
        return array_values(array_filter($this->get('kecamatan/school', ['kode_kecamatan' => $districtCode, 'jenjang' => $level]), 'is_array'));
    }

    /**
     * @param array<string, string> $query
     *
     * @return list<array{code: string, name: string}>
     */
    private function regions(string $path, array $query, string $codeKey, string $nameKey): array
    {
        $regions = [];

        foreach ($this->get($path, $query) as $row) {
            if (! is_array($row) || ! isset($row[$codeKey], $row[$nameKey])) {
                continue;
            }

            $code = trim((string) $row[$codeKey]);

            if ($code !== '' && ! isset($regions[$code])) {
                $regions[$code] = ['code' => $code, 'name' => trim((string) $row[$nameKey])];
            }
        }

        return array_values($regions);
    }

    /**
     * @param array<string, string> $query
     *
     * @return list<mixed>
     */
    private function get(string $path, array $query): array
    {
        if ($this->delayMs > 0) {
            usleep($this->delayMs * 1000);
        }

        $json = ($this->fetch)($path, $query);

        if (is_array($json) && array_is_list($json)) {
            return $json;
        }

        foreach (['data', 'results'] as $key) {
            if (is_array($json) && isset($json[$key]) && is_array($json[$key])) {
                return array_values($json[$key]);
            }
        }

        throw new RuntimeException('Dapodik sent an unexpected answer for ' . $path . ' (no list).');
    }
}
