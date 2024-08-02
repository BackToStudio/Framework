<?php

namespace BackTo\Framework\Theme\Actions;

use BackTo\Framework\Contracts\Hooks;

use function remove_action;

class RemoveSvgFilters implements Hooks
{

    public function hooks()
    {
        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
        remove_action('wp_body_open', 'gutenberg_global_styles_render_svg_filters');
    }
}
