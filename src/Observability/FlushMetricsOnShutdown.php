<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Infrastructure\WordPressMetricStore;

/**
 * Flushes the metric store write buffer on WordPress shutdown.
 *
 * Ensures all in-memory metrics accumulated during the request
 * are persisted in a single database write at the end of the lifecycle.
 */
final class FlushMetricsOnShutdown implements Hooks
{
    private readonly WordPressMetricStore $metricStore;
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(
        WordPressMetricStore $metricStore,
        HookDispatcherInterface $hookDispatcher,
    ) {
        $this->metricStore = $metricStore;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('shutdown', [$this, 'flush'], 999);
    }

    public function flush(): void
    {
        $this->metricStore->flush();
    }
}
