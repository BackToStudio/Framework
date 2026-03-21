<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Dashboard;

use BackTo\Framework\Contracts\AdminPageInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\HealthCheckRegistry;
use BackTo\Framework\Queue\Contracts\QueueQueryInterface;
use BackTo\Framework\Queue\Entity\JobStatus;

/**
 * Admin page controller for the Operations Dashboard.
 *
 * Aggregates data from health checks, queue, cache metrics, and metric store
 * into a unified operations view. Delegates all HTML rendering to
 * {@see OperationsDashboardRenderer}.
 */
class OperationsDashboardPage implements AdminPageInterface
{
    private const MENU_POSITION = 3;
    private const SUMMARY_WINDOW = 86400; // 24 hours

    private readonly HealthCheckRegistry $healthCheckRegistry;
    private readonly QueueQueryInterface $queueQuery;
    private readonly MetricStoreInterface $metricStore;
    private readonly OperationsDashboardRenderer $renderer;

    public function __construct(
        HealthCheckRegistry $healthCheckRegistry,
        QueueQueryInterface $queueQuery,
        MetricStoreInterface $metricStore,
        ?OperationsDashboardRenderer $renderer = null,
    ) {
        $this->healthCheckRegistry = $healthCheckRegistry;
        $this->queueQuery = $queueQuery;
        $this->metricStore = $metricStore;
        $this->renderer = $renderer ?? new OperationsDashboardRenderer();
    }

    public function getPageTitle(): string
    {
        return 'Operations Dashboard';
    }

    public function getMenuTitle(): string
    {
        return 'Operations';
    }

    public function getCapability(): string
    {
        return 'manage_options';
    }

    public function getMenuSlug(): string
    {
        return 'backto-operations';
    }

    public function getIconUrl(): string
    {
        return 'dashicons-heart';
    }

    public function getPosition(): ?int
    {
        return self::MENU_POSITION;
    }

    public function render(): void
    {
        $healthChecks = $this->healthCheckRegistry->runAll();
        $queueMetrics = $this->collectQueueMetrics();
        $cacheMetrics = $this->collectCacheMetrics();
        $metricsSummary = $this->metricStore->summary(\time() - self::SUMMARY_WINDOW);

        $this->renderer->renderPage($healthChecks, $queueMetrics, $cacheMetrics, $metricsSummary);
    }

    /**
     * @return array<string, mixed>
     */
    private function collectQueueMetrics(): array
    {
        return [
            'pending' => $this->queueQuery->countByStatus(JobStatus::Pending),
            'running' => $this->queueQuery->countByStatus(JobStatus::Running),
            'failed' => $this->queueQuery->countByStatus(JobStatus::Failed),
            'active_groups' => $this->queueQuery->getActiveGroups(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectCacheMetrics(): array
    {
        $hitRate = $this->metricStore->latest('cache.hit_rate');
        $hits = $this->metricStore->latest('cache.hits');
        $misses = $this->metricStore->latest('cache.misses');

        return [
            'hit_rate' => $hitRate !== null ? $hitRate['value'] : 0.0,
            'hits' => $hits !== null ? (int) $hits['value'] : 0,
            'misses' => $misses !== null ? (int) $misses['value'] : 0,
        ];
    }
}
