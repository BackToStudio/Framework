<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Admin\Contracts\AdminPageInterface;
use BackTo\Framework\Contracts\NonceManagerInterface;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Security\AuditLog\AuditLogRenderer;
use BackTo\Framework\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Security\Contracts\AuditLogSeverity;

/**
 * Admin page controller for the security audit log.
 *
 * Handles request routing (filtering, export, purge) and delegates
 * all HTML rendering to {@see AuditLogRenderer}.
 */
class AuditLogAdminPage implements AdminPageInterface
{
    private const DEFAULT_PER_PAGE = 50;
    private const MENU_POSITION = 81;

    private readonly AuditLogRepositoryInterface $repository;
    private readonly RequestContextInterface $requestContext;
    private readonly NonceManagerInterface $nonceManager;
    private readonly AuditLogCsvExporter $csvExporter;
    private readonly AuditLogRenderer $renderer;

    private int $perPage = self::DEFAULT_PER_PAGE;

    public function __construct(
        AuditLogRepositoryInterface $repository,
        RequestContextInterface $requestContext,
        NonceManagerInterface $nonceManager,
        ?AuditLogCsvExporter $csvExporter = null,
        ?AuditLogRenderer $renderer = null,
    ) {
        $this->repository = $repository;
        $this->requestContext = $requestContext;
        $this->nonceManager = $nonceManager;
        $this->csvExporter = $csvExporter ?? new AuditLogCsvExporter($repository);
        $this->renderer = $renderer ?? new AuditLogRenderer($nonceManager);
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
                $this->renderer->renderNotice('Security check failed. Please try again.');
            } elseif (! $this->csvExporter->canExport()) {
                $this->renderer->renderNotice('Please wait before exporting again.');
            } else {
                $this->csvExporter->markExported();
                $this->csvExporter->export($filters);

                return;
            }
        }

        if ($this->isPurgeRequest()) {
            if (! $this->verifyNonce('backto_audit_purge', '_purge_nonce')) {
                $this->renderer->renderNotice('Security check failed. Please try again.');
            } else {
                $days = $this->getPurgeDays();
                $purged = $this->repository->purge($days);
                $this->renderer->renderNotice('Purged ' . $purged . ' events older than ' . $days . ' days.');
            }
        }

        $events = $this->repository->getEvents($filters, $this->perPage, $offset);

        $this->renderer->renderPage($events, $filters, $currentPage, $this->perPage);
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

        $event = (string) ($this->requestContext->query('event') ?? '');
        if ($event !== '' && in_array($event, $allowedEvents, true)) {
            $filters['event'] = $event;
        }

        $severity = (string) ($this->requestContext->query('severity') ?? '');
        if ($severity !== '' && AuditLogSeverity::tryFrom($severity) !== null) {
            $filters['severity'] = $severity;
        }

        return $filters;
    }

    protected function getCurrentPage(): int
    {
        return max(1, (int) ($this->requestContext->query('paged') ?? 1));
    }

    protected function isExportRequest(): bool
    {
        return ($this->requestContext->query('action') ?? '') === 'export';
    }

    protected function isPurgeRequest(): bool
    {
        return ($this->requestContext->post('action') ?? '') === 'purge';
    }

    protected function getPurgeDays(): int
    {
        return max(1, (int) ($this->requestContext->post('days') ?? 90));
    }

    protected function verifyNonce(string $action, string $queryArg): bool
    {
        $nonce = $this->requestContext->input($queryArg) ?? '';

        if (! is_string($nonce) || $nonce === '') {
            return false;
        }

        return $this->nonceManager->verifyNonce($nonce, $action);
    }
}
