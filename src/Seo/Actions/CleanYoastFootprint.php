<?php

namespace BackTo\Framework\Seo\Actions;

use BackTo\Framework\Contracts\Hooks;

/**
 * Remove Yoast SEO debug markers and version information from the HTML output.
 */
class CleanYoastFootprint implements Hooks
{
    public function hooks()
    {
        \add_filter('wpseo_debug_markers', '__return_false');
        \add_filter('wpseo_hide_version', '__return_true');
    }
}
