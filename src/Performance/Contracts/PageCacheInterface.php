<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Contracts;

/**
 * Port for full-page HTML cache.
 *
 * Stores pre-rendered HTML pages on disk to bypass PHP/WordPress execution
 * for non-logged-in visitors.
 */
interface PageCacheInterface
{
    /**
     * Get a cached page by its URL.
     *
     * @return string|null The cached HTML, or null on cache miss.
     */
    public function get(string $url): ?string;

    /**
     * Store a rendered page in the cache.
     */
    public function put(string $url, string $html, int $ttl): void;

    /**
     * Remove a cached page by URL.
     */
    public function invalidate(string $url): void;

    /**
     * Flush all cached pages.
     */
    public function flush(): void;
}
