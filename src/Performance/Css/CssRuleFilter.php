<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Css;

/**
 * Filters CSS rules by removing those whose selectors are not used in the markup.
 *
 * Extracted from RemoveUnusedCss to respect the Single Responsibility Principle:
 * this class handles CSS parsing and rule filtering only.
 */
final class CssRuleFilter
{
    /**
     * Filter CSS rules, keeping only those whose selectors match used elements.
     *
     * @param array{classes: array<string, true>, ids: array<string, true>, tags: array<string, true>} $selectors
     */
    public static function filter(string $css, array $selectors): string
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

            if (SelectorMatcher::isSelectorUsed($selector, $selectors)) {
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
}
