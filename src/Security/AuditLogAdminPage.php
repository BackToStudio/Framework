<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Admin\Contracts\AdminPageInterface;
use BackTo\Framework\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Security\Contracts\AuditLogSeverity;

/**
 * Admin page for viewing, filtering, and exporting the security audit log.
 */
class AuditLogAdminPage implements AdminPageInterface
{
    use HtmlEscapeTrait;

    private const DEFAULT_PER_PAGE = 50;
    private const MENU_POSITION = 81;

    private readonly AuditLogRepositoryInterface $repository;
    private readonly AuditLogCsvExporter $csvExporter;

    private int $perPage = self::DEFAULT_PER_PAGE;

    public function __construct(AuditLogRepositoryInterface $repository, ?AuditLogCsvExporter $csvExporter = null)
    {
        $this->repository = $repository;
        $this->csvExporter = $csvExporter ?? new AuditLogCsvExporter($repository);
    }

    public function getPageTitle(): string
    {
        return 'Security Audit Log';
    }

    public function getMenuTitle(): string
    {
        return 'Audit Log';
    }

    public function getCapability(): string
    {
        return 'manage_options';
    }

    public function getMenuSlug(): string
    {
        return 'backto-audit-log';
    }

    public function getIconUrl(): string
    {
        return 'dashicons-shield';
    }

    public function getPosition(): ?int
    {
        return self::MENU_POSITION;
    }

    public function render(): void
    {
        $currentPage = $this->getCurrentPage();
        $filters = $this->getFiltersFromRequest();
        $offset = ($currentPage - 1) * $this->perPage;

        if ($this->isExportRequest()) {
            if (! $this->verifyNonce('backto_audit_export', '_export_nonce')) {
                $this->renderNotice('Security check failed. Please try again.');
            } elseif (! $this->csvExporter->canExport()) {
                $this->renderNotice('Please wait before exporting again.');
            } else {
                $this->csvExporter->markExported();
                $this->csvExporter->export($filters);

                return;
            }
        }

        if ($this->isPurgeRequest()) {
            if (! $this->verifyNonce('backto_audit_purge', '_purge_nonce')) {
                $this->renderNotice('Security check failed. Please try again.');
            } else {
                $days = $this->getPurgeDays();
                $purged = $this->repository->purge($days);
                $this->renderNotice('Purged ' . $purged . ' events older than ' . $days . ' days.');
            }
        }

        $events = $this->repository->getEvents($filters, $this->perPage, $offset);

        $this->renderPage($events, $filters, $currentPage);
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @param array<string, mixed> $filters
     */
    public function renderPage(array $events, array $filters, int $currentPage): void
    {
        echo '<div class="wrap">';
        echo '<h1>Security Audit Log</h1>';

        $this->renderFilterForm($filters);
        $this->renderTable($events);
        $this->renderPagination($currentPage, count($events));
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

        $eventTypes = [
            'login_success', 'login_failed', 'user_role_changed',
            'critical_option_changed', 'plugin_activated', 'plugin_deactivated',
            'theme_switched', 'user_created', 'user_deleted',
            'self_promotion_blocked', 'privileged_role_granted',
        ];

        foreach ($eventTypes as $type) {
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

    public function renderPagination(int $currentPage, int $eventCount): void
    {
        echo '<div style="margin-top:10px;">';

        if ($currentPage > 1) {
            echo '<a href="' . $this->escapeAttr($this->buildPageUrl($currentPage - 1)) . '" class="button">&laquo; Previous</a> ';
        }

        echo '<span>Page ' . $currentPage . '</span> ';

        if ($eventCount >= $this->perPage) {
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

/**
     * @return array<string, mixed>
     */
    protected function getFiltersFromRequest(): array
    {
        $filters = [];

        $allowedEvents = [
            'login_success', 'login_failed', 'user_role_changed',
            'critical_option_changed', 'plugin_activated', 'plugin_deactivated',
            'theme_switched', 'user_created', 'user_deleted',
            'self_promotion_blocked', 'privileged_role_granted',
        ];

        $event = (string) ($_GET['event'] ?? '');
        if ($event !== '' && in_array($event, $allowedEvents, true)) {
            $filters['event'] = $event;
        }

        $severity = (string) ($_GET['severity'] ?? '');
        if ($severity !== '' && AuditLogSeverity::tryFrom($severity) !== null) {
            $filters['severity'] = $severity;
        }

        return $filters;
    }

    protected function getCurrentPage(): int
    {
        return max(1, (int) ($_GET['paged'] ?? 1));
    }

    protected function isExportRequest(): bool
    {
        return ($_GET['action'] ?? '') === 'export';
    }

    protected function isPurgeRequest(): bool
    {
        return ($_POST['action'] ?? '') === 'purge';
    }

    protected function getPurgeDays(): int
    {
        return max(1, (int) ($_POST['days'] ?? 90));
    }

    protected function buildPageUrl(int $page): string
    {
        return '?page=backto-audit-log&paged=' . $page;
    }

    protected function renderNotice(string $message): void
    {
        echo '<div class="notice notice-success is-dismissible"><p>' . $this->escapeHtml($message) . '</p></div>';
    }

protected function verifyNonce(string $action, string $queryArg): bool
    {
        $nonce = $_REQUEST[$queryArg] ?? '';

        if (! is_string($nonce) || $nonce === '') {
            return false;
        }

        return $this->wpVerifyNonce($nonce, $action);
    }

    protected function wpVerifyNonce(string $nonce, string $action): bool
    {
        if (function_exists('wp_verify_nonce')) {
            return wp_verify_nonce($nonce, $action) !== false;
        }

        return false;
    }

    protected function renderNonceField(string $action, string $name): void
    {
        if (function_exists('wp_create_nonce')) {
            echo '<input type="hidden" name="' . $this->escapeAttr($name) . '" value="' . $this->escapeAttr(wp_create_nonce($action)) . '" />';
        }
    }

}
