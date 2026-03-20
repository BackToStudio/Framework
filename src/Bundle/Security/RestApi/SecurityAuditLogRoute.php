<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\RestApi;

use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\Bundle\Security\Contracts\AuditLogRepositoryInterface;
use WP_REST_Response;

/**
 * REST endpoint to retrieve security audit log events.
 *
 * GET /backto/v1/security/audit-log?event=login_failed&severity=warning&per_page=50&page=1
 */
final class SecurityAuditLogRoute implements RestRouteInterface
{
    private readonly AuditLogRepositoryInterface $repository;

    public function __construct(AuditLogRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getNamespace(): string
    {
        return 'backto/v1';
    }

    public function getRoute(): string
    {
        return '/security/audit-log';
    }

    
    public function getMethods(): array
    {
        return ['GET'];
    }

    public function handle(mixed $request): mixed
    {
        $filters = [];

        $event = $request->get_param('event');
        if (is_string($event) && $event !== '') {
            $filters['event'] = $event;
        }

        $severity = $request->get_param('severity');
        if (is_string($severity) && $severity !== '') {
            $filters['severity'] = $severity;
        }

        $perPage = max(1, min(100, (int) ($request->get_param('per_page') ?? 50)));
        $page = max(1, (int) ($request->get_param('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $events = $this->repository->getEvents($filters, $perPage, $offset);

        return new WP_REST_Response([
            'events' => $events,
            'page' => $page,
            'per_page' => $perPage,
        ], 200);
    }

    public function getPermissionCallback(): ?callable
    {
        return static fn (): bool => current_user_can('manage_options');
    }
}
