<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\BrandingAssets;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\School;
use Modules\Asisten\Libraries\AvatarStorage;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\TemplateStorage;
use Modules\Berkas\Services\UploadRejected;
use Modules\Situs\Models\SiteQuestionModel;
use Throwable;

/**
 * Loads a school's branding pack (D-054): a folder with branding.json and the
 * image files it names. Sets School.* settings and stores the images exactly as
 * an upload in Pengaturan → Identitas Sekolah would. Safe to run again.
 *
 * branding.json:
 *   {"settings": {"name": "...", "shortName": "...", "primaryColor": "#005c2f", ...},
 *    "images": {"logo": "logo.png", "logoWide": "...", "favicon": "favicon.png", ...}}
 *
 * Starter questions for /tanya-jawab (D-072) come from tanya-jawab.json next to
 * branding.json or in its parent folder (the client folder). They are loaded only
 * while the installation has no questions, so the school's own edits are kept:
 *   {"questions": [{"topic": "...", "question": "...", "answer": "Markdown", "published": true}, ...]}
 *
 * Example files for document types ("Unduh contoh") come from a berkas-contoh/
 * folder next to branding.json or in its parent folder (the client folder):
 * berkas-contoh/<code>.<pdf|doc|docx|jpg|jpeg|png>, e.g. sk-aktif.doc. A file is
 * installed and switched on only for a type that has no example yet; the same
 * file again changes nothing, and an example the school uploaded itself is kept.
 *
 * The Tanya Asisten picture (D-110) comes from asisten.webp (or .png/.jpg) next to branding.json;
 * asisten-<School.illustrationAccent>.webp is preferred when it exists. It is installed only while
 * the school has no picture of its own, and the same file again changes nothing.
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

            if ($key === 'mapEmbed') {
                $value = map_embed_url($value);
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

        $result = $this->loadQuestions($dir);
        $result = $result === EXIT_SUCCESS ? $this->loadTemplates($dir) : $result;

        return $result === EXIT_SUCCESS ? $this->loadAssistantPicture($dir) : $result;
    }

    /**
     * Installs the Tanya Asisten picture (asisten.webp) from the pack folder, if present.
     */
    private function loadAssistantPicture(string $dir): int
    {
        $accent     = (string) setting('School.illustrationAccent');
        $candidates = [];

        if (preg_match('/\A[a-z]+\z/', $accent) === 1) {
            $candidates[] = $dir . '/asisten-' . $accent . '.webp';
        }

        foreach (['webp', 'png', 'jpg'] as $ext) {
            $candidates[] = $dir . '/asisten.' . $ext;
        }

        $file = null;

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $file = $candidate;

                break;
            }
        }

        if ($file === null || ! service('modules')->isEnabled('Asisten')) {
            return EXIT_SUCCESS;
        }

        $current = AvatarStorage::file();

        if ($current !== '') {
            CLI::write(
                hash_file('sha256', AvatarStorage::DIR . $current) === hash_file('sha256', $file)
                    ? '  Assistant picture: already installed.'
                    : '  Assistant picture: kept the picture the school uploaded.',
                'yellow',
            );

            return EXIT_SUCCESS;
        }

        try {
            $stored = AvatarStorage::storeFile($file);
        } catch (Throwable $e) {
            CLI::error('Assistant picture ' . basename($file) . ': ' . $e->getMessage());

            return EXIT_ERROR;
        }

        $old = AvatarStorage::replace($stored);
        service('audit')->log('settings_updated', 'settings', 'Asisten', ['avatar' => $old], ['avatar' => $stored], 'Gambar Tanya Asisten dimuat dari paket branding');
        CLI::write('  Assistant picture: ' . basename($file) . ' installed.', 'green');

        return EXIT_SUCCESS;
    }

    /**
     * Installs example files from berkas-contoh/ in the pack folder or its parent, if present.
     */
    private function loadTemplates(string $dir): int
    {
        $folder = null;

        foreach ([$dir, dirname($dir)] as $base) {
            if (is_dir($base . '/berkas-contoh')) {
                $folder = $base . '/berkas-contoh';

                break;
            }
        }

        if ($folder === null || ! service('modules')->isEnabled('Berkas') || ! db_connect()->fieldExists('template_path', 'document_types')) {
            return EXIT_SUCCESS;
        }

        $files = glob($folder . '/*.*') ?: [];
        sort($files);
        $model   = model(DocumentTypeModel::class);
        $storage = new TemplateStorage();

        foreach ($files as $file) {
            $name = basename($file);
            $code = strtolower(pathinfo($name, PATHINFO_FILENAME));
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (! is_file($file) || ! isset(TemplateStorage::FORMATS[$ext])) {
                CLI::write("  Example {$name} skipped: use PDF, DOC, DOCX, JPG or PNG.", 'yellow');

                continue;
            }

            $type = $model->where('code', $code)->first();

            if ($type === null) {
                CLI::write("  Example {$name} skipped: no document type with code {$code}.", 'yellow');

                continue;
            }

            $current = $storage->path($type['template_path'] ?? null);

            if ($current !== null) {
                CLI::write(
                    hash_file('sha256', $current) === hash_file('sha256', $file)
                        ? "  Example {$code}: already installed."
                        : "  Example {$code}: kept the example the school uploaded.",
                    'yellow',
                );

                continue;
            }

            try {
                $stored = $storage->store($file, $name);
            } catch (UploadRejected $e) {
                CLI::error("Example {$name}: " . $e->getMessage());

                return EXIT_ERROR;
            }

            $model->update((int) $type['id'], $stored + ['template_enabled' => 1]);
            CLI::write("  Example {$code}: {$name} installed and shown to applicants.", 'green');
        }

        return EXIT_SUCCESS;
    }

    /**
     * Loads tanya-jawab.json from the pack folder or its parent, if present.
     */
    private function loadQuestions(string $dir): int
    {
        $file = null;

        foreach ([$dir, dirname($dir)] as $folder) {
            if (is_file($folder . '/tanya-jawab.json')) {
                $file = $folder . '/tanya-jawab.json';

                break;
            }
        }

        if ($file === null) {
            return EXIT_SUCCESS;
        }

        $model = model(SiteQuestionModel::class);

        // Deleted questions count too: a school that removed the starter set keeps it removed.
        if ($model->withDeleted()->countAllResults() > 0) {
            CLI::write('Tanya jawab skipped: the installation already has questions.', 'yellow');

            return EXIT_SUCCESS;
        }

        $data = json_decode((string) file_get_contents($file), true);
        $rows = [];

        foreach (is_array($data) ? (array) ($data['questions'] ?? []) : [] as $i => $item) {
            $topic    = is_array($item) ? trim((string) ($item['topic'] ?? '')) : '';
            $question = is_array($item) ? trim((string) ($item['question'] ?? '')) : '';
            $answer   = is_array($item) ? trim((string) ($item['answer'] ?? '')) : '';

            if ($topic === '' || $question === '' || $answer === '' || mb_strlen($topic) > 80 || mb_strlen($question) > 200 || mb_strlen($answer) > 5000) {
                CLI::error('tanya-jawab.json: question ' . ($i + 1) . ' needs a topic (max 80), question (max 200) and answer (max 5000).');

                return EXIT_ERROR;
            }

            $row = ['topic' => $topic, 'question' => $question, 'answer' => $answer];
            // Answers that still need the school's input stay hidden until completed.
            $row['is_published'] = (int) ((bool) ($item['published'] ?? true) && ! SiteQuestionModel::needsSchoolInput($row));
            $rows[]              = $row;
        }

        if ($rows === []) {
            CLI::error('tanya-jawab.json has no questions.');

            return EXIT_ERROR;
        }

        foreach ($rows as $position => $row) {
            $model->insert($row + ['sort_order' => ($position + 1) * 10]);
        }

        $model->normalise();
        $hidden = count(array_filter($rows, static fn (array $r): bool => $r['is_published'] === 0));
        CLI::write('Tanya jawab loaded: ' . count($rows) . ' question(s), ' . $hidden . ' hidden until the school completes them.', 'green');

        return EXIT_SUCCESS;
    }
}
