<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Actions;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Remove Yoast SEO debug markers and version information from the HTML output.
 */
final class CleanYoastFootprint implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('wpseo_debug_markers', '__return_false');
        $this->hookDispatcher->addFilter('wpseo_hide_version', '__return_true');
    }
}
