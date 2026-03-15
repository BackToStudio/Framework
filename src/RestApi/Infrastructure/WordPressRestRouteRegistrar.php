<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Infrastructure;

use BackTo\Framework\RestApi\Contracts\RestRouteRegistrarInterface;

use function register_rest_route;

/**
 * WordPress adapter for REST API route registration.
 */
class WordPressRestRouteRegistrar implements RestRouteRegistrarInterface
{
    /**
     * @param array<string, mixed> $args
     */
    public function register(string $namespace, string $route, array $args): void
    {
        register_rest_route($namespace, $route, $args);
    }
}
