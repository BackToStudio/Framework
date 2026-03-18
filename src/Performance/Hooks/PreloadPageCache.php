<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Performance\Contracts\PageCacheInterface;

/**
 * Preload the page cache in the background after content changes.
 *
 * When a post is saved or published, this hook schedules a WP-Cron
 * event that re-fetches the affected URLs (the post itself plus
 * related pages like the homepage and archives) so the cache is warm
 * before the next visitor arrives.
 *
 * Flow:
 * 1. save_post / transition_post_status fires
 * 2. We schedule a single cron event (btf_preload_page_cache) in 5 seconds
 * 3. WP-Cron fires the event, which fetches each URL via a non-blocking
 *    loopback HTTP request, triggering ServePageCache to store fresh HTML
 */
final class PreloadPageCache implements Hooks
{
    public const CRON_HOOK = 'btf_preload_page_cache';
    public const CRON_FULL_HOOK = 'btf_preload_page_cache_full';

    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly PageCacheInterface $pageCache;

    /** @var int Delay in seconds before the cron event fires */
    private readonly int $delay;

    /** @var int Maximum URLs to preload per batch */
    private readonly int $batchSize;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        PageCacheInterface $pageCache,
        int $delay = 5,
        int $batchSize = 50
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->pageCache = $pageCache;
        $this->delay = $delay;
        $this->batchSize = $batchSize;
    }

    public function hooks(): void
    {
        // Schedule preload after content changes
        $this->hookDispatcher->addAction('save_post', [$this, 'schedulePostPreload'], 20);
        $this->hookDispatcher->addAction('transition_post_status', [$this, 'scheduleOnPublish'], 20, 3);

        // Schedule full preload after theme/customizer changes
        $this->hookDispatcher->addAction('switch_theme', [$this, 'scheduleFullPreload']);
        $this->hookDispatcher->addAction('customize_save_after', [$this, 'scheduleFullPreload']);

        // Execute the preload when cron fires
        $this->hookDispatcher->addAction(self::CRON_HOOK, [$this, 'executePostPreload']);
        $this->hookDispatcher->addAction(self::CRON_FULL_HOOK, [$this, 'executeFullPreload']);
    }

    /**
     * Schedule preloading of a specific post and its related pages.
     */
    public function schedulePostPreload(int $postId): void
    {
        $post = \get_post($postId);

        if (!$post instanceof \WP_Post) {
            return;
        }

        // Only preload published posts
        if ($post->post_status !== 'publish') {
            return;
        }

        // Skip revisions and auto-saves
        if (\wp_is_post_revision($postId) || \wp_is_post_autosave($postId)) {
            return;
        }

        // Don't pile up: clear any pending preload for this post
        \wp_clear_scheduled_hook(self::CRON_HOOK, [$postId]);

        // Schedule in {delay} seconds to let the cache invalidation settle
        \wp_schedule_single_event(time() + $this->delay, self::CRON_HOOK, [$postId]);

        // Spawn cron immediately so it doesn't wait for the next page load
        \spawn_cron();
    }

    /**
     * On publish/unpublish transitions, schedule a broader preload.
     *
     * @param \WP_Post $post
     */
    public function scheduleOnPublish(string $newStatus, string $oldStatus, $post): void
    {
        if ($newStatus === $oldStatus) {
            return;
        }

        // Only on transitions involving 'publish' (new publish or unpublish)
        if ($newStatus !== 'publish' && $oldStatus !== 'publish') {
            return;
        }

        $this->schedulePostPreload($post->ID);
    }

    /**
     * Schedule a full site preload (after theme change, customizer save, etc.).
     */
    public function scheduleFullPreload(): void
    {
        \wp_clear_scheduled_hook(self::CRON_FULL_HOOK);
        \wp_schedule_single_event(time() + $this->delay, self::CRON_FULL_HOOK);
        \spawn_cron();
    }

    /**
     * Execute the preload for a specific post and related pages.
     *
     * Called by WP-Cron in the background.
     */
    public function executePostPreload(int $postId): void
    {
        $urls = $this->getPostRelatedUrls($postId);
        $this->preloadUrls($urls);
    }

    /**
     * Execute a full site preload.
     *
     * Called by WP-Cron in the background after theme/customizer changes.
     */
    public function executeFullPreload(): void
    {
        $urls = $this->getSiteUrls();
        $this->preloadUrls($urls);
    }

    /**
     * Collect all URLs related to a specific post.
     *
     * @return string[]
     */
    public function getPostRelatedUrls(int $postId): array
    {
        $postType = \get_post_type($postId) ?: 'post';
        $post = \get_post($postId);

        return array_unique(array_filter(array_merge(
            $this->collectPermalinkUrl($postId),
            [\home_url('/')],
            $this->collectBlogPageUrl(),
            $this->collectPostTypeArchiveUrl($postType),
            $this->collectTaxonomyUrls($postId, $postType),
            $this->collectAuthorUrl($post),
            $this->collectDateArchiveUrls($post),
        )));
    }

    /** @return string[] */
    private function collectPermalinkUrl(int $postId): array
    {
        $permalink = \get_permalink($postId);

        return $permalink !== false ? [$permalink] : [];
    }

    /** @return string[] */
    private function collectBlogPageUrl(): array
    {
        $blogPageId = (int) \get_option('page_for_posts');

        if ($blogPageId <= 0) {
            return [];
        }

        $blogUrl = \get_permalink($blogPageId);

        return $blogUrl !== false ? [$blogUrl] : [];
    }

    /** @return string[] */
    private function collectPostTypeArchiveUrl(string $postType): array
    {
        if ($postType === 'page') {
            return [];
        }

        $archiveUrl = \get_post_type_archive_link($postType);

        return $archiveUrl !== false ? [$archiveUrl] : [];
    }

    /** @return string[] */
    private function collectTaxonomyUrls(int $postId, string $postType): array
    {
        $taxonomies = \get_object_taxonomies($postType, 'names');
        $terms = \wp_get_post_terms($postId, $taxonomies);

        if (!\is_array($terms)) {
            return [];
        }

        $urls = [];

        foreach ($terms as $term) {
            $termLink = \get_term_link($term);

            if (\is_string($termLink)) {
                $urls[] = $termLink;
            }
        }

        return $urls;
    }

    /** @return string[] */
    private function collectAuthorUrl(?\WP_Post $post): array
    {
        if (!$post instanceof \WP_Post) {
            return [];
        }

        $authorUrl = \get_author_posts_url($post->post_author);

        return $authorUrl !== false ? [$authorUrl] : [];
    }

    /** @return string[] */
    private function collectDateArchiveUrls(?\WP_Post $post): array
    {
        if (!$post instanceof \WP_Post) {
            return [];
        }

        $date = $post->post_date;
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));

        return [
            \get_year_link((int) $year),
            \get_month_link((int) $year, (int) $month),
        ];
    }

    /**
     * Collect the main site URLs for a full preload.
     *
     * @return string[]
     */
    public function getSiteUrls(): array
    {
        $urls = [\home_url('/')];
        $urls = array_merge($urls, $this->collectBlogPageUrl());

        // Recent published posts
        $recentPosts = \get_posts([
            'numberposts'      => $this->batchSize,
            'post_type'        => 'post',
            'post_status'      => 'publish',
            'orderby'          => 'date',
            'order'            => 'DESC',
            'suppress_filters' => true,
        ]);

        foreach ($recentPosts as $post) {
            $urls[] = \get_permalink($post->ID);
        }

        // Published pages
        $pages = \get_posts([
            'numberposts'      => $this->batchSize,
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'orderby'          => 'menu_order',
            'order'            => 'ASC',
            'suppress_filters' => true,
        ]);

        foreach ($pages as $page) {
            $urls[] = \get_permalink($page->ID);
        }

        // Category archives
        $categories = \get_categories(['hide_empty' => true, 'number' => 20]);
        if (\is_array($categories)) {
            foreach ($categories as $cat) {
                $urls[] = \get_category_link($cat->term_id);
            }
        }

        // Tag archives (top tags)
        $tags = \get_tags(['hide_empty' => true, 'number' => 20, 'orderby' => 'count', 'order' => 'DESC']);
        if (\is_array($tags)) {
            foreach ($tags as $tag) {
                $urls[] = \get_tag_link($tag->term_id);
            }
        }

        $urls = array_unique(array_filter($urls));

        // Respect batch size
        return array_slice($urls, 0, $this->batchSize);
    }

    /**
     * Preload URLs by sending non-blocking loopback HTTP requests.
     *
     * Each request hits the site's own front end, which triggers
     * ServePageCache to generate and store the cached HTML.
     *
     * @param string[] $urls
     */
    public function preloadUrls(array $urls): void
    {
        foreach ($urls as $url) {
            // Skip if already cached
            if ($this->pageCache->get($url) !== null) {
                continue;
            }

            \wp_remote_get($url, [
                'timeout'   => 30,
                'blocking'  => false,
                'sslverify' => true,
                'headers'   => [
                    'X-Cache-Preload' => '1',
                    'Cache-Control'   => 'no-cache',
                ],
                'cookies'   => [], // No cookies = not logged-in = cacheable
            ]);
        }
    }
}
