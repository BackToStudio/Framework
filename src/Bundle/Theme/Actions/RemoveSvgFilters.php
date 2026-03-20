<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Theme\Actions;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

final class RemoveSvgFilters implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->removeAction('wp_body_open', 'wp_global_styles_render_svg_filters');
        $this->hookDispatcher->removeAction('wp_body_open', 'gutenberg_global_styles_render_svg_filters');
    }
}
