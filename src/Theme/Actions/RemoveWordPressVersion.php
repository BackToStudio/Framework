<?php

namespace BackTo\Framework\Theme\Actions;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

class RemoveWordPressVersion implements Hooks
{

    private HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        // Remove WordPress version.
        $this->hookDispatcher->removeAction('wp_head', 'wp_generator');
        // Remove the WordPress version from RSS feeds.
        $this->hookDispatcher->addFilter('the_generator', '__return_false');
    }
}
