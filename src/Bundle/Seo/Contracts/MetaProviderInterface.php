<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Contracts;

/**
 * Provides SEO meta information from an SEO plugin for a given post/page.
 */
interface MetaProviderInterface
{
    /**
     * Get the SEO title for a post.
     */
    public function getTitle(?int $postId = null): ?string;

    /**
     * Get the SEO meta description for a post.
     */
    public function getDescription(?int $postId = null): ?string;

    /**
     * Get the canonical URL for a post.
     */
    public function getCanonicalUrl(?int $postId = null): ?string;

    /**
     * Get the Open Graph title for a post.
     */
    public function getOgTitle(?int $postId = null): ?string;

    /**
     * Get the Open Graph description for a post.
     */
    public function getOgDescription(?int $postId = null): ?string;

    /**
     * Get the Open Graph image URL for a post.
     */
    public function getOgImageUrl(?int $postId = null): ?string;
}
