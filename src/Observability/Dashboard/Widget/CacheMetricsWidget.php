<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Dashboard\Widget;

/**
 * Renders the cache metrics table widget.
 */
final class CacheMetricsWidget
{
    /**
     * @param array<string, mixed> $metrics
     */
    public function render(array $metrics): void
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
}
