<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Infrastructure;

use BackTo\Framework\Performance\Contracts\HtmlOptimizerInterface;

/**
 * WordPress adapter for HTML minification.
 *
 * Reduces HTML transfer size by:
 * - Removing HTML comments (except IE conditionals and script/style content)
 * - Collapsing multiple whitespace into single spaces
 * - Removing unnecessary whitespace between tags
 * - Removing optional type attributes from script/style tags
 */
class WordPressHtmlOptimizer implements HtmlOptimizerInterface
{
    public function optimize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = $this->preserveAndProcess($html);

        return $html;
    }

    private function preserveAndProcess(string $html): string
    {
        $preserved = [];
        $index = 0;

        // Preserve <pre>, <code>, <textarea>, <script>, <style> content (only non-empty)
        $html = (string) preg_replace_callback(
            '/<(pre|code|textarea|script|style)\b[^>]*>.+?<\/\1>/is',
            function (array $matches) use (&$preserved, &$index): string {
                $placeholder = '<!--PRESERVED_' . $index . '-->';
                $preserved[$placeholder] = $matches[0];
                $index++;

                return $placeholder;
            },
            $html
        );

        // Remove HTML comments (but not IE conditionals or preserved placeholders)
        $html = (string) preg_replace('/<!--(?!\[if\s|PRESERVED_).*?-->/s', '', $html);

        // Collapse whitespace between tags
        $html = (string) preg_replace('/>\s+</', '> <', $html);

        // Collapse multiple whitespace into single space
        $html = (string) preg_replace('/\s{2,}/', ' ', $html);

        // Remove type="text/javascript" and type="text/css"
        $html = str_replace([' type="text/javascript"', ' type="text/css"', " type='text/javascript'", " type='text/css'"], '', $html);

        // Restore preserved blocks
        $html = str_replace(array_keys($preserved), array_values($preserved), $html);

        return trim($html);
    }
}
