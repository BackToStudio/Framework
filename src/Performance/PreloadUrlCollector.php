<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;

/**
 * Collects URLs that need to be preloaded for cache warming.
 *
 * Gathers post-related URLs (permalink, archives, taxonomies, author, date)
 * and site-wide URLs (recent posts, pages, category/tag archives).
 */
class PreloadUrlCollector
{
    private readonly ContentQueryInterface $contentQuery;
    private readonly SiteContextInterface $siteContext;
    private readonly OptionsRepositoryInterface $options;
    private readonly int $batchSize;

    public function __construct(
        ContentQueryInterface $contentQuery,
        SiteContextInterface $siteContext,
        OptionsRepositoryInterface $options,
        int $batchSize = 50,
    ) {
        $this->contentQuery = $contentQuery;
        $this->siteContext = $siteContext;
        $this->options = $options;
        $this->batchSize = $batchSize;
    }

    /**
     * Collect all URLs related to a specific post.
     *
     * @return string[]
     */
    public function getPostRelatedUrls(int $postId): array
    {
        $postType = $this->contentQuery->getPostType($postId) ?: 'post';
        $post = $this->contentQuery->getPost($postId);

        return array_unique(array_filter(array_merge(
            $this->collectPermalinkUrl($postId),
            [$this->siteContext->getHomeUrl() . '/'],
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
        $urls = [$this->siteContext->getHomeUrl() . '/'];
        $urls = array_merge($urls, $this->collectBlogPageUrl());

        // Recent published posts
        $recentPosts = $this->contentQuery->getPosts([
            'numberposts'      => $this->batchSize,
            'post_type'        => 'post',
            'post_status'      => 'publish',
            'orderby'          => 'date',
            'order'            => 'DESC',
            'suppress_filters' => true,
        ]);

        foreach ($recentPosts as $post) {
            $urls[] = $this->contentQuery->getPermalink($post->ID);
        }

        // Published pages
        $pages = $this->contentQuery->getPosts([
            'numberposts'      => $this->batchSize,
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'orderby'          => 'menu_order',
            'order'            => 'ASC',
            'suppress_filters' => true,
        ]);

        foreach ($pages as $page) {
            $urls[] = $this->contentQuery->getPermalink($page->ID);
        }

        // Category archives
        $categories = $this->contentQuery->getCategories(['hide_empty' => true, 'number' => 20]);
        foreach ($categories as $cat) {
            $urls[] = $this->contentQuery->getCategoryLink($cat->term_id);
        }

        // Tag archives (top tags)
        $tags = $this->contentQuery->getTags(['hide_empty' => true, 'number' => 20, 'orderby' => 'count', 'order' => 'DESC']);
        foreach ($tags as $tag) {
            $urls[] = $this->contentQuery->getTagLink($tag->term_id);
        }

        $urls = array_unique(array_filter($urls));

        // Respect batch size
        return array_slice($urls, 0, $this->batchSize);
    }

    /** @return string[] */
    private function collectPermalinkUrl(int $postId): array
    {
        $permalink = $this->contentQuery->getPermalink($postId);

        return $permalink !== false ? [$permalink] : [];
    }

    /** @return string[] */
    private function collectBlogPageUrl(): array
    {
        $blogPageId = (int) $this->options->get('page_for_posts', 0);

        if ($blogPageId <= 0) {
            return [];
        }

        $blogUrl = $this->contentQuery->getPermalink($blogPageId);

        return $blogUrl !== false ? [$blogUrl] : [];
    }

    /** @return string[] */
    private function collectPostTypeArchiveUrl(string $postType): array
    {
        if ($postType === 'page') {
            return [];
        }

        $archiveUrl = $this->contentQuery->getPostTypeArchiveLink($postType);

        return $archiveUrl !== false ? [$archiveUrl] : [];
    }

    /** @return string[] */
    private function collectTaxonomyUrls(int $postId, string $postType): array
    {
        $taxonomies = $this->contentQuery->getObjectTaxonomies($postType);
        $terms = $this->contentQuery->getPostTerms($postId, $taxonomies);

        if ($terms === false) {
            return [];
        }

        $urls = [];

        foreach ($terms as $term) {
            $termLink = $this->contentQuery->getTermLink($term);

            if ($termLink !== false) {
                $urls[] = $termLink;
            }
        }

        return $urls;
    }

    /** @return string[] */
    private function collectAuthorUrl(?object $post): array
    {
        if ($post === null) {
            return [];
        }

        $authorUrl = $this->contentQuery->getAuthorPostsUrl($post->post_author);

        return $authorUrl !== '' ? [$authorUrl] : [];
    }

    /** @return string[] */
    private function collectDateArchiveUrls(?object $post): array
    {
        if ($post === null) {
            return [];
        }

        $date = $post->post_date;
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));

        return [
            $this->contentQuery->getYearLink((int) $year),
            $this->contentQuery->getMonthLink((int) $year, (int) $month),
        ];
    }
}
