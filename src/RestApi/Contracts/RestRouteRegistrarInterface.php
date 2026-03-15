<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Contracts;

/**
 * Port interface for REST API route registration.
 */
interface RestRouteRegistrarInterface
{
    /**
     * @param array<string, mixed> $args
     */
    public function register(string $namespace, string $route, array $args): void;
}
