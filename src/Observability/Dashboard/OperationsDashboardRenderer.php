<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Dashboard;

use BackTo\Framework\Bundle\Security\Hardening\HtmlEscapeTrait;
use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Observability\Dashboard\Widget\CacheMetricsWidget;
use BackTo\Framework\Observability\Dashboard\Widget\HealthCheckWidget;
use BackTo\Framework\Observability\Dashboard\Widget\QueueStatusWidget;
use BackTo\Framework\Observability\Dashboard\Widget\SecurityAlertsWidget;

/**
 * Renders the Operations Dashboard admin page HTML.
 *
 * Delegates rendering to individual widget classes for modularity.
 * Each widget is responsible for its own HTML output and styling.
 */
final class OperationsDashboardRenderer
{
    use HtmlEscapeTrait;

    private readonly HealthCheckWidget $healthCheckWidget;
    private readonly QueueStatusWidget $queueStatusWidget;
    private readonly CacheMetricsWidget $cacheMetricsWidget;
    private readonly SecurityAlertsWidget $securityAlertsWidget;

    public function __construct(
        HealthCheckWidget $healthCheckWidget,
        QueueStatusWidget $queueStatusWidget,
        CacheMetricsWidget $cacheMetricsWidget,
        SecurityAlertsWidget $securityAlertsWidget,
    ) {
        $this->healthCheckWidget = $healthCheckWidget;
        $this->queueStatusWidget = $queueStatusWidget;
        $this->cacheMetricsWidget = $cacheMetricsWidget;
        $this->securityAlertsWidget = $securityAlertsWidget;
    }

    /**
     * @param array<string, HealthCheckResult> $healthChecks
     * @param array<string, mixed> $queueMetrics
     * @param array<string, mixed> $cacheMetrics
     * @param array<string, array{current: float, min: float, max: float, avg: float, count: int}> $metricsSummary
     */
    public function renderPage(
        array $healthChecks,
        array $queueMetrics,
        array $cacheMetrics,
        array $metricsSummary,
    ): void {
        echo '<div class="wrap">';
        echo '<h1>Operations Dashboard</h1>';
        echo '<p>System health overview &mdash; last updated: ' . $this->escapeHtml(\gmdate('Y-m-d H:i:s')) . ' UTC</p>';

        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">';

        $this->healthCheckWidget->render($healthChecks);
        $this->queueStatusWidget->render($queueMetrics);
        $this->cacheMetricsWidget->render($cacheMetrics);
        $this->securityAlertsWidget->render();

        echo '</div>';

        // Metrics summary below the grid
        if ($metricsSummary !== []) {
            echo '<div style="margin-top:20px;">';
            $this->renderMetricsSummaryWidget($metricsSummary);
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * @param array<string, array{current: float, min: float, max: float, avg: float, count: int}> $summary
     */
    private function renderMetricsSummaryWidget(array $summary): void
    {
        echo '<div class="postbox" style="padding:15px;">';
        echo '<h2 style="margin-top:0;">Metrics Summary (24h)</h2>';

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>Metric</th><th>Current</th><th>Min</th><th>Max</th><th>Avg</th><th>Samples</th>';
        echo '</tr></thead><tbody>';

        foreach ($summary as $name => $data) {
            echo '<tr>';
            echo '<td><code>' . $this->escapeHtml($name) . '</code></td>';
            echo '<td>' . \number_format($data['current'], 2) . '</td>';
            echo '<td>' . \number_format($data['min'], 2) . '</td>';
            echo '<td>' . \number_format($data['max'], 2) . '</td>';
            echo '<td>' . \number_format($data['avg'], 2) . '</td>';
            echo '<td>' . $data['count'] . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }
}
