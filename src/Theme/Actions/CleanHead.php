<?php

declare(strict_types=1);

namespace BackTo\Framework\Theme\Actions;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

final class CleanHead implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        // Remove the links to the extra feeds such as category feeds
        $this->hookDispatcher->removeAction('wp_head', 'feed_links_extra', 3);
        // Remove the links to the general feeds: Post and Comment Feed
        $this->hookDispatcher->removeAction('wp_head', 'feed_links', 2);
        // Remove the link to the Really Simple Discovery service endpoint, EditURI link
        $this->hookDispatcher->removeAction('wp_head', 'rsd_link');
        // Remove the link to the Windows Live Writer manifest file.
        $this->hookDispatcher->removeAction('wp_head', 'wlwmanifest_link');
        // Remove index link
        $this->hookDispatcher->removeAction('wp_head', 'index_rel_link');
        // Remove prev link
        $this->hookDispatcher->removeAction('wp_head', 'parent_post_rel_link', 10);
        // Remove start link
        $this->hookDispatcher->removeAction('wp_head', 'start_post_rel_link', 10);
        // Remove relational links for the posts adjacent to the current post.
        $this->hookDispatcher->removeAction('wp_head', 'adjacent_posts_rel_link', 10);
    }
}
