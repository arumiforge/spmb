<?php

declare(strict_types=1);

namespace Modules\Situs\Libraries;

use League\CommonMark\CommonMarkConverter;

/**
 * Turns an answer written in Markdown into safe HTML for /tanya-jawab.
 *
 * Raw HTML is removed and unsafe links (javascript:, data:) are dropped, as in
 * the guide pages. Links that start with "/" point into this site, so they keep
 * working when the app runs in a sub-folder. Placeholders:
 * {nama_sekolah}, {tahun_ajaran}.
 *
 * With $keepLineBreaks (popup text) a single line break stays a line break
 * instead of joining the lines.
 */
class AnswerRenderer
{
    private ?CommonMarkConverter $converter = null;

    public function __construct(private readonly bool $keepLineBreaks = false)
    {
    }

    public function render(string $markdown): string
    {
        $markdown = strtr($markdown, [
            '{nama_sekolah}' => (string) setting('School.name'),
            '{tahun_ajaran}' => (string) setting('Pendaftaran.academicYear'),
        ]);

        $this->converter ??= new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]
            + ($this->keepLineBreaks ? ['renderer' => ['soft_break' => "<br>\n"]] : []));
        $html = (string) $this->converter->convert($markdown);

        return (string) preg_replace_callback(
            '#href="/(?!/)([^"]*)"#',
            static fn (array $m): string => 'href="' . esc(site_url(html_entity_decode($m[1], ENT_QUOTES)), 'attr') . '"',
            $html,
        );
    }
}
