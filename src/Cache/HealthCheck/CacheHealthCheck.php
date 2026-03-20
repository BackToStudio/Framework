<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\HealthCheck;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;

/**
 * Verifies that the cache subsystem is operational.
 */
final class CacheHealthCheck implements HealthCheckInterface
{
    private readonly CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getName(): string
    {
        return 'cache';
    }

    public function check(): HealthCheckResult
    {
        $testKey = '__backto_health_check_' . \bin2hex(\random_bytes(4));

        try {
            $this->cache->set($testKey, 'ok', 10);
            $value = $this->cache->get($testKey);
            $this->cache->delete($testKey);

            if ($value !== 'ok') {
                return HealthCheckResult::degraded('Cache read/write mismatch');
            }

            return HealthCheckResult::healthy('Cache operational');
        } catch (\Throwable $e) {
            return HealthCheckResult::unhealthy('Cache error: ' . $e->getMessage());
        }
    }
}
