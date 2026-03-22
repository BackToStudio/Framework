<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Remediation;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Observability\Contracts\RemediationInterface;

/**
 * Auto-remediation for cache health check failures.
 *
 * When the cache reports unhealthy status, this handler
 * clears the cache to recover from corrupted state.
 */
final class CacheRemediation implements RemediationInterface
{
    private readonly CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getTargetCheck(): string
    {
        return 'cache';
    }

    public function remediate(array $metadata = []): bool
    {
        return $this->cache->clear();
    }

    public function getDescription(): string
    {
        return 'Clear cache to recover from corrupted state';
    }
}
