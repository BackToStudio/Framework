<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Port interface for rate limiter state persistence.
 */
interface RateLimiterRepositoryInterface
{
    /**
     * Increment the hit count for a given key and return the current count.
     */
    public function increment(string $key, int $windowSeconds): int;

    /**
     * Get current hit count for a key.
     */
    public function getHits(string $key): int;

    /**
     * Get the remaining TTL in seconds for a key.
     */
    public function getTtl(string $key): int;
}
