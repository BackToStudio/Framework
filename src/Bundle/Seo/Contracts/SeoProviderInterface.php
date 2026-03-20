<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Contracts;

/**
 * Unified interface to interact with SEO plugins (Yoast, SEOPress, etc.).
 */
interface SeoProviderInterface extends SocialLinksProviderInterface, MetaProviderInterface
{
    /**
     * Return the SEO plugin identifier (e.g. 'yoast', 'seopress').
     */
    public function getName(): string;

    /**
     * Whether the underlying SEO plugin is active.
     */
    public function isActive(): bool;
}
