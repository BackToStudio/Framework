<?php

namespace BackTo\Framework\Theme\Actions;

use BackTo\Framework\Contracts\Hooks;

use function add_filter;

class RemoveNavigationFallback implements Hooks
{

    public function hooks()
    {
        add_filter('block_core_navigation_render_fallback', '__return_false');
    }
}
