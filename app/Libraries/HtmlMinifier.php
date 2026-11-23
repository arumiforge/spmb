<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Small, safe HTML minifier for every page the app sends (D-094). The view files stay as they are;
 * only the response body shrinks.
 *
 * - Every run of whitespace that contains a line break becomes one line break, so the indentation
 *   disappears while the page renders exactly the same (HTML treats both as one space).
 * - HTML comments are removed, except conditional comments.
 * - Left untouched: <pre>, <textarea>, <script>, <style> and elements with a `whitespace-pre*` class,
 *   where whitespace matters.
 */
final class HtmlMinifier
{
    private const PROTECTED = '~<(pre|textarea|script|style)\b[^>]*>.*?</\1\s*>'
        . '|<([a-z][a-z0-9]*)\b[^>]*\bclass="[^"]*\bwhitespace-pre[^"]*"[^>]*>.*?</\2\s*>~is';

    public static function minify(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $kept     = [];
        $original = $html;
        $html     = preg_replace_callback(self::PROTECTED, static function (array $m) use (&$kept): string {
            $kept[] = $m[0];

            return "\x00" . (count($kept) - 1) . "\x00";
        }, $html);

        if ($html === null) {
            return $original;
        }

        $html = preg_replace('/<!--(?!\[if|<!|>).*?-->/s', '', $html) ?? $html;
        $html = preg_replace('/[ \t\r]*\n\s*/', "\n", $html) ?? $html;
        $html = trim($html);

        return preg_replace_callback('/\x00(\d+)\x00/', static fn (array $m): string => $kept[(int) $m[1]], $html) ?? $html;
    }
}
