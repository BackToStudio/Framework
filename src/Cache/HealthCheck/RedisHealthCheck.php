<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\HealthCheck;

use BackTo\Framework\Cache\Contracts\RedisClientInterface;
use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;

/**
 * Verifies Redis connectivity and reports server info.
 *
 * Only registered when Redis is configured as cache strategy.
 */
final class RedisHealthCheck implements HealthCheckInterface
{
    private readonly RedisClientInterface $client;

    public function __construct(RedisClientInterface $client)
    {
        $this->client = $client;
    }

    public function getName(): string
    {
        return 'redis';
    }

    public function check(): HealthCheckResult
    {
        try {
            if (!$this->client->ping()) {
                return HealthCheckResult::unhealthy('Redis not responding to PING');
            }

            $info = $this->client->info();

            $metadata = [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 'unknown',
                'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 'unknown',
            ];

            $usedMemoryPeak = $info['used_memory_peak'] ?? null;
            $maxMemory = $info['maxmemory'] ?? null;

            if ($maxMemory !== null && $maxMemory !== '0' && $usedMemoryPeak !== null) {
                $ratio = (int) $usedMemoryPeak / (int) $maxMemory;
                $metadata['memory_usage_ratio'] = \round($ratio, 2);

                if ($ratio > 0.9) {
                    return HealthCheckResult::degraded(
                        \sprintf('Redis memory usage high (%.0f%%)', $ratio * 100),
                        $metadata,
                    );
                }
            }

            return HealthCheckResult::healthy('Redis operational', $metadata);
        } catch (\Throwable $e) {
            return HealthCheckResult::unhealthy('Redis error: ' . $e->getMessage());
        }
    }
}
