<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Dashboard;

use BackTo\Framework\Bundle\Security\Hardening\HtmlEscapeTrait;
use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Contracts\HealthCheckStatus;

/**
 * Renders the Operations Dashboard admin page HTML.
 *
 * Displays health checks, queue status, cache metrics, and recent alerts
 * in a unified view for site operators.
 */
final class OperationsDashboardRenderer
{
    use HtmlEscapeTrait;

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

        $this->renderHealthCheckWidget($healthChecks);
        $this->renderQueueWidget($queueMetrics);
        $this->renderCacheWidget($cacheMetrics);
        $this->renderMetricsSummaryWidget($metricsSummary);

        echo '</div>';
        echo '</div>';
    }

    /**
     * @param array<string, HealthCheckResult> $checks
     */
    public function renderHealthCheckWidget(array $checks): void
    {
        echo '<div class="postbox" style="padding:15px;">';
        echo '<h2 style="margin-top:0;">Health Checks</h2>';

        if ($checks === []) {
            echo '<p>No health checks registered.</p>';
            echo '</div>';

            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>Component</th><th>Status</th><th>Message</th>';
        echo '</tr></thead><tbody>';

        foreach ($checks as $name => $result) {
            $statusStyle = match ($result->getHealthCheckStatus()) {
                HealthCheckStatus::Healthy => 'color:#00a32a;font-weight:bold',
                HealthCheckStatus::Degraded => 'color:#dba617;font-weight:bold',
                HealthCheckStatus::Unhealthy => 'color:#dc3232;font-weight:bold',
            };

            $statusIcon = match ($result->getHealthCheckStatus()) {
                HealthCheckStatus::Healthy => '&#10003;',
                HealthCheckStatus::Degraded => '&#9888;',
                HealthCheckStatus::Unhealthy => '&#10007;',
            };

            echo '<tr>';
            echo '<td>' . $this->escapeHtml($name) . '</td>';
            echo '<td style="' . $statusStyle . '">' . $statusIcon . ' ' . $this->escapeHtml(\strtoupper($result->getStatus())) . '</td>';
            echo '<td>' . $this->escapeHtml($result->getMessage()) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $metrics
     */
    public function renderQueueWidget(array $metrics): void
    {
        echo '<div class="postbox" style="padding:15px;">';
        echo '<h2 style="margin-top:0;">Queue Status</h2>';

        $pending = (int) ($metrics['pending'] ?? 0);
        $running = (int) ($metrics['running'] ?? 0);
        $failed = (int) ($metrics['failed'] ?? 0);
        $groups = (array) ($metrics['active_groups'] ?? []);

        $pendingStyle = $pending > 100 ? 'color:#dc3232;font-weight:bold' : '';
        $failedStyle = $failed > 0 ? 'color:#dba617;font-weight:bold' : '';

        echo '<table class="widefat striped"><tbody>';
        echo '<tr><td>Pending jobs</td><td style="' . $pendingStyle . '">' . $pending . '</td></tr>';
        echo '<tr><td>Running jobs</td><td>' . $running . '</td></tr>';
        echo '<tr><td>Failed jobs</td><td style="' . $failedStyle . '">' . $failed . '</td></tr>';
        echo '<tr><td>Active groups</td><td>' . $this->escapeHtml(\implode(', ', $groups) ?: 'None') . '</td></tr>';
        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $metrics
     */
    public function renderCacheWidget(array $metrics): void
    {
        echo '<div class="postbox" style="padding:15px;">';
        echo '<h2 style="margin-top:0;">Cache Metrics</h2>';

        $hitRate = (float) ($metrics['hit_rate'] ?? 0.0);
        $hits = (int) ($metrics['hits'] ?? 0);
        $misses = (int) ($metrics['misses'] ?? 0);

        $hitRateStyle = $hitRate < 50.0 ? 'color:#dc3232;font-weight:bold' : 'color:#00a32a;font-weight:bold';

        echo '<table class="widefat striped"><tbody>';
        echo '<tr><td>Hit rate</td><td style="' . $hitRateStyle . '">' . \number_format($hitRate, 1) . '%</td></tr>';
        echo '<tr><td>Total hits</td><td>' . $hits . '</td></tr>';
        echo '<tr><td>Total misses</td><td>' . $misses . '</td></tr>';
        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * @param array<string, array{current: float, min: float, max: float, avg: float, count: int}> $summary
     */
    public function renderMetricsSummaryWidget(array $summary): void
    {
        echo '<div class="postbox" style="padding:15px;">';
        echo '<h2 style="margin-top:0;">Metrics Summary (24h)</h2>';

        if ($summary === []) {
            echo '<p>No metrics recorded yet.</p>';
            echo '</div>';

            return;
        }

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
