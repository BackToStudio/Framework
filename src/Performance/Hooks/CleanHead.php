<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Remove unnecessary tags from wp_head output.
 *
 * Cleans: RSD link, WLW manifest, shortlinks, REST API link,
 * adjacent post links, WP generator, feed links, and s.w.org DNS prefetch.
 */
class CleanHead implements Hooks
{
    private HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->removeAction('wp_head', 'rsd_link');
        $this->hookDispatcher->removeAction('wp_head', 'wlwmanifest_link');
        $this->hookDispatcher->removeAction('wp_head', 'wp_shortlink_wp_head');
        $this->hookDispatcher->removeAction('wp_head', 'rest_output_link_wp_head');
        $this->hookDispatcher->removeAction('wp_head', 'wp_oembed_add_discovery_links');
        $this->hookDispatcher->removeAction('wp_head', 'adjacent_posts_rel_link_wp_head');
        $this->hookDispatcher->removeAction('wp_head', 'wp_generator');
        $this->hookDispatcher->removeAction('wp_head', 'feed_links', 2);
        $this->hookDispatcher->removeAction('wp_head', 'feed_links_extra', 3);
        $this->hookDispatcher->addFilter('wp_resource_hints', [$this, 'removeSWOrgDnsPrefetch'], 10, 2);
    }

    /**
     * Remove dns-prefetch for s.w.org (WordPress emoji CDN).
     *
     * @param string[] $hints
     * @return string[]
     */
    public function removeSWOrgDnsPrefetch(array $hints, string $relationType): array
    {
        if ($relationType !== 'dns-prefetch') {
            return $hints;
        }

        return array_values(array_filter($hints, function (string $hint): bool {
            return !str_contains($hint, 's.w.org');
        }));
    }
}
