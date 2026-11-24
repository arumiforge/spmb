<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\CodeIgniter;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Read-only health report of the server the app runs on (Super Admin, D-101):
 * PHP limits that decide the largest upload, required extensions, writable
 * folders, disk space, database and app facts. Nothing here changes state.
 *
 * Each row: label, shown value, status (ok | warn | bad | info) and an optional hint.
 */
class ServerCheck
{
    /**
     * PHP extensions the app needs (composer.json + image handling + QR/PDF).
     */
    public const REQUIRED_EXTENSIONS = ['intl', 'mbstring', 'mysqli', 'curl', 'json', 'openssl', 'fileinfo', 'gd', 'zip', 'sodium'];

    /**
     * Extensions that help but are not required.
     */
    public const OPTIONAL_EXTENSIONS = ['exif', 'zend opcache', 'imagick', 'apcu', 'redis'];

    /**
     * Folders under writable/ the app writes to.
     */
    public const WRITABLE_DIRS = ['cache', 'logs', 'session', 'uploads'];

    /**
     * Below this free space (bytes) the disk row warns; below a quarter of it, it fails.
     */
    public const DISK_WARN_BYTES = 2 * 1024 * 1024 * 1024;

    /**
     * @return array<string, list<array{label: string, value: string, status: string, hint?: string}>>
     */
    public function report(): array
    {
        return [
            'upload'     => $this->upload(),
            'php'        => $this->php(),
            'extensions' => $this->extensions(),
            'storage'    => $this->storage(),
            'database'   => $this->database(),
            'app'        => $this->app(),
        ];
    }

    /**
     * Worst status of the whole report, for the summary banner.
     *
     * @param array<string, list<array{status: string}>> $report
     */
    public static function overall(array $report): string
    {
        $statuses = array_merge(...array_map(static fn (array $rows): array => array_column($rows, 'status'), array_values($report)));

        return in_array('bad', $statuses, true) ? 'bad' : (in_array('warn', $statuses, true) ? 'warn' : 'ok');
    }

    /**
     * The largest upload PHP accepts, in bytes: the smaller of upload_max_filesize and post_max_size.
     */
    public static function maxUploadBytes(): int
    {
        $limits = array_filter([
            self::iniBytes((string) ini_get('upload_max_filesize')),
            self::iniBytes((string) ini_get('post_max_size')),
        ], static fn (int $bytes): bool => $bytes > 0);

        return $limits === [] ? 0 : min($limits);
    }

    /**
     * "12M" → 12582912; "0" or "-1" → 0 (no limit).
     */
    public static function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $number = (float) $value;

        return (int) match (strtolower(substr($value, -1))) {
            'g'     => $number * 1024 ** 3,
            'm'     => $number * 1024 ** 2,
            'k'     => $number * 1024,
            default => $number,
        };
    }

    public static function formatBytes(float|int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return rtrim(rtrim(number_format($bytes / 1024 ** $power, 1, ',', '.'), '0'), ',') . ' ' . $units[$power];
    }

    /**
     * @return list<array{label: string, value: string, status: string, hint?: string}>
     */
    private function upload(): array
    {
        $max      = self::maxUploadBytes();
        $largest  = $this->largestConfiguredUploadBytes();
        $uploads  = (bool) ini_get('file_uploads');
        $maxFiles = (int) ini_get('max_file_uploads');

        return [
            ['label' => 'file_uploads', 'value' => $uploads ? 'On' : 'Off', 'status' => $uploads ? 'ok' : 'bad', 'hint' => $uploads ? '' : 'Unggah berkas dimatikan di php.ini.'],
            ['label' => 'upload_max_filesize', 'value' => (string) ini_get('upload_max_filesize'), 'status' => 'info'],
            ['label' => 'post_max_size', 'value' => (string) ini_get('post_max_size'), 'status' => self::iniBytes((string) ini_get('post_max_size')) >= self::iniBytes((string) ini_get('upload_max_filesize')) ? 'info' : 'warn', 'hint' => 'Sebaiknya sedikit lebih besar dari upload_max_filesize.'],
            ['label' => 'max_file_uploads', 'value' => (string) $maxFiles, 'status' => $maxFiles >= 5 ? 'info' : 'warn'],
            [
                'label'  => 'Batas unggah efektif (PHP)',
                'value'  => $max === 0 ? 'Tanpa batas' : self::formatBytes($max),
                'status' => $max === 0 || $max >= $largest ? 'ok' : 'bad',
                'hint'   => $largest > 0 ? 'Batas terbesar di pengaturan berkas: ' . self::formatBytes($largest) . '.' : '',
            ],
            [
                'label'  => 'Nginx client_max_body_size',
                'value'  => $this->nginxBodyLimit() ?? 'Tidak terbaca dari aplikasi',
                'status' => 'info',
                'hint'   => 'Nilai dari docker/nginx/spmb.conf. Harus sama atau lebih besar dari post_max_size.',
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string, status: string, hint?: string}>
     */
    private function php(): array
    {
        $memory   = self::iniBytes((string) ini_get('memory_limit'));
        $exec     = (int) ini_get('max_execution_time');
        $display  = filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN);
        $isProd   = ENVIRONMENT === 'production';
        $opcache  = function_exists('opcache_get_status') && (bool) ini_get('opcache.enable');
        $timezone = (string) ini_get('date.timezone');
        $appZone  = config('App')->appTimezone;

        return [
            ['label' => 'Versi PHP', 'value' => PHP_VERSION, 'status' => version_compare(PHP_VERSION, '8.5.13', '>=') ? 'ok' : 'warn', 'hint' => 'Proyek memakai PHP 8.5.13 atau lebih baru.'],
            ['label' => 'SAPI', 'value' => PHP_SAPI, 'status' => 'info'],
            ['label' => 'memory_limit', 'value' => (string) ini_get('memory_limit'), 'status' => $memory === 0 || $memory >= 128 * 1024 ** 2 ? 'ok' : 'warn', 'hint' => 'Minimal 128M untuk membuat PDF dan mengolah foto.'],
            ['label' => 'max_execution_time', 'value' => $exec === 0 ? '0 (tanpa batas)' : $exec . ' detik', 'status' => $exec === 0 || $exec >= 30 ? 'ok' : 'warn'],
            ['label' => 'max_input_time', 'value' => (string) ini_get('max_input_time'), 'status' => 'info'],
            ['label' => 'max_input_vars', 'value' => (string) ini_get('max_input_vars'), 'status' => (int) ini_get('max_input_vars') >= 1000 ? 'ok' : 'warn'],
            ['label' => 'OPcache', 'value' => $opcache ? 'Aktif' : 'Tidak aktif', 'status' => $opcache || ! $isProd ? 'ok' : 'warn', 'hint' => $opcache ? '' : 'Nyalakan di production agar halaman lebih cepat.'],
            ['label' => 'display_errors', 'value' => $display ? 'On' : 'Off', 'status' => $display && $isProd ? 'bad' : 'ok', 'hint' => $display && $isProd ? 'Matikan di production: pesan galat bisa membuka detail server.' : ''],
            ['label' => 'date.timezone (PHP)', 'value' => $timezone !== '' ? $timezone : '(kosong)', 'status' => 'info', 'hint' => 'Zona waktu aplikasi: ' . $appZone . '.'],
        ];
    }

    /**
     * @return list<array{label: string, value: string, status: string, hint?: string}>
     */
    private function extensions(): array
    {
        $rows = [];

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $rows[] = ['label' => $ext, 'value' => $loaded ? (phpversion($ext) ?: 'Terpasang') : 'Tidak ada', 'status' => $loaded ? 'ok' : 'bad'];
        }

        foreach (self::OPTIONAL_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $rows[] = ['label' => $ext . ' (opsional)', 'value' => $loaded ? (phpversion($ext) ?: 'Terpasang') : 'Tidak ada', 'status' => 'info'];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, value: string, status: string, hint?: string}>
     */
    private function storage(): array
    {
        $rows = [];

        foreach (self::WRITABLE_DIRS as $dir) {
            $path     = WRITEPATH . $dir;
            $writable = is_dir($path) && is_writable($path);
            $rows[]   = ['label' => 'writable/' . $dir, 'value' => $writable ? 'Bisa ditulis' : 'Tidak bisa ditulis', 'status' => $writable ? 'ok' : 'bad'];
        }

        $free  = @disk_free_space(WRITEPATH);
        $total = @disk_total_space(WRITEPATH);

        if ($free !== false && $total !== false && $total > 0) {
            $status = $free < self::DISK_WARN_BYTES / 4 ? 'bad' : ($free < self::DISK_WARN_BYTES ? 'warn' : 'ok');
            $rows[] = [
                'label'  => 'Ruang disk kosong',
                'value'  => self::formatBytes($free) . ' dari ' . self::formatBytes($total) . ' (' . round($free / $total * 100) . '%)',
                'status' => $status,
                'hint'   => $status === 'ok' ? '' : 'Hapus cadangan lama atau perbesar disk.',
            ];
        }

        $rows[] = ['label' => 'Besar folder unggahan', 'value' => self::formatBytes($this->folderSize(WRITEPATH . 'uploads')), 'status' => 'info'];

        if (function_exists('sys_getloadavg') && ($load = sys_getloadavg()) !== false) {
            $rows[] = ['label' => 'Beban CPU (1/5/15 menit)', 'value' => implode(' / ', array_map(static fn (float $l): string => number_format($l, 2), $load)), 'status' => 'info'];
        }

        if (is_readable('/proc/meminfo') && preg_match_all('/^(MemTotal|MemAvailable):\s+(\d+) kB/m', (string) file_get_contents('/proc/meminfo'), $m) === 2) {
            $mem    = array_combine($m[1], array_map('intval', $m[2]));
            $rows[] = ['label' => 'Memori tersedia', 'value' => self::formatBytes($mem['MemAvailable'] * 1024) . ' dari ' . self::formatBytes($mem['MemTotal'] * 1024), 'status' => $mem['MemAvailable'] < 256 * 1024 ? 'warn' : 'info'];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, value: string, status: string, hint?: string}>
     */
    private function database(): array
    {
        try {
            $db      = db_connect();
            $version = $db->getVersion();
            $name    = $db->getDatabase();
            $size    = (float) ($db->query('SELECT COALESCE(SUM(data_length + index_length), 0) AS size FROM information_schema.tables WHERE table_schema = ?', [$name])->getRow()->size ?? 0);
            $tables  = count($db->listTables());
            $zone    = (string) ($db->query('SELECT @@session.time_zone AS tz')->getRow()->tz ?? '');

            return [
                ['label' => 'Server database', 'value' => $db->DBDriver . ' ' . $version, 'status' => version_compare((string) preg_replace('/[^0-9.].*$/', '', $version), '8.0', '>=') ? 'ok' : 'warn', 'hint' => 'Proyek memakai MySQL 8.4.'],
                ['label' => 'Nama database', 'value' => $name, 'status' => 'info'],
                ['label' => 'Jumlah tabel', 'value' => (string) $tables, 'status' => 'info'],
                ['label' => 'Besar database', 'value' => self::formatBytes($size), 'status' => 'info'],
                ['label' => 'Zona waktu sesi', 'value' => $zone, 'status' => 'info'],
            ];
        } catch (Throwable $e) {
            log_message('error', 'ServerCheck database: ' . $e->getMessage());

            return [['label' => 'Server database', 'value' => 'Tidak bisa terhubung', 'status' => 'bad']];
        }
    }

    /**
     * @return list<array{label: string, value: string, status: string, hint?: string}>
     */
    private function app(): array
    {
        $isProd  = ENVIRONMENT === 'production';
        $baseUrl = config('App')->baseURL;
        $https   = str_starts_with($baseUrl, 'https://');

        return [
            ['label' => 'Lingkungan (CI_ENVIRONMENT)', 'value' => ENVIRONMENT, 'status' => 'info'],
            ['label' => 'CodeIgniter', 'value' => CodeIgniter::CI_VERSION, 'status' => 'info'],
            ['label' => 'Alamat dasar (baseURL)', 'value' => $baseUrl, 'status' => $https || ! $isProd ? 'ok' : 'warn', 'hint' => $https || ! $isProd ? '' : 'Production sebaiknya memakai https://.'],
            ['label' => 'Waktu server', 'value' => date('Y-m-d H:i:s T'), 'status' => 'info'],
            ['label' => 'Penyimpanan sesi', 'value' => basename(str_replace('\\', '/', config('Session')->driver)), 'status' => 'info'],
            ['label' => 'Cache', 'value' => config('Cache')->handler, 'status' => 'info'],
            ['label' => 'Sistem operasi', 'value' => php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')', 'status' => 'info'],
        ];
    }

    /**
     * Largest per-file limit set in the app (document types), so the PHP limit can be compared with it.
     */
    private function largestConfiguredUploadBytes(): int
    {
        try {
            $db = db_connect();

            if (! $db->tableExists('document_types')) {
                return 0;
            }

            $kb = (int) ($db->table('document_types')->selectMax('max_size_kb', 'kb')->where('deleted_at', null)->where('is_active', 1)->get()->getRow()->kb ?? 0);

            return max($kb * 1024, self::iniBytes((string) setting('Berkas.imageUploadLimitKb') . 'K'));
        } catch (Throwable) {
            return 0;
        }
    }

    private function nginxBodyLimit(): ?string
    {
        $file = ROOTPATH . 'docker/nginx/spmb.conf';

        if (! is_readable($file) || preg_match('/^\s*client_max_body_size\s+([^;]+);/m', (string) file_get_contents($file), $m) !== 1) {
            return null;
        }

        return trim($m[1]);
    }

    private function folderSize(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $size = 0;

        try {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
                $size += $file->isFile() ? $file->getSize() : 0;
            }
        } catch (Throwable) {
            // Unreadable sub-folder: show what could be counted.
        }

        return $size;
    }
}
