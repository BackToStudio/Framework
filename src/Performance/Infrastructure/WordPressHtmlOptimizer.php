<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Infrastructure;

use BackTo\Framework\Performance\Contracts\HtmlOptimizerInterface;

/**
 * WordPress adapter for HTML minification.
 *
 * Reduces HTML transfer size by delegating to focused minifiers:
 * - CssMinifier: inline CSS optimization
 * - JsMinifier: inline JS optimization
 * - This class: HTML structure optimization (comments, whitespace, type attributes)
 */
final class WordPressHtmlOptimizer implements HtmlOptimizerInterface
{
    /**
     * Block-level elements where whitespace between closing/opening tags is insignificant.
     */
    private const BLOCK_ELEMENTS = 'html|head|body|header|footer|main|nav|aside|section|article|div|p|ul|ol|li|table|tr|td|th|thead|tbody|tfoot|form|fieldset|figure|figcaption|details|summary|h[1-6]|meta|link|title|script|style|noscript';

    private readonly CssMinifier $cssMinifier;
    private readonly JsMinifier $jsMinifier;

    public function __construct(?CssMinifier $cssMinifier = null, ?JsMinifier $jsMinifier = null)
    {
        $this->cssMinifier = $cssMinifier ?? new CssMinifier();
        $this->jsMinifier = $jsMinifier ?? new JsMinifier();
    }

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
                $css = $this->cssMinifier->minify($matches[2]);
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
                    $content = $this->jsMinifier->minify($content);
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
}
