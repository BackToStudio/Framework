<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Dashboard\Widget;

use BackTo\Framework\Bundle\Security\Hardening\HtmlEscapeTrait;
use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Contracts\HealthCheckStatus;

/**
 * Renders the health check status table widget.
 */
final class HealthCheckWidget
{
    use HtmlEscapeTrait;

    /**
     * @param array<string, HealthCheckResult> $checks
     */
    public function render(array $checks): void
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
}
