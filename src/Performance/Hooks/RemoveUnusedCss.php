<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Remove unused CSS rules from inline <style> blocks.
 *
 * Analyzes the HTML markup to find which CSS selectors are actually referenced,
 * then strips unused rules from inline style blocks. This is especially effective
 * with block themes like Twenty Twenty-Five that emit per-block inline CSS.
 *
 * Hooks into template_redirect at a lower priority than MinifyHtml so it runs
 * on the raw HTML before minification.
 */
final class RemoveUnusedCss implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly bool $enabled;

    /** @var string[] CSS block IDs to never touch */
    private readonly array $preserveIds;

    /**
     * @param string[] $preserveIds Style block IDs to never strip (e.g., 'global-styles-inline-css')
     */
    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        bool $enabled = false,
        array $preserveIds = ['global-styles-inline-css']
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->enabled = $enabled;
        $this->preserveIds = $preserveIds;
    }

    public function hooks(): void
    {
        if (!$this->enabled) {
            return;
        }

        // Priority 9: run before MinifyHtml (default 10)
        $this->hookDispatcher->addAction('template_redirect', [$this, 'startBuffering'], 9);
    }

    public function startBuffering(): void
    {
        if (\is_admin()) {
            return;
        }

        \ob_start([$this, 'removeUnused']);
    }

    public function removeUnused(string $html): string
    {
        if ($html === '' || !str_contains($html, '<style')) {
            return $html;
        }

        return self::process($html, $this->preserveIds);
    }

    /**
     * Process HTML and remove unused CSS rules from inline style blocks.
     *
     * Public static so it can be called directly in tests/benchmarks without hooks.
     *
     * @param string[] $preserveIds
     */
    public static function process(string $html, array $preserveIds = ['global-styles-inline-css']): string
    {
        // 1. Extract the markup (HTML without style blocks) to determine what's used
        $markupOnly = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $selectors = self::extractUsedSelectors($markupOnly);

        // 2. Process each inline <style> block
        $html = (string) preg_replace_callback(
            '/<style\b([^>]*)>(.*?)<\/style>/is',
            function (array $matches) use ($selectors, $preserveIds): string {
                $attrs = $matches[1];
                $css = $matches[2];

                // Check if this block should be preserved entirely
                if (preg_match('/id=["\']([^"\']+)/', $attrs, $idMatch)) {
                    if (in_array($idMatch[1], $preserveIds, true)) {
                        return $matches[0];
                    }
                }

                $filtered = self::filterCssRules($css, $selectors);

                // If all rules were removed, remove the entire style block
                if (trim($filtered) === '') {
                    return '';
                }

                return '<style' . $attrs . '>' . $filtered . '</style>';
            },
            $html
        );

        return $html;
    }

    /**
     * Extract all selectors that are referenced in the HTML markup.
     *
     * @return array{classes: array<string, true>, ids: array<string, true>, tags: array<string, true>}
     */
    private static function extractUsedSelectors(string $markup): array
    {
        $classes = [];
        $ids = [];
        $tags = [];

        // Classes
        preg_match_all('/class=["\']([^"\']+)/', $markup, $classMatches);
        foreach ($classMatches[1] as $classList) {
            foreach (preg_split('/\s+/', $classList) as $cls) {
                if ($cls !== '') {
                    $classes[$cls] = true;
                }
            }
        }

        // IDs
        preg_match_all('/id=["\']([^"\']+)/', $markup, $idMatches);
        foreach ($idMatches[1] as $id) {
            $ids[$id] = true;
        }

        // Tags
        preg_match_all('/<([a-z][a-z0-9]*)\b/i', $markup, $tagMatches);
        foreach ($tagMatches[1] as $tag) {
            $tags[strtolower($tag)] = true;
        }

        return ['classes' => $classes, 'ids' => $ids, 'tags' => $tags];
    }

    /**
     * Filter CSS rules, keeping only those whose selectors match used elements.
     *
     * @param array{classes: array<string, true>, ids: array<string, true>, tags: array<string, true>} $selectors
     */
    private static function filterCssRules(string $css, array $selectors): string
    {
        $result = '';
        $pos = 0;
        $len = strlen($css);

        while ($pos < $len) {
            // Skip whitespace
            while ($pos < $len && ctype_space($css[$pos])) {
                $pos++;
            }

            if ($pos >= $len) {
                break;
            }

            // Handle @-rules (always keep)
            if ($css[$pos] === '@') {
                $atRule = self::extractAtRule($css, $pos);
                $result .= $atRule;
                $pos += strlen($atRule);

                continue;
            }

            // Find the selector (everything up to {)
            $bracePos = strpos($css, '{', $pos);
            if ($bracePos === false) {
                break;
            }

            $selector = trim(substr($css, $pos, $bracePos - $pos));

            // Find the matching closing brace
            $closePos = self::findClosingBrace($css, $bracePos);
            if ($closePos === false) {
                break;
            }

            $fullRule = substr($css, $pos, $closePos - $pos + 1);

            if (self::isSelectorUsed($selector, $selectors)) {
                $result .= $fullRule;
            }

            $pos = $closePos + 1;
        }

        return $result;
    }

    /**
     * Extract a full @-rule (including nested braces for @media, @supports, etc.).
     */
    private static function extractAtRule(string $css, int $pos): string
    {
        $bracePos = strpos($css, '{', $pos);
        $semicolonPos = strpos($css, ';', $pos);

        // @-rules without braces (e.g., @import, @charset)
        if ($bracePos === false || ($semicolonPos !== false && $semicolonPos < $bracePos)) {
            return substr($css, $pos, $semicolonPos - $pos + 1);
        }

        // @-rules with braces — find matching close
        $closePos = self::findClosingBrace($css, $bracePos);
        if ($closePos === false) {
            return substr($css, $pos);
        }

        return substr($css, $pos, $closePos - $pos + 1);
    }

    /**
     * Find the position of the closing brace matching the opening brace at $openPos.
     */
    private static function findClosingBrace(string $css, int $openPos): int|false
    {
        $depth = 0;
        $len = strlen($css);

        for ($i = $openPos; $i < $len; $i++) {
            if ($css[$i] === '{') {
                $depth++;
            } elseif ($css[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return false;
    }

    /**
     * Determine if a CSS selector is used in the page markup.
     *
     * @param array{classes: array<string, true>, ids: array<string, true>, tags: array<string, true>} $selectors
     */
    private static function isSelectorUsed(string $selector, array $selectors): bool
    {
        // Universal selectors, :root — always used
        if (trim($selector) === '*' || preg_match('/^(:root|html|body)\b/', trim($selector))) {
            return true;
        }

        // CSS custom property declarations — always keep
        if (str_contains($selector, '--')) {
            return true;
        }

        // Multiple selectors (comma-separated): keep if ANY is used
        $parts = explode(',', $selector);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || $part === '*') {
                return true;
            }

            if (self::isSingleSelectorUsed($part, $selectors)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a single (non-comma) selector matches elements in the markup.
     *
     * For compound selectors like `.foo.bar`, ALL classes must be present.
     * For descendant selectors like `.foo .bar`, we check the last part (the subject).
     *
     * @param array{classes: array<string, true>, ids: array<string, true>, tags: array<string, true>} $selectors
     */
    private static function isSingleSelectorUsed(string $selector, array $selectors): bool
    {
        $parts = preg_split('/\s*[>~+\s]\s*/', $selector);

        if ($parts === false || $parts === []) {
            return false;
        }

        $subject = end($parts);
        $subject = preg_replace('/:{1,2}[a-zA-Z-]+(\([^)]*\))?/', '', $subject);

        return self::matchesClassSelectors($subject, $selectors['classes'])
            ?? self::matchesIdSelectors($subject, $selectors['ids'])
            ?? self::matchesTagSelectors($subject, $selectors['tags'])
            ?? false;
    }

    /**
     * @param array<string, true> $usedClasses
     */
    private static function matchesClassSelectors(string $subject, array $usedClasses): ?bool
    {
        preg_match_all('/\.([a-zA-Z_-][a-zA-Z0-9_-]*)/', $subject, $matches);
        $classes = $matches[1] ?? [];

        if (empty($classes)) {
            return null; // No class selectors, try next matcher
        }

        foreach ($classes as $cls) {
            if (!isset($usedClasses[$cls])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, true> $usedIds
     */
    private static function matchesIdSelectors(string $subject, array $usedIds): ?bool
    {
        preg_match_all('/#([a-zA-Z_-][a-zA-Z0-9_-]*)/', $subject, $matches);

        foreach ($matches[1] as $id) {
            if (isset($usedIds[$id])) {
                return true;
            }
        }

        return null;
    }

    /**
     * @param array<string, true> $usedTags
     */
    private static function matchesTagSelectors(string $subject, array $usedTags): ?bool
    {
        $cleaned = preg_replace('/[.#][a-zA-Z_-][a-zA-Z0-9_-]*/', '', $subject);
        preg_match_all('/\b([a-z][a-z0-9]*)\b/i', $cleaned, $matches);

        foreach ($matches[1] as $tag) {
            if (isset($usedTags[strtolower($tag)])) {
                return true;
            }
        }

        return null;
    }
}
