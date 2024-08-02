<?php

namespace BackTo\Framework\Theme\Actions;

use BackTo\Framework\Contracts\Hooks;

use function add_filter;

class RemoveYoastFootprint implements Hooks
{

    public function hooks()
    {
        add_filter('wpseo_debug_markers', '__return_false');
        add_filter('wpseo_hide_version', '__return_true');
    }
}
