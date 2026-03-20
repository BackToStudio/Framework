<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Css;

/**
 * Determines whether CSS selectors are referenced in the page markup.
 *
 * Extracted from RemoveUnusedCss to respect the Single Responsibility Principle:
 * this class handles selector matching only.
 */
final class SelectorMatcher
{
    /**
     * Determine if a CSS selector is used in the page markup.
     *
     * @param array{classes: array<string, true>, ids: array<string, true>, tags: array<string, true>} $selectors
     */
    public static function isSelectorUsed(string $selector, array $selectors): bool
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
        $subject = (string) preg_replace('/:{1,2}[a-zA-Z-]+(\([^)]*\))?/', '', $subject);

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
        $classes = $matches[1];

        if (empty($classes)) {
            return null;
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
        $cleaned = (string) preg_replace('/[.#][a-zA-Z_-][a-zA-Z0-9_-]*/', '', $subject);
        preg_match_all('/\b([a-z][a-z0-9]*)\b/i', $cleaned, $matches);

        foreach ($matches[1] as $tag) {
            if (isset($usedTags[strtolower($tag)])) {
                return true;
            }
        }

        return null;
    }
}
