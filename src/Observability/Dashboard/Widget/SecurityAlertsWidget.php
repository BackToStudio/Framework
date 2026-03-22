<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Dashboard\Widget;

use BackTo\Framework\Bundle\Security\Hardening\HtmlEscapeTrait;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;

/**
 * Renders recent security-related alerts and remediation activity.
 */
final class SecurityAlertsWidget
{
    use HtmlEscapeTrait;

    private readonly MetricStoreInterface $metricStore;

    public function __construct(MetricStoreInterface $metricStore)
    {
        $this->metricStore = $metricStore;
    }

    public function render(): void
    {
        echo '<div class="postbox" style="padding:15px;">';
        echo '<h2 style="margin-top:0;">Security &amp; Remediation</h2>';

        $remediationAttempts = $this->metricStore->latest('remediation.attempts');
        $remediationSuccesses = $this->metricStore->latest('remediation.successes');
        $remediationFailures = $this->metricStore->latest('remediation.failures');
        $slowQueries = $this->metricStore->latest('db.slow_queries');
        $digestSent = $this->metricStore->latest('digest.sent');

        echo '<table class="widefat striped"><tbody>';

        $this->renderRow('Remediation attempts', $remediationAttempts);
        $this->renderRow('Remediation successes', $remediationSuccesses);
        $this->renderRow('Remediation failures', $remediationFailures, 'color:#dc3232;font-weight:bold');
        $this->renderRow('Last slow queries count', $slowQueries);
        $this->renderRow('Digests sent', $digestSent);

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * @param array{value: float, type: string, recorded_at: int}|null $metric
     */
    private function renderRow(string $label, ?array $metric, string $style = ''): void
    {
        $value = $metric !== null ? (int) $metric['value'] : 0;
        $styleAttr = ($style !== '' && $value > 0) ? ' style="' . $style . '"' : '';

        echo '<tr><td>' . $this->escapeHtml($label) . '</td>';
        echo '<td' . $styleAttr . '>' . $value . '</td></tr>';
    }
}
