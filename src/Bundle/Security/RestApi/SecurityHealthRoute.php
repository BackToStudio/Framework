<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\RestApi;

use BackTo\Framework\Bundle\Admin\Contracts\CapabilityManagerInterface;
use BackTo\Framework\RestApi\Contracts\RestRequest;
use BackTo\Framework\RestApi\Contracts\RestResponse;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\Bundle\Security\HealthCheck\SecurityHealthCheck;

/**
 * REST endpoint to retrieve security health check status.
 *
 * GET /backto/v1/security/health
 */
final class SecurityHealthRoute implements RestRouteInterface
{
    private readonly SecurityHealthCheck $healthCheck;
    private readonly CapabilityManagerInterface $capabilityManager;

    public function __construct(
        SecurityHealthCheck $healthCheck,
        CapabilityManagerInterface $capabilityManager,
    ) {
        $this->healthCheck = $healthCheck;
        $this->capabilityManager = $capabilityManager;
    }

    public function getNamespace(): string
    {
        return 'backto/v1';
    }

    public function getRoute(): string
    {
        return '/security/health';
    }


    public function getMethods(): array
    {
        return ['GET'];
    }

    public function handle(RestRequest $request): RestResponse
    {
        $result = $this->healthCheck->check();

        return new RestResponse([
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
            'metadata' => $result->getMetadata(),
        ], 200);
    }

    public function getPermissionCallback(): ?callable
    {
        return fn (): bool => $this->capabilityManager->currentUserCan('manage_options');
    }
}
