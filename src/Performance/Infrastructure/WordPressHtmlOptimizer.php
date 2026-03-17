<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Infrastructure;

use BackTo\Framework\Performance\Contracts\HtmlOptimizerInterface;

/**
 * WordPress adapter for HTML minification.
 *
 * Reduces HTML transfer size by:
 * - Minifying inline CSS (collapse whitespace, remove comments, strip last semicolons)
 * - Minifying inline JS (collapse whitespace while preserving strings/regex)
 * - Removing HTML comments (except IE conditionals)
 * - Collapsing whitespace between block-level tags (><)
 * - Collapsing whitespace between inline tags (> <)
 * - Removing optional type attributes from script/style tags
 */
final class WordPressHtmlOptimizer implements HtmlOptimizerInterface
{
    /**
     * Block-level elements where whitespace between closing/opening tags is insignificant.
     */
    private const BLOCK_ELEMENTS = 'html|head|body|header|footer|main|nav|aside|section|article|div|p|ul|ol|li|table|tr|td|th|thead|tbody|tfoot|form|fieldset|figure|figcaption|details|summary|h[1-6]|meta|link|title|script|style|noscript';

    public function optimize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        return $this->process($html);
    }

    private function process(string $html): string
    {
        $preserved = [];
        $index = 0;

        // 1. Preserve <pre>, <code>, <textarea> content verbatim
        $html = (string) preg_replace_callback(
            '/<(pre|code|textarea)\b[^>]*>.+?<\/\1>/is',
            function (array $matches) use (&$preserved, &$index): string {
                $placeholder = '<!--PRESERVED_' . $index . '-->';
                $preserved[$placeholder] = $matches[0];
                $index++;

                return $placeholder;
            },
            $html
        );

        // 2. Minify inline <style> blocks
        $html = (string) preg_replace_callback(
            '/<style\b([^>]*)>(.+?)<\/style>/is',
            function (array $matches) use (&$preserved, &$index): string {
                $attrs = $matches[1];
                $css = $this->minifyCss($matches[2]);
                $placeholder = '<!--PRESERVED_' . $index . '-->';
                $preserved[$placeholder] = '<style' . $attrs . '>' . $css . '</style>';
                $index++;

                return $placeholder;
            },
            $html
        );

        // 3. Minify inline <script> blocks (non-JSON, non-importmap)
        $html = (string) preg_replace_callback(
            '/<script\b([^>]*)>(.+?)<\/script>/is',
            function (array $matches) use (&$preserved, &$index): string {
                $attrs = $matches[1];
                $content = $matches[2];

                // Don't minify JSON-LD, importmaps, or module preloads — they're data, not code
                $isData = preg_match('/type\s*=\s*["\'](?:application\/(?:ld\+json|json)|importmap)["\']/i', $attrs);

                if (!$isData) {
                    $content = $this->minifyInlineJs($content);
                }

                $placeholder = '<!--PRESERVED_' . $index . '-->';
                $preserved[$placeholder] = '<script' . $attrs . '>' . $content . '</script>';
                $index++;

                return $placeholder;
            },
            $html
        );

        // 4. Remove HTML comments (but not IE conditionals or preserved placeholders)
        $html = (string) preg_replace('/<!--(?!\[if\s|PRESERVED_).*?-->/s', '', $html);

        // 5. Collapse whitespace between block-level tags: >\s+< → ><
        $blockPattern = self::BLOCK_ELEMENTS;
        $html = (string) preg_replace(
            '/(<\/(?:' . $blockPattern . ')>)\s+(<(?:' . $blockPattern . ')[\s>])/i',
            '$1$2',
            $html
        );
        $html = (string) preg_replace(
            '/(<\/(?:' . $blockPattern . ')>)\s+(<\/)/i',
            '$1$2',
            $html
        );
        // Opening block tag followed by whitespace then another tag
        $html = (string) preg_replace(
            '/(>)\s+(<!--PRESERVED_)/i',
            '$1$2',
            $html
        );

        // 6. Collapse remaining whitespace between tags (preserve single space for inline)
        $html = (string) preg_replace('/>\s{2,}</', '> <', $html);

        // 7. Collapse multiple whitespace into single space
        $html = (string) preg_replace('/\s{2,}/', ' ', $html);

        // 8. Remove type="text/javascript" and type="text/css"
        $html = str_replace(
            [' type="text/javascript"', ' type="text/css"', " type='text/javascript'", " type='text/css'"],
            '',
            $html
        );

        // 9. Restore preserved blocks
        $html = str_replace(array_keys($preserved), array_values($preserved), $html);

        return trim($html);
    }

    /**
     * Minify CSS content.
     *
     * - Remove CSS comments
     * - Collapse whitespace
     * - Remove whitespace around selectors/braces/colons/semicolons
     * - Remove trailing semicolons before closing braces
     * - Collapse zero units
     */
    private function minifyCss(string $css): string
    {
        // Remove CSS comments
        $css = (string) preg_replace('/\/\*.*?\*\//s', '', $css);

        // Collapse whitespace
        $css = (string) preg_replace('/\s+/', ' ', $css);

        // Remove spaces around { } : ; ,
        $css = (string) preg_replace('/\s*([{};:,>~+])\s*/', '$1', $css);

        // Remove trailing semicolons before }
        $css = str_replace(';}', '}', $css);

        // Collapse "0px" → "0" (but not in expressions like "10px")
        $css = (string) preg_replace('/(?<=[\s:,])0(?:px|em|rem|%|vh|vw|vmin|vmax|ex|ch|cm|mm|in|pt|pc)\b/', '0', $css);

        // Remove leading zeros in decimals: 0.5 → .5
        $css = (string) preg_replace('/(?<=[\s:,])0+\./', '.', $css);

        // Shorten hex colors: #aabbcc → #abc
        $css = (string) preg_replace_callback(
            '/#([0-9a-fA-F])\1([0-9a-fA-F])\2([0-9a-fA-F])\3\b/',
            fn(array $m) => '#' . $m[1] . $m[2] . $m[3],
            $css
        );

        return trim($css);
    }

    /**
     * Minify inline JavaScript (safe, conservative approach).
     *
     * Preserves string literals by extracting them first, minifying
     * the code structure, then restoring strings.
     */
    private function minifyInlineJs(string $js): string
    {
        $strings = [];
        $idx = 0;

        // Extract string literals (double-quoted, single-quoted) to protect them
        $js = (string) preg_replace_callback(
            '/("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')/',
            function (array $m) use (&$strings, &$idx): string {
                $placeholder = "\x00STR_{$idx}\x00";
                $strings[$placeholder] = $m[0];
                $idx++;

                return $placeholder;
            },
            $js
        );

        // Remove single-line comments (// ...) but not URLs (://)
        $js = (string) preg_replace('/(?<![:\\\])\/\/[^\n]*/', '', $js);

        // Remove multi-line comments (/* ... */) but not legal/license ones (/*! ... */)
        $js = (string) preg_replace('/\/\*(?!\!)[^*]*\*+(?:[^\/][^*]*\*+)*\//', '', $js);

        // Collapse spaces/tabs
        $js = (string) preg_replace('/[ \t]+/', ' ', $js);

        // Collapse multiple newlines to one
        $js = (string) preg_replace('/\n\s*\n/', "\n", $js);

        // Remove spaces around operators and punctuation
        $js = (string) preg_replace('/\s*([{};,=:?<>!&|+\-*\/^~%()])\s*/', '$1', $js);

        // Restore needed space after keywords
        $js = (string) preg_replace(
            '/\b(var|let|const|return|typeof|instanceof|new|delete|throw|case|in|of|void|yield|await|async|function|class|extends|import|export|from|default)\b(?=[^\s;,)}])/',
            '$1 ',
            $js
        );

        // Restore space between ) and identifiers: )function → ) function
        $js = (string) preg_replace('/\)(?=\b)/', ') ', $js);

        // Restore string literals
        $js = str_replace(array_keys($strings), array_values($strings), $js);

        return trim($js);
    }
}
