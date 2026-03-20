<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Theme\Actions;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

final class RemoveNavigationFallback implements Hooks
{

    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('block_core_navigation_render_fallback', '__return_false');
    }
}
