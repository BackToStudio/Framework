<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Performance\Contracts\HtmlOptimizerInterface;

/**
 * Minify HTML output via output buffering.
 *
 * Reduces HTML transfer size by 15-25% by removing whitespace and comments.
 */
class MinifyHtml implements Hooks
{
    private HookDispatcherInterface $hookDispatcher;
    private HtmlOptimizerInterface $htmlOptimizer;

    public function __construct(HookDispatcherInterface $hookDispatcher, HtmlOptimizerInterface $htmlOptimizer)
    {
        $this->hookDispatcher = $hookDispatcher;
        $this->htmlOptimizer = $htmlOptimizer;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('template_redirect', [$this, 'startBuffering']);
    }

    public function startBuffering(): void
    {
        if (\is_admin()) {
            return;
        }

        \ob_start([$this, 'minifyOutput']);
    }

    public function minifyOutput(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        return $this->htmlOptimizer->optimize($html);
    }
}
