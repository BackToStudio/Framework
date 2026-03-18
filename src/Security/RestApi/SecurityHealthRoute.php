<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\RestApi;

use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\Security\HealthCheck\SecurityHealthCheck;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST endpoint to retrieve security health check status.
 *
 * GET /backto/v1/security/health
 */
final class SecurityHealthRoute implements RestRouteInterface
{
    private readonly SecurityHealthCheck $healthCheck;

    public function __construct(SecurityHealthCheck $healthCheck)
    {
        $this->healthCheck = $healthCheck;
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

    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $result = $this->healthCheck->check();

        return new WP_REST_Response([
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
            'metadata' => $result->getMetadata(),
        ], 200);
    }

    public function getPermissionCallback(): ?callable
    {
        return static fn (): bool => current_user_can('manage_options');
    }
}
