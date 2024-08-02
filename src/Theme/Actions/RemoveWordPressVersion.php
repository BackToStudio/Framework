<?php

namespace BackTo\Framework\Theme\Actions;

use BackTo\Framework\Contracts\Hooks;

use function remove_action;
use function add_filter;

class RemoveWordPressVersion implements Hooks
{

    public function hooks()
    {
        // Remove WordPress version.
        remove_action('wp_head', 'wp_generator');
        // Remove the WordPress version from RSS feeds.
        add_filter('the_generator', '__return_false');
    }
}
