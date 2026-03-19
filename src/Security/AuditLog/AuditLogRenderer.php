<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\AuditLog;

use BackTo\Framework\Security\Contracts\AuditLogSeverity;
use BackTo\Framework\Security\HtmlEscapeTrait;

/**
 * Renders the audit log admin page HTML.
 *
 * Extracted from AuditLogAdminPage to respect the Single Responsibility Principle:
 * this class handles HTML rendering only, while AuditLogAdminPage handles
 * request routing and page lifecycle.
 */
final class AuditLogRenderer
{
    use HtmlEscapeTrait;

    /**
     * @param array<int, array<string, mixed>> $events
     * @param array<string, mixed> $filters
     */
    public function renderPage(array $events, array $filters, int $currentPage, int $perPage): void
    {
        echo '<div class="wrap">';
        echo '<h1>Security Audit Log</h1>';

        $this->renderFilterForm($filters);
        $this->renderTable($events);
        $this->renderPagination($currentPage, count($events), $perPage);
        $this->renderActions();

        echo '</div>';
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function renderFilterForm(array $filters): void
    {
        $eventValue = $this->escapeAttr((string) ($filters['event'] ?? ''));
        $severityValue = $this->escapeAttr((string) ($filters['severity'] ?? ''));

        echo '<form method="get" style="margin-bottom:15px;">';
        echo '<input type="hidden" name="page" value="backto-audit-log" />';

        echo '<label>Event: <select name="event">';
        echo '<option value="">All</option>';

        foreach (self::EVENT_TYPES as $type) {
            $selected = $eventValue === $type ? ' selected' : '';
            echo '<option value="' . $this->escapeAttr($type) . '"' . $selected . '>' . $this->escapeHtml($type) . '</option>';
        }

        echo '</select></label> ';

        echo '<label>Severity: <select name="severity">';
        echo '<option value="">All</option>';

        foreach (AuditLogSeverity::cases() as $sevEnum) {
            $sev = $sevEnum->value;
            $selected = $severityValue === $sev ? ' selected' : '';
            echo '<option value="' . $sev . '"' . $selected . '>' . ucfirst($sev) . '</option>';
        }

        echo '</select></label> ';
        echo '<button type="submit" class="button">Filter</button>';
        echo '</form>';
    }

    /**
     * @param array<int, array<string, mixed>> $events
     */
    public function renderTable(array $events): void
    {
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>Time</th><th>Event</th><th>Severity</th><th>Details</th>';
        echo '</tr></thead><tbody>';

        if ($events === []) {
            echo '<tr><td colspan="4">No events found.</td></tr>';
        }

        foreach ($events as $event) {
            $timestamp = (int) ($event['timestamp'] ?? 0);
            $date = $timestamp > 0 ? gmdate('Y-m-d H:i:s', $timestamp) : '-';
            $eventName = $this->escapeHtml((string) ($event['event'] ?? ''));
            $severity = (string) ($event['severity'] ?? 'info');
            $context = $event['context'] ?? [];

            $severityClass = match (AuditLogSeverity::tryFrom($severity)) {
                AuditLogSeverity::Critical => 'color:#dc3232;font-weight:bold',
                AuditLogSeverity::Warning => 'color:#dba617',
                default => 'color:#72aee6',
            };

            $details = [];

            /** @var mixed $value */
            foreach ($context as $key => $value) {
                $displayValue = is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
                $details[] = $this->escapeHtml($key) . ': ' . $this->escapeHtml($displayValue);
            }

            echo '<tr>';
            echo '<td>' . $this->escapeHtml($date) . '</td>';
            echo '<td><code>' . $eventName . '</code></td>';
            echo '<td style="' . $severityClass . '">' . $this->escapeHtml(strtoupper($severity)) . '</td>';
            echo '<td><small>' . implode(' | ', $details) . '</small></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    public function renderPagination(int $currentPage, int $eventCount, int $perPage): void
    {
        echo '<div style="margin-top:10px;">';

        if ($currentPage > 1) {
            echo '<a href="' . $this->escapeAttr($this->buildPageUrl($currentPage - 1)) . '" class="button">&laquo; Previous</a> ';
        }

        echo '<span>Page ' . $currentPage . '</span> ';

        if ($eventCount >= $perPage) {
            echo '<a href="' . $this->escapeAttr($this->buildPageUrl($currentPage + 1)) . '" class="button">Next &raquo;</a>';
        }

        echo '</div>';
    }

    public function renderActions(): void
    {
        echo '<div style="margin-top:20px;">';

        echo '<form method="get" style="display:inline;">';
        echo '<input type="hidden" name="page" value="backto-audit-log" />';
        echo '<input type="hidden" name="action" value="export" />';
        $this->renderNonceField('backto_audit_export', '_export_nonce');
        echo '<button type="submit" class="button">Export CSV</button>';
        echo '</form> ';

        echo '<form method="post" style="display:inline;">';
        echo '<input type="hidden" name="action" value="purge" />';
        $this->renderNonceField('backto_audit_purge', '_purge_nonce');
        echo '<label>Purge events older than <input type="number" name="days" value="90" min="1" max="365" style="width:60px;" /> days</label> ';
        echo '<button type="submit" class="button" onclick="return confirm(\'Are you sure?\');">Purge</button>';
        echo '</form>';

        echo '</div>';
    }

    public function renderNotice(string $message): void
    {
        echo '<div class="notice notice-success is-dismissible"><p>' . $this->escapeHtml($message) . '</p></div>';
    }

    private function buildPageUrl(int $page): string
    {
        return '?page=backto-audit-log&paged=' . $page;
    }

    private function renderNonceField(string $action, string $name): void
    {
        if (function_exists('wp_create_nonce')) {
            echo '<input type="hidden" name="' . $this->escapeAttr($name) . '" value="' . $this->escapeAttr(wp_create_nonce($action)) . '" />';
        }
    }

    /** @var string[] */
    private const EVENT_TYPES = [
        'login_success', 'login_failed', 'user_role_changed',
        'critical_option_changed', 'plugin_activated', 'plugin_deactivated',
        'theme_switched', 'user_created', 'user_deleted',
        'self_promotion_blocked', 'privileged_role_granted',
    ];
}
