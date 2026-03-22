<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;

/**
 * Decorator that tracks cache hit/miss metrics.
 *
 * Wraps any CacheInterface implementation and records hit/miss
 * counters in the metric store for dashboard visibility.
 */
final class CacheMetricsDecorator implements CacheInterface
{
    private readonly CacheInterface $inner;
    private readonly MetricStoreInterface $metricStore;

    private int $hits = 0;
    private int $misses = 0;

    public function __construct(CacheInterface $inner, MetricStoreInterface $metricStore)
    {
        $this->inner = $inner;
        $this->metricStore = $metricStore;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->inner->get($key, $default);

        if ($value === $default) {
            $this->misses++;
        } else {
            $this->hits++;
        }

        return $value;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->inner->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->inner->delete($key);
    }

    public function clear(): bool
    {
        return $this->inner->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->inner->getMultiple($keys, $default);
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        return $this->inner->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->inner->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->inner->has($key);
    }

    /**
     * Flush in-request counters to the metric store as a snapshot.
     *
     * Call this at shutdown to persist the request-level hit rate.
     */
    /**
     * Flush in-request counters to the metric store as a snapshot.
     *
     * Persists hit/miss counts and computed hit rate in a single batch.
     * Call this once at shutdown — not per-request — to avoid DB churn.
     */
    public function flushMetrics(): void
    {
        $total = $this->hits + $this->misses;

        if ($total === 0) {
            return;
        }

        $hitRate = \round(($this->hits / $total) * 100, 1);
        $this->metricStore->increment('cache.hits', (float) $this->hits);
        $this->metricStore->increment('cache.misses', (float) $this->misses);
        $this->metricStore->record('cache.hit_rate', $hitRate, 'gauge');
        $this->metricStore->record('cache.requests', (float) $total, 'counter');
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function getMisses(): int
    {
        return $this->misses;
    }
}
