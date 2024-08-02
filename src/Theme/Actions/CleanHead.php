<?php

namespace BackTo\Framework\Theme\Actions;

use BackTo\Framework\Contracts\Hooks;

use function remove_action;

class CleanHead implements Hooks
{

    public function hooks()
    {
        // Remove the links to the extra feeds such as category feeds
        remove_action('wp_head', 'feed_links_extra', 3);
        // Remove the links to the general feeds: Post and Comment Feed
        remove_action('wp_head', 'feed_links', 2);
        // Remove the link to the Really Simple Discovery service endpoint, EditURI link
        remove_action('wp_head', 'rsd_link');
        // Remove the link to the Windows Live Writer manifest file.
        remove_action('wp_head', 'wlwmanifest_link');
        // Remove index link
        remove_action('wp_head', 'index_rel_link');
        // Remove prev link
        remove_action('wp_head', 'parent_post_rel_link', 10, 0);
        // Remove start link
        remove_action('wp_head', 'start_post_rel_link', 10, 0);
        // Remove relational links for the posts adjacent to the current post.
        remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
    }
}
