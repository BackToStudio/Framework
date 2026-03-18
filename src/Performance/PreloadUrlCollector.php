<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

/**
 * Collects URLs that need to be preloaded for cache warming.
 *
 * Gathers post-related URLs (permalink, archives, taxonomies, author, date)
 * and site-wide URLs (recent posts, pages, category/tag archives).
 */
class PreloadUrlCollector
{
    public function __construct(
        private readonly int $batchSize = 50,
    ) {
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
}
