<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Performance\Css\CssRuleFilter;
use BackTo\Framework\Performance\Css\HtmlSelectorExtractor;

/**
 * Remove unused CSS rules from inline <style> blocks.
 *
 * Analyzes the HTML markup to find which CSS selectors are actually referenced,
 * then strips unused rules from inline style blocks. This is especially effective
 * with block themes like Twenty Twenty-Five that emit per-block inline CSS.
 *
 * Hooks into template_redirect at a lower priority than MinifyHtml so it runs
 * on the raw HTML before minification.
 *
 * CSS parsing, selector extraction and matching are delegated to:
 * - {@see HtmlSelectorExtractor} for HTML analysis
 * - {@see CssRuleFilter} for CSS rule filtering
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
        $markupOnly = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $selectors = HtmlSelectorExtractor::extract($markupOnly);

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

                $filtered = CssRuleFilter::filter($css, $selectors);

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
}
