<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Contracts;

/**
 * Port for HTML output optimization.
 */
interface HtmlOptimizerInterface
{
    /**
     * Optimize the HTML content (minification, cleanup).
     */
    public function optimize(string $html): string;
}
