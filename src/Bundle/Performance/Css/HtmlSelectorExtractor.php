<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Css;

/**
 * Extracts CSS selectors (classes, IDs, tags) referenced in HTML markup.
 *
 * Extracted from RemoveUnusedCss to respect the Single Responsibility Principle:
 * this class handles HTML analysis only.
 */
final class HtmlSelectorExtractor
{
    /**
     * Extract all selectors that are referenced in the HTML markup.
     *
     * @return array{classes: array<string, true>, ids: array<string, true>, tags: array<string, true>}
     */
    public static function extract(string $markup): array
    {
        $classes = [];
        $ids = [];
        $tags = [];

        // Classes
        preg_match_all('/class=["\']([^"\']+)/', $markup, $classMatches);
        foreach ($classMatches[1] as $classList) {
            /** @var list<string> $parts */
            $parts = preg_split('/\s+/', $classList);
            foreach ($parts as $cls) {
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
}
