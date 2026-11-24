<?php

declare(strict_types=1);

namespace Modules\Panduan\Libraries;

use App\Libraries\ModuleRegistry;
use League\CommonMark\CommonMarkConverter;

/**
 * Assembles a role's guide from the Markdown files in each enabled module's
 * Guide/ folder. The Panduan module's own file comes first, then the other
 * modules in name order. On English and Arabic pages a translated file next to the
 * Indonesian one (<audience>.en.md, <audience>.ar.md) is used when it exists (D-109).
 */
class GuideBuilder
{
    /**
     * @param array<string, array{title: string, description: string, group: string|null, icon: string}> $roles
     */
    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly array $roles,
    ) {
    }

    /**
     * @return array<string, array{title: string, description: string, group: string|null, icon: string}>
     */
    public function roles(): array
    {
        return $this->roles;
    }

    /**
     * Guide files that make up the page for a role slug (Guide/<audience>.md).
     *
     * @return list<string> Absolute paths
     */
    public function files(string $slug, ?string $locale = null): array
    {
        $locale ??= function_exists('locale_current') ? locale_current() : 'id';

        if (! isset($this->roles[$slug])) {
            return [];
        }

        $audiences = [$slug];

        if ($this->roles[$slug]['group'] !== null) {
            $audiences[] = 'semua-petugas';
        }

        if ($slug === 'super-admin') {
            $audiences[] = 'admin-sekolah';
        }

        $modules = $this->modules->enabled();
        usort($modules, static fn (string $a, string $b): int => [$a !== 'Panduan', $a] <=> [$b !== 'Panduan', $b]);

        $files = [];

        foreach ($modules as $module) {
            foreach ($audiences as $audience) {
                $file = $this->modules->file($module, 'Guide/' . $audience . '.md');

                if ($file !== null) {
                    $files[] = self::translated($file, $locale);
                }

                // Optional sections in Guide/<audience>/*.md, in file name order, so
                // several work packages can each own one part of a module's guide.
                $folder = $this->modules->directory($module, 'Guide/' . $audience);

                if ($folder !== null) {
                    $parts = array_filter(glob($folder . '/*.md') ?: [], static fn (string $part): bool => preg_match('/\.[a-z]{2}\.md\z/', $part) !== 1);
                    sort($parts);
                    array_push($files, ...array_map(static fn (string $part): string => self::translated($part, $locale), $parts));
                }
            }
        }

        return $files;
    }

    /**
     * The translation of a guide file next to it (calon-murid.en.md for calon-murid.md, D-109), or the
     * Indonesian file when there is none.
     */
    public static function translated(string $file, string $locale): string
    {
        if ($locale === 'id') {
            return $file;
        }

        $candidate = substr($file, 0, -3) . '.' . $locale . '.md';

        return is_file($candidate) ? $candidate : $file;
    }

    /**
     * @return array{html: string, toc: list<array{id: string, label: string}>}
     */
    public function render(string $slug): array
    {
        $markdown = '';

        foreach ($this->files($slug) as $file) {
            $markdown .= trim((string) file_get_contents($file)) . "\n\n";
        }

        $markdown = strtr($markdown, [
            '{nama_sekolah}' => (string) setting('School.name'),
            '{url_masuk}'    => site_url('masuk'),
            '{url_situs}'    => base_url('/'),
        ]);

        $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $html      = (string) $converter->convert($markdown);
        // Links to pages of this website ("/daftar") keep the page's language and a sub-folder install.
        $html = (string) preg_replace_callback('#href="/(?!/)([^"]*)"#', static fn (array $m): string => 'href="' . esc(site_url(html_entity_decode($m[1], ENT_QUOTES)), 'attr') . '"', $html);
        $toc  = [];
        $used = [];

        $html = (string) preg_replace_callback('#<h2>(.*?)</h2>#s', static function (array $m) use (&$toc, &$used): string {
            $label = trim(strip_tags($m[1]));
            $id    = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($label)), '-') ?: 'bagian';
            $base  = $id;

            for ($i = 2; isset($used[$id]); $i++) {
                $id = $base . '-' . $i;
            }

            $used[$id] = true;
            $toc[]     = ['id' => $id, 'label' => html_entity_decode($label, ENT_QUOTES)];

            return '<h2 id="' . $id . '">' . $m[1] . '</h2>';
        }, $html);

        return ['html' => $html, 'toc' => $toc];
    }
}
