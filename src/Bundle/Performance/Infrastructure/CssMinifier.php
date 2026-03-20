<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Infrastructure;

/**
 * Minifies CSS content.
 *
 * - Removes CSS comments
 * - Collapses whitespace
 * - Removes whitespace around selectors/braces/colons/semicolons
 * - Removes trailing semicolons before closing braces
 * - Collapses zero units and shortens hex colors
 */
final class CssMinifier
{
    public function minify(string $css): string
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
}
