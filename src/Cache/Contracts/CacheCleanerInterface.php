<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Contracts;

/**
 * Port interface for bulk-clearing cache entries by prefix.
 */
interface CacheCleanerInterface
{
    /**
     * Delete all cache entries matching the given prefix.
     */
    public function clearByPrefix(string $prefix): bool;
}
