<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Dashboard\Widget;

use BackTo\Framework\Bundle\Security\Hardening\HtmlEscapeTrait;

/**
 * Renders the queue status table widget.
 */
final class QueueStatusWidget
{
    use HtmlEscapeTrait;

    /**
     * @param array<string, mixed> $metrics
     */
    public function render(array $metrics): void
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
}
