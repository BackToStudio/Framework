<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\RestApi;

use BackTo\Framework\Bundle\Admin\Contracts\CapabilityManagerInterface;
use BackTo\Framework\RestApi\Contracts\RestRequest;
use BackTo\Framework\RestApi\Contracts\RestResponse;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\Bundle\Security\Contracts\AuditLogRepositoryInterface;

/**
 * REST endpoint to retrieve security audit log events.
 *
 * GET /backto/v1/security/audit-log?event=login_failed&severity=warning&per_page=50&page=1
 */
final class SecurityAuditLogRoute implements RestRouteInterface
{
    private readonly AuditLogRepositoryInterface $repository;
    private readonly CapabilityManagerInterface $capabilityManager;

    public function __construct(
        AuditLogRepositoryInterface $repository,
        CapabilityManagerInterface $capabilityManager,
    ) {
        $this->repository = $repository;
        $this->capabilityManager = $capabilityManager;
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

    public function handle(RestRequest $request): RestResponse
    {
        $filters = [];

        $event = $request->getParam('event');
        if (is_string($event) && $event !== '') {
            $filters['event'] = $event;
        }

        $severity = $request->getParam('severity');
        if (is_string($severity) && $severity !== '') {
            $filters['severity'] = $severity;
        }

        $perPage = max(1, min(100, (int) ($request->getParam('per_page') ?? 50)));
        $page = max(1, (int) ($request->getParam('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $events = $this->repository->getEvents($filters, $perPage, $offset);

        return new RestResponse([
            'events' => $events,
            'page' => $page,
            'per_page' => $perPage,
        ], 200);
    }

    public function getPermissionCallback(): ?callable
    {
        return fn (): bool => $this->capabilityManager->currentUserCan('manage_options');
    }
}
