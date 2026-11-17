<?php

declare(strict_types=1);

namespace Modules\Panduan\Libraries;

use App\Libraries\ModuleRegistry;
use League\CommonMark\CommonMarkConverter;

/**
 * Assembles a role's guide from the Markdown files in each enabled module's
 * Guide/ folder. The Panduan module's own file comes first, then the other
 * modules in name order.
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
    public function files(string $slug): array
    {
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
                    $files[] = $file;
                }
            }
        }

        return $files;
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
        $toc       = [];
        $used      = [];

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
