<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Cache\CacheMetricsDecorator;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Infrastructure\WordPressMetricStore;

/**
 * Flushes all metric buffers on WordPress shutdown.
 *
 * Ensures both the CacheMetricsDecorator counters and the MetricStore
 * write buffer are persisted in a single lifecycle event.
 * CacheMetricsDecorator is flushed first (it writes to MetricStore),
 * then MetricStore persists everything to the database.
 */
final class FlushMetricsOnShutdown implements Hooks
{
    private readonly WordPressMetricStore $metricStore;
    private readonly HookDispatcherInterface $hookDispatcher;
    private ?CacheMetricsDecorator $cacheMetrics = null;

    public function __construct(
        WordPressMetricStore $metricStore,
        HookDispatcherInterface $hookDispatcher,
    ) {
        $this->metricStore = $metricStore;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function setCacheMetricsDecorator(CacheMetricsDecorator $cacheMetrics): void
    {
        $this->cacheMetrics = $cacheMetrics;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('shutdown', [$this, 'flush'], 999);
    }

    public function flush(): void
    {
        if (!$this->metricStore->isDirty() && $this->cacheMetrics === null) {
            return;
        }

        if ($this->cacheMetrics !== null) {
            $this->cacheMetrics->flushMetrics();
        }

        $this->metricStore->flush();
    }
}
