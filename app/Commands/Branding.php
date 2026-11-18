<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\BrandingAssets;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\School;
use Throwable;

/**
 * Loads a school's branding pack (D-054): a folder with branding.json and the
 * image files it names. Sets School.* settings and stores the images exactly as
 * an upload in Pengaturan → Identitas Sekolah would. Safe to run again.
 *
 * branding.json:
 *   {"settings": {"name": "...", "shortName": "...", "primaryColor": "#005c2f", ...},
 *    "images": {"logo": "logo.png", "logoWide": "...", "favicon": "favicon.png", ...}}
 */
class Branding extends BaseCommand
{
    protected $group       = 'SPMB';
    protected $name        = 'spmb:branding';
    protected $description = 'Load a school branding pack (branding.json + images) into the settings.';
    protected $usage       = 'spmb:branding <folder>';
    protected $arguments   = ['folder' => 'Folder with branding.json, e.g. clients/<school>/branding'];

    public function run(array $params)
    {
        $dir = rtrim((string) ($params[0] ?? ''), '/');

        // spark runs from public/: resolve relative folders against the project root.
        if ($dir !== '' && ! str_starts_with($dir, '/') && ! is_dir($dir)) {
            $dir = ROOTPATH . $dir;
        }

        $file = $dir . '/branding.json';

        if ($dir === '' || ! is_file($file)) {
            CLI::error('branding.json not found. Usage: php spark ' . $this->usage);

            return EXIT_ERROR;
        }

        $pack = json_decode((string) file_get_contents($file), true);

        if (! is_array($pack)) {
            CLI::error('branding.json is not valid JSON.');

            return EXIT_ERROR;
        }

        $allowed       = array_keys(get_class_vars(School::class));
        $imageSettings = array_column(BrandingAssets::ASSETS, 'setting');
        $old           = [];
        $new           = [];

        foreach ((array) ($pack['settings'] ?? []) as $key => $value) {
            if (! in_array($key, $allowed, true) || in_array($key, $imageSettings, true) || ! is_string($value)) {
                CLI::write("  Skipped unknown setting {$key}", 'yellow');

                continue;
            }

            if ($key === 'primaryColor' && valid_hex_color($value) === null) {
                CLI::error("primaryColor {$value} is not a #rrggbb colour.");

                return EXIT_ERROR;
            }

            $current = (string) setting('School.' . $key);

            if ($current !== $value) {
                setting('School.' . $key, $value);
                $old[$key] = $current;
                $new[$key] = $value;
            }
        }

        foreach ((array) ($pack['images'] ?? []) as $key => $name) {
            if (! isset(BrandingAssets::ASSETS[$key]) || ! is_string($name) || ! is_file($dir . '/' . basename($name))) {
                CLI::error("Image {$key}: missing or unknown ({$name}).");

                return EXIT_ERROR;
            }

            try {
                $stored = BrandingAssets::storeFile($dir . '/' . basename($name), $key);
            } catch (Throwable $e) {
                CLI::error($e->getMessage());

                return EXIT_ERROR;
            }

            $setting = BrandingAssets::ASSETS[$key]['setting'];
            $current = (string) setting('School.' . $setting);
            BrandingAssets::remove($current);
            setting('School.' . $setting, $stored);
            $old[$setting] = $current;
            $new[$setting] = $stored;
            CLI::write("  {$key}: {$name}");
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'School', $old, $new, 'Identitas sekolah dimuat dari paket branding');
        }

        CLI::write('Branding loaded: ' . count($new) . ' setting(s) changed.', 'green');

        return EXIT_SUCCESS;
    }
}
