<?php

declare(strict_types=1);

namespace {{namespace}};

use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use WP_REST_Request;
use WP_REST_Response;

class {{className}} implements RestRouteInterface
{
    public function getNamespace(): string
    {
        return '{{routeNamespace}}';
    }

    public function getRoute(): string
    {
        return '{{route}}';
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return ['GET'];
    }

    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response(['message' => 'Hello from {{className}}'], 200);
    }

    public function getPermissionCallback(): ?callable
    {
        return null;
    }
}
