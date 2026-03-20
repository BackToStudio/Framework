<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Infrastructure;

/**
 * Minifies inline JavaScript (safe, conservative approach).
 *
 * Preserves string literals by extracting them first, minifying
 * the code structure, then restoring strings.
 */
final class JsMinifier
{
    public function minify(string $js): string
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
